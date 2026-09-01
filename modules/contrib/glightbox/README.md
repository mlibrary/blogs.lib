# GLightbox

GLightbox is a pure javascript lightbox. It can display images, iframes,
inline content and videos with optional autoplay for YouTube, Vimeo and even self hosted videos:
[GLightbox](https://biati-digital.github.io/glightbox/)

GLightbox Drupal module contains only images field formatter for now.
Video and iFrames field formatters will be added soon. But you can use
GLightbox in custom block or module to display video in popup.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/glightbox).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/glightbox).


## Table of contents

- Features
- Requirements
- Installation
- Configuration
- Maintainers


## Features

The GLightbox module:

- Works as a Formatter in entities and in views.
- Excellent integration with core image field and image styles and the Insert
  module

The GLightbox plugin:

- Small - only 11KB Gzipped
- Fast and Responsive - works with any screen size
- Gallery Support - Create multiple galleries
- Response Images Support - Let the browser use
  the optimal image for the current screen resolution
- Video Support - YouTube, Vimeo and self-hosted videos with autoplay
- Inline content support - display any inline content
- Iframe support - need to embed an iframe? no problem
- Keyboard Navigation - esc, arrows keys, tab and enter is all you need
- Touch Navigation - mobile touch events
- Zoomable images - zoom and drag images on mobile and desktop
- API - control the lightbox with the provided methods
- Themeable - create your skin or modify
  the animations with some minor css changes

## GLightbox Inline

If you want to include GLightbox scripts on all pages, you need to enable
GLightbox Inline module. Then .glightbox, .glightbox-inline classes will initialize
popup automatically on all pages.

## Responsive Images

Choose "GLightbox Responsive" as your image formatter. Standalone, only the
content/trigger image can make use of responsive image styles. If you install
the glightbox_inline module, responsive image styles will be available for the
GLightbox image as well.

## Requirements

Just GLightbox plugin in `"libraries"` folder.


## Installation

1. Install as you would normally install a contributed Drupal module. For further
   information, see
   [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

2. Change the permission of glightbox plugin inside 'libraries' folder.

3. Go to "Administer" -> "Extend" and enable the GLightbox module.


## Configuration

Go to "Configuration" -> "Media" -> "GLightbox" to find all the configuration options.

## Recommended modules

Looking for Image gallery module? You can try these modules with GLightbox integration:

[Extra Block Types (EBT): Image Gallery](https://www.drupal.org/project/ebt_image_gallery)
[Extra Paragraph Types (EPT): Image Gallery](https://www.drupal.org/project/ept_image_gallery)
[Extra Block Types (EBT): Image](https://www.drupal.org/project/ebt_image)
[Extra Paragraph Types (EPT): Image](https://www.drupal.org/project/ept_image)

Looking for Video gallery module? You can try these modules with GLightbox integration:

[Extra Block Types (EBT): Video and Image Gallery](https://www.drupal.org/project/ebt_video_and_image_gallery)
[Extra Paragraph Types (EPT): Video and Image Gallery](https://www.drupal.org/project/ept_video_and_image_gallery)
[Extra Block Types (EBT): Video](https://www.drupal.org/project/ebt_video)
[Extra Paragraph Types (EPT): Video](https://www.drupal.org/project/ept_video)

## Maintainers

- Ivan Abramenko - [levmyshkin](https://www.drupal.org/u/levmyshkin)
