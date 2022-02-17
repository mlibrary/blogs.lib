# Views Migration

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Upgrading

INTRODUCTION
------------

This module provides views migrate from drupal 7 to drupal 8 or 9. 

 - Download and install the migrate_plus module into your new Drupal 8 site

REQUIREMENTS
------------

 * This module requires migrate_plus module.

INSTALLATION
------------

The installation of this module is like other Drupal modules.

 1. Copy/upload the views_migration module to the modules directory.

 2. Enable the 'views_migration' module and desired sub-modules in 'Extend'.
   (/admin/modules)

CONFIGURATION
-------------

 * Configure your drupal 7 database in Drupal 8 upgrade /upgrade page
 * check with drush migrate:status in your terminal
    ```sh
    drush migrate:status d7_views_migration
    ```
 * Import Drupal 7 views with 
    ```sh
    drush migrate:import d7_views_migration
    ```
