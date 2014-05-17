autonotifications
============

SUMMARY
---------
1. Add a automatic notification when a user registers.
2. Command line script to add notifications to existing users.

Based on work by Pierre Martel http://projects.ez.no/autonotifications

You can choose the user group and subtrees, it will add the notification on the subtrees only if the user is in the group.

LICENSE
--------

GPL 2.0

TO INSTALL
-----------

1. Install and activate the extension in the usual way.

2. In the admin interface, create a workflow that contains this event.

3. Select the user groups that will be affected and the subtrees that will be added to the users.

4. Save the workflow.

5. Bind the post-publish trigger to the workflow you've created.

6. If you want to add notifications to existing users (as if they had just registered) then after setting up the workflow run

php extension/autonotifications/bin/php/update_existing_users.php
