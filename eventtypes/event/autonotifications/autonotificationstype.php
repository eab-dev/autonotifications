<?php
class AutoNotificationsType extends eZWorkflowEventType
{
	const WORKFLOW_TYPE_STRING = "autonotifications";

	function __construct()
	{
		$this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, "AutoNotifications"  );
		$this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after' ) ) ) );
	}

	function attributeDecoder( $event, $attr )
	{
		switch ( $attr )
		{
			case 'selected_usergroups':
			{
				$attributeValue = trim( $event->attribute( 'data_text1' ) );
				$returnValue = empty( $attributeValue ) ? array() : explode( ',', $attributeValue );
			}break;

			case 'selected_subtrees':
			{
				$attributeValue = trim( $event->attribute( 'data_text2' ) );
				$returnValue = empty( $attributeValue ) ? array() : explode( ',', $attributeValue );
			}break;

			 default:
				$returnValue = null;
		}
		return $returnValue;
	}

	function typeFunctionalAttributes( )
	{
		return array('selected_usergroups', 'selected_subtrees' );
	}

	function attributes()
	{
		return array_merge( array( 'usergroups','subtrees' ), eZWorkflowEventType::attributes() );
	}

	function hasAttribute( $attr )
	{
		return in_array( $attr, $this->attributes() );
	}

	function attribute( $attr )
	{
		switch( $attr )
		{
			case 'usergroups':
			{
				$groups = eZPersistentObject::fetchObjectList( eZContentObject::definition(), array( 'id', 'name' ),
																array( 'contentclass_id' => 3 ), null, null, false );
				foreach ( $groups as $key => $group )
				{
					$groups[$key]['Name'] = $group['name'];
					$groups[$key]['value'] = $group['id'];
				}
				return $groups;
			}
			break;
		}
		return eZWorkflowEventType::attribute( $attr );
	}

	function execute( $process, $event )
	{
		$parameters = $process->attribute( 'parameter_list' );
		$objectID = $parameters['object_id'];
		$object = eZContentObject::fetch( $objectID );

		$subtrees = $event->attribute( 'selected_subtrees' );
		$groups = $event->attribute( 'selected_usergroups' );
		$userClassIDArray=eZUser::fetchUserClassNames();

		if ( !$object )
		{
			eZDebugSetting::writeError( 'kernel-workflow-autonotifications', "No object with ID $objectID", 'AutoNotificationsType::execute' );
			return eZWorkflowType::STATUS_WORKFLOW_CANCELLED;
		}
		if(in_array($object->attribute('class_identifier'), $userClassIDArray) && is_array($subtrees) && !empty($subtrees) && is_array($groups) && !empty($groups))
		{
			foreach ( $groups as $groupID )
			{
				if ( $groupID != false )
				{
					$group = eZContentObject::fetch( $groupID );
					if ( isset( $group ) )
					{
						foreach ( $group->attribute( 'assigned_nodes' ) as $assignedNode )
						{
							$userNodeArray = $assignedNode->subTree( array( 'ClassFilterType' => 'include',
																			'ClassFilterArray' => $userClassIDArray,
																			'Limitation' => array() ) );
							foreach ( $userNodeArray as $userNode )
							{
								$userIDArray[] = $userNode->attribute( 'contentobject_id' );
							}
						}
					}
				}
			}
			$userIDArray = array_unique( $userIDArray );
			if(in_array($objectID, $userIDArray))
			{
				foreach($subtrees as $subtree)
				{
					$rule =eZSubtreeNotificationRule::create( $subtree, $objectID );
					$rule->store();
				}
			}
		}
		return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
	}

	function validateHTTPInput( $http, $base, $workflowEvent, &$validation )
	{
		$returnState = eZInputValidator::STATE_ACCEPTED;
		$reason = array();

		if ( !$http->hasSessionVariable( 'BrowseParameters' ) )
		{
			$selected_subtrees = array_unique( $this->attributeDecoder( $workflowEvent, 'selected_subtrees' ) );
			if ( is_array( $selected_subtrees ) and
				 count( $selected_subtrees ) > 0 )
			{
				foreach( $selected_subtrees as $SubtreeID )
				{
					if ( !is_numeric( $SubtreeID ) or
						 !is_object( $Subtree = eZContentObject::fetch( $SubtreeID ) ) )
					{
						$returnState = eZInputValidator::STATE_INVALID;
						$reason[ 'list' ][] = $SubtreeID;
					}
				}
				$reason[ 'text' ] = ezpI18n::tr( "design/autonotifications", "Some subtrees are incorrect" );
			}
		}

		if ( $returnState == eZInputValidator::STATE_INVALID )
		{
			$validation[ 'processed' ] = true;
			$validation[ 'events' ][] = array( 'id' => $workflowEvent->attribute( 'id' ),
											   'placement' => $workflowEvent->attribute( 'placement' ),
											   'workflow_type' => &$this,
											   'reason' => $reason );
		}
		return $returnState;
	}

	function fetchHTTPInput( $http, $base, $event )
	{
		$usersVar = $base . "_event_autonotifications_selected_usergroups_" . $event->attribute( "id" );
		if ( $http->hasPostVariable( $usersVar ) )
		{
			$event->setAttribute( "data_text1", implode( ',', $http->postVariable( $usersVar ) ) );
		}

		if ( $http->hasSessionVariable( 'BrowseParameters' ) )
		{
			$browseParameters = $http->sessionVariable( 'BrowseParameters' );
			if ( isset( $browseParameters['custom_action_data'] ) )
			{
				$customData = $browseParameters['custom_action_data'];
				if ( isset( $customData['event_id'] ) &&
					 $customData['event_id'] == $event->attribute( 'id' ) )
				{
					if ( !$http->hasPostVariable( 'BrowseCancelButton' ) and
						 $http->hasPostVariable( 'SelectedNodeIDArray' ) )
					{
						$nodeIDArray = $http->postVariable( 'SelectedNodeIDArray' );
						if ( is_array( $nodeIDArray ) and
							 count( $nodeIDArray ) > 0 )
						{

							switch( $customData['browse_action'] )
							{
							case 'AddSubtrees':
								{
									$event->setAttribute( 'data_text2', implode( ',',
																				 array_unique( array_merge( $this->attributeDecoder( $event, 'selected_subtrees' ),
																											$nodeIDArray ) ) ) );
								} break;

							}
						}
						$http->removeSessionVariable( 'BrowseParameters' );
					}
				}
			}
		}
	}

	function customWorkflowEventHTTPAction( $http, $action, $workflowEvent )
	{
		$eventID = $workflowEvent->attribute( "id" );
		$module =& $GLOBALS['eZRequestedModule'];
		switch ( $action )
		{
			case 'AddSubtrees' :
			{

				eZContentBrowse::browse( array( 'action_name' => 'SelectMultipleSubtrees',
												'from_page' => '/workflow/edit/' . $workflowEvent->attribute( 'workflow_id' ),
												'custom_action_data' => array( 'event_id' => $eventID,
																			   'browse_action' => $action ),
												 ),
										 $module );
			} break;
			case 'RemoveSubtrees' :
			{
				if ( $http->hasPostVariable( 'RemoveSubtreesIDArray_' . $eventID ) )
				{
					$workflowEvent->setAttribute( 'data_text2', implode( ',', array_diff( $this->attributeDecoder( $workflowEvent, 'selected_subtrees' ),
																						  $http->postVariable( 'RemoveSubtreesIDArray_' . $eventID ) ) ) );
				}
			} break;
		}
	}

}

eZWorkflowEventType::registerEventType( AutoNotificationsType::WORKFLOW_TYPE_STRING, "autonotificationstype" );

?>
