autonotifications
============

SUMMARY
---------
1. Add automatic notification settings when a user registers, including digest settings.
2. Command line script to add notifications and digest settings to existing users.

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

    d. Reload the autoload arrays

        bin/php/ezpgenerateautoloads.php

    e. Clear the cache:

        bin/php/ezcache.php --clear-all

2. In the admin interface, create a workflow that contains this event:

* Choose which object versions should cause the notifications to be set. Typically you will want only
publishing a new user object to add notifications. Otherwise notifications will be added every time the user's profile is updated.

* Select the user groups that will be affected

* Select the subtree notifications that will be added to the users.

3. Save the workflow.

4. Typically you will want to bind the post-publish trigger to the workflow
you've created. However, sometimes you want to add notifications when a user
object is moved from one user group to another. If so, you should bind the
workflow to the post-move trigger instead. You will need to activate this
trigger in `settings/override/workflow.ini.append.php`:

        <?php /* #?ini charset="utf-8"?

        [OperationSettings]
        AvailableOperationList[]=content_move

        */ ?>

5. Override the digest settings with your own settings in `settings/override/autonotifications.ini.append.php`

USAGE
-----

If you want to add notifications to existing users (as if they had just registered) then after setting up the workflow run:

    php extension/autonotifications/bin/php/update_existing_users.php
