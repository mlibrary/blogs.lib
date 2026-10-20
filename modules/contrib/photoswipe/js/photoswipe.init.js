(function (Drupal, PhotoSwipeLightbox) {
  /**
   * Initialises photoswipe galleries.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.photoswipe = {
    attach(context, settings) {
      once('photoswipe', '.photoswipe-gallery', context).forEach((gallery) => {
        const lightbox = new PhotoSwipeLightbox({
          // Select the gallery.
          gallerySelector: '.photoswipe-gallery',
          // Only trigger photoswipe on links with the photoswipe class:
          childSelector: 'a.photoswipe',
          // Initialize Photoswipe with our settings:
          // eslint-disable-next-line
          pswpModule: PhotoSwipe,
          ...(settings?.photoswipe?.options || {}),
        });

        // Check if the gallery has the `data-show-download-button` data
        // attribute, to show the download button:
        if (gallery.hasAttribute('data-show-download-button')) {
          // This is a straight copy of the code example from
          // https://photoswipe.com/adding-ui-elements/#adding-download-button:
          lightbox.on('uiRegister', () => {
            lightbox.pswp.ui.registerElement({
              name: 'download-button',
              order: 8,
              isButton: true,
              title: settings?.photoswipe?.options?.downloadTitle,
              tagName: 'a',
              html: {
                isCustomSVG: true,
                inner:
                  '<path d="M20.5 14.3 17.1 18V10h-2.2v7.9l-3.4-3.6L10 16l6 6.1 6-6.1ZM23 23H9v2h14Z" id="pswp__icn-download"/>',
                outlineID: 'pswp__icn-download',
              },
              onInit: (el, pswp) => {
                el.setAttribute('download', '');
                el.setAttribute('target', '_blank');
                // Added "noreferrer", for further safety:
                el.setAttribute('rel', 'noopener noreferrer');

                pswp.on('change', () => {
                  el.href = pswp.currSlide.data.src;
                });
              },
            });
          });
        }

        // Adds ability to react on photoswipe initialization.
        const event = new CustomEvent('photoswipeLightboxBuild', {
          detail: {
            lightbox,
          },
        });

        gallery.dispatchEvent(event);

        // Initialize.
        lightbox.init();
      });
    },
  };
  // eslint-disable-next-line
})(Drupal, PhotoSwipeLightbox);
