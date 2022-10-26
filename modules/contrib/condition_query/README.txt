CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

Extends conditional plugin API to add URI Query
Parameters to control the content visibility.
Drupal core conditional plugin API provides the ability to control
the visibility of content (block) using paths, etc.
This module adds new conditional plugin to allow administrators
to control the visibility of content using url query parameter.

This plugin can be used to show or hide blocks,
contents etc based on query parameter.
It supports query parameter with array ex: ?visibility[]=show

List of features using condition plugins,
1) Block
2) Rules
3) Page manager
    etc.

Example usage:
1) Display blocks if query parameter has "visibility=show"
   ( http:://www.example.com/?visibility=show )
2) Hide all blocks if query parameter has "app=true"
   ( http:://www.example.com/?app=true)


REQUIREMENTS
------------

None.


INSTALLATION
------------

* Install as usual, see http://drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

* Configure visibility of block Administration » Structure » Block layout
   Configure block » Under Visibility add/update "Request Param"

* "Negate the condition" can be used to reverse the condition,
   example : show the block if url do not have parameter
   http://www.example.com/?app=yes


MAINTAINERS
-----------

Current maintainers:
* Loganathan Harikrishnan (logan.H) - https://www.drupal.org/u/loganh
* Derek Wright (dww) - https://www.drupal.org/u/dww
