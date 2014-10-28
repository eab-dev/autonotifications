autonotifications
============

SUMMARY
---------
1. Add automatic notification settings when a user registers, including digest settings.
2. Command line script to add notifications and digest settings and to existing users.

Based on work by Pierre Martel http://projects.ez.no/autonotifications

You can choose the user group and subtrees, it will add the notification on the subtrees only if the user is in the group.

LICENSE
--------

GPL 2.0

TO INSTALL
----------

1. Install and activate the extension in the usual way:

    a. Copy the `autonotifications` folder to the `extension` folder.

    b. Edit `settings/override/site.ini.append.php`

    c. Under `[ExtensionSettings]` add:

        ActiveExtensions[]=autonotifications

    d. Clear the cache:

        bin/php/ezcache.php --clear-all

2. In the admin interface, create a workflow that contains this event.

3. Select the user groups that will be affected and the subtrees that will be added to the users.

4. Save the workflow.

5. Bind the post-publish trigger to the workflow you've created.

6. Override the digest settings with your own settings in `settings/override/autonotifications.ini.append.php`

USAGE
-----

If you want to add notifications to existing users (as if they had just registered) then after setting up the workflow run:

    php extension/autonotifications/bin/php/update_existing_users.php
