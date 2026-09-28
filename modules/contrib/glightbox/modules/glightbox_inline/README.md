# GLIGHTBOX INLINE

# INTRODUCTION

[GLightbox](https://biati-digital.github.io/glightbox) is a light-weight, pure Javascript,
customizable lightbox plugin for
[GLightbox module](https://drupal.org/project/glightbox) integration to add
support for GLightbox-ing inline elements in your templates and content.

# FEATURES
glightbox_inline allows you to open content already on the page within a glightbox.
It also allows to load content via AJAX.

# REQUIREMENTS

- Main [glightbox module](https://drupal.org/project/glightbox).


# INSTALLATION

1. Install the module as normal, see also
[core docs](https://www.drupal.org/documentation/install/modules-themes/modules-8)

# USAGE
To create an element which opens the GLightbox on click:

- Enable the module. No other options or configuration exist.
- Add the class `glightbox-inline` to an element and make href value a selector for the
element you wish to open. Eg,
`<a class="glightbox-inline" href="#user-login">User Login</a>` will open a
popup with the first element with the id `#user-login` as the content.
- Optional add `data-glightbox` to the link to control the size of the modal window,
for example `data-glightbox="width: 700; height: auto"`.
- If you want to display page in popup, just add path in href attribute:
`href="/node/42"` or `href="https://drupalbook.org"`
- You also can set href attribute with URL to video or image, for example:
`href="https://drupalbook.org/sites/default/files/video.mp4` or YouTube video
`href="https://youtu.be/g2coDPosRSs`.
Video will be playing automatically in popup.
- See more examples here: https://biati-digital.github.io/glightbox/
