Image Resize filter for Drupal 8
--------------------------------

This filter makes it easy to resize images, especially when combined with CKeditor.
Users never have to worry about scaling image sizes again, just insert an image and
set it's height and width properties in HTML and the image is resized on output.

Install
-------

1) Normal installation https://www.drupal.org/docs/extending-drupal/installing-drupal-modules.
2) Visit Configuration->Content authoring-> Text formats and editors (/admin/config/content/formats).
   Click  "Configure" next to the text format you want to enable the image resize filter on.
3) Check the box for "Image Resize Filter: Resize images based on their given height and width attributes" for
   resize the images automatically and "Image Resize Filter: Link images derivates to source" to link the images
   to their original image.
4) IMPORTANT: Re-order your enabled filters under "Filter processing order". The Image resize filters must be
   the last filters in the list.
5) Optional: Go to the Filter Settings to set additional configuration.

Author D7 Version: Nathan Haug (quicksketch)
Maintainer D8 Version: David Valdez (gnuget) and https://agaric.coop
