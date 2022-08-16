CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usage
 * Maintainers


INTRODUCTION
------------

Adds a button to CKEditor for inserting and editing abbreviations. If an
existing abbr tag is selected, the context menu also contains a link to edit the
abbreviation.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/ckeditor_abbreviation

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/ckeditor_abbreviation


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the CKEditor Abbreviation module as you would normally install a
   contributed Drupal module.
   Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Content authoring > Text
       formats and editors.
    3. Move the CKEditor Abbreviation icon into the active toolbar. The button
       is available in the CKEditor toolbar configuration of text formats if you
       set ckeditor as text editor.
    4. Enable the filter 'Limit allowed HTML tags and correct faulty HTML'. Add
       the title attribute to the allowed HTML abbr tag, if you want to allow an
       explanation of the abbreviation.


USAGE
-----

 * Select the abbreviation you want to tag. Click the ckeditor abbreviation icon and fill in the fields in the
   opening dialog.
 * To edit a tagged abbreviation place the cursor within the abbreviation text and click the ckeditor abbreviation icon.
   Or open the context menu by right-clicking on your mouse and select "Edit Abbreviation".
 * To remove an abbreviation title attribute, delete the explanation in the ckeditor abbreviation dialog.
   In order to untag an abbreviation, delete the abbreviation in the ckeditor abbreviation dialog.


MAINTAINERS
-----------

 * Richard Papp (boromino) - https://www.drupal.org/u/boromino

Supporting organizations:

 * LakeDrops - https://www.drupal.org/lakedrops
