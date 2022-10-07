## Contents of this file

* Introduction
* Requirements
* Installation
* Configuration
* Per user settings
* Maintainers


## INTRODUCTION

**Notify** is a lightweight module for sending plain text email
notifications about new nodes and comments posted on a Drupal web
site.

Users may subscribe to notifications about all new content, or only
subscribe to specific content types.

* For a full description of the module, visit the [project page][1].

* To submit bug reports and feature suggestions, or to track changes
  visit the project's [issue tracker][2].

* For community documentation, visit the [documentation page][3].

If you enable [node revisions][4], the
notification email will also mention the name of the last person to
edit the node.


## REQUIREMENTS

This module requires cron to be running at least as frequently as you
want to send out notifications.


## INSTALLATION

1. Install as you would normally install a contributed Drupal
   module. Visit [Installing modules][IM] for further information.

2. Enable Notify module by locating it on the list under the Extend
   tab in the administrative GUI. The tables will be created
   automagically for you at this point.

3. Set permissions on the **People » Permissions page**.

   To set the notification checkbox default on new user registration
   form, or let new users opt in for notifications during
   registration, you must grant the anonymous user the right to
   "Access Notify".

   To allow authenticated users manage what content types they
   subscribe to, you must give the authenticated user the right to
   "Access Notify".

4. Configure general notification settings.  See the next section for
   details.


## CONFIGURATION

The administrative interface is at: **Manage » Configuration »
People » Notify Settings**.

- The "Settings" tab is for setting how often notifications are sent.

- The and "Default settings" tab is to set up default presets for new
  users, and for selecting permitted notification by node type.

- The "Users" tab is to review and see per-user settings.

- The remaining two tabs ("Queue" and "Skip flags") are for managing
  the notification queue.

When setting how often notifications are sent, note that email
updates can only happen as frequently as the cron is set to run.
Check your cron settings.


### PER USER SETTINGS

To manage your own notification preferences, click on the
"Notification settings" on your "My account" page.

## MAINTAINERS

Maintainers:

* [matt2000][MC] (Matt Chapman) is the project owner.
* [gisle][GH] (Gisle Hannemyr) maintains all supported branches.
* [Larisse][LA] (Larisse Amorim) maintains the Drupal 9+ branch.

Past contributors:

* [Kjartan][KM] (Kjartan Mannes) created the projct.
* [RobRoy][RB] (Rob Roy Barreca) was a previous maintainer.

Supporting organizations

* This project has been sponsored by [Hannemyr Nye Medier AS][HNM].

[1]: https://drupal.org/project/notify
[2]: https://drupal.org/project/issues/notify
[3]: https://www.drupal.org/docs/contributed-modules/notify
[4]: https://www.drupal.org/node/320614

[IM]: https://www.drupal.org/node/1897420
[MC]: https://www.drupal.org/u/matt2000
[GH]: https://www.drupal.org/u/gisle
[LA]: https://www.drupal.org/u/larisse
[KM]: https://www.drupal.org/u/kjartan
[RB]: https://www.drupal.org/u/robroy

[HNM]: https://hannemyr.no
