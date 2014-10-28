<?php

require 'autoload.php';//make sure relevant eZ Classes can be loaded
$cli = eZCLI::instance();//provides interface with CLI

//Setting up the script object itself:
$script = eZScript::instance( array( 	'description' =>  "Add notification settings for users as if they had just registered\n",
							'use-session' => false,
							'use-modules' => true,
							'use-extensions' => true,
							'debug-output' => false,
							'debug-message' =>true
	                                     ) );
$script->startup();
$script->initialize();

// We need to find which user groups this update should apply to, so fetch the workflows and search for events with a matching event type

$userClassIDArray = eZUser::fetchUserClassNames();
$eventsFound = 0;
$eventTypeString = 'event_' . AutoNotificationsType::WORKFLOW_TYPE_STRING;
$userIDArray[] = array();
$workflows = eZWorkflow::fetchList();

foreach ( $workflows as $workflow )
{
	$eventList = $workflow->fetchEvents();
	foreach ( $eventList as $event )
	{
		if ( $event->TypeString == $eventTypeString )
		{
			$eventsFound++;
			$userGroupObjectIDArray = AutoNotificationsType::attributeDecoder( $event, 'selected_usergroups' );
			$subtrees = AutoNotificationsType::attributeDecoder( $event, 'selected_subtrees' );
			
			// fetch children of usergroups, foreach child do
			foreach( $userGroupObjectIDArray as $userGroupObjectID )
			{
				$userGroup = eZContentObject::fetch( $userGroupObjectID );
				if ( isset( $userGroup ) )
				{
					foreach ( $userGroup->assignedNodes() as $assignedNode )
					{
						$userNodeArray = $assignedNode->subTree( array( 'ClassFilterType' => 'include',
																		'ClassFilterArray' => $userClassIDArray,
																		'Limitation' => array() ) );
						foreach ( $userNodeArray as $userNode )
							$userIDArray[] = $userNode->ContentObjectID;
					}
				}
            }
            $userIDArray = array_unique( $userIDArray );
			foreach ( $userIDArray as $userID )
			{
				// Set the digest options according to the settings in autonotifications.ini
				AutoNotificationsType::setDigestOptions( $userID );

				// Below causes an SQL error but seems to work. Without this test, existing notifications would be duplicated
				$notificationNodeIDList = eZSubtreeNotificationRule::fetchNodesForUserID( $userID, false );
				foreach ( $subtrees as $subtreeNodeID )
				{
					if ( !in_array( $subtreeNodeID, $notificationNodeIDList ) )
					{
						$rule = eZSubtreeNotificationRule::create( $subtreeNodeID, $userID );
						$rule->store();
					}
				}
			}
		}
	}
}

if ( $eventsFound == 0 )
	$cli->output( "No autonotification events found, have you set up a workflow?" );

$script->shutdown(); //stop the script

?>