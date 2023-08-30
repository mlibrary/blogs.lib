CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * FAQ
 * Maintainers


INTRODUCTION
------------

The Create User Permission module makes it possible to make people be
able to create users, without granting them the permission to "administer
users" (.i.e for any user having the permission, it can create new users).

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/create_user_permission

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/create_user_permission


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
 Visit: https://www.drupal.org/node/1897420 for further information.

 * Using composer:
   composer require drupal/create_user_permission
   drush en create_user_permission


CONFIGURATION
-------------

* Configure the user permissions in Administration » People » Permissions:

  - Use the Create User Permission. (Contributed module)

    Enable the check box for all the users that you want to allow to be
    able to create new user.


FAQ
---

Q: I enabled "the users", how do I test the same?

A: After enabling, login with the user other that administrator for
which you have enabled the permissions. And use the path /admin/people
create to create new users.


MAINTAINERS
-----------

Current maintainers:
 * Eirik Morland (eiriksm) - https://www.drupal.org/u/eiriksm
