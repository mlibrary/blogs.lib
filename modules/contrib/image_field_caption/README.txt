|=====================|
| Image Field Caption |
|=====================|

  Provides a caption text area for image fields.

  This module behavior is based on the Image Field Caption module for Drupal 7 (7.x-2.1).
  https://www.drupal.org/project/image_field_caption
  
  The first adaptation for Drupal 8 was created by Echofive with the support of Wieni for the time,
  the money and the coffee ;)
  
  https://www.drupal.org/u/echofive
  https://www.drupal.org/wieni

|==============|
| Installation |
|==============|

  1. Download the module.
  2. Upload module to the modules/contribs folder.
  3. Enable the module.
  4. Flush all of Drupal's caches.

|=======|
| Usage |
|=======|

  1. Add a new image field to a content type, or use an existing image field and 
    set the field format to "Image with caption" on the "Manage display" tab.
  2. Add or edit a node or any other entity with an image field.
  3. Go to the image field on the entity form.
  4. Enter text into the caption text area and choose format.
  5. Save the entity.
  6. View the entity to see your image field caption.   
      
|===============|
| Configuration |
|===============|

  The configuration is only done on a per field basis.

|===============|
| Caption Theme |
|===============|

  By default, an image field's caption will be rendered below the image. 
  To customize the image caption display, copy the image-caption-formatter.html.twig file
  to your theme's directory and adjust the html for your needs. 
  Flush Drupal's theme registry cache to have it recognize your theme's new file:

  themes/custom/MY_THEME/image-caption-formatter.html.twig

|=============|
| Caption CSS |
|=============|

  To make changes to the caption css, use this CSS selector:

  blockquote.image-field-caption { /* add custom css here */ }

|==================|
| More Information |
|==================|

  About the Drupal 7 version (for legacy).
  http://www.drupal.org/project/image_field_caption
  http://www.tylerfrankenstein.com/image_field_caption

