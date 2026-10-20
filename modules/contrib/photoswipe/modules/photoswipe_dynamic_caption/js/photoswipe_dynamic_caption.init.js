/* eslint-disable no-undef */
/* eslint-disable no-new */
(function (Drupal, PhotoSwipeDynamicCaption) {
  /**
   * Adds caption plugin to photoswipe.
   */
  Drupal.behaviors.photoswipeCaption = {
    attach(context, settings) {
      const captionOptions =
        settings?.photoswipe?.options?.captionOptions || {};
      const allowHtmlCaptions =
        settings?.photoswipe?.options?.allowHtmlCaptions || false;

      // Attaches caption plugin.
      once('photoswipeCaption', '.photoswipe-gallery', context).forEach(
        (gallery) => {
          gallery.addEventListener('photoswipeLightboxBuild', (e) => {
            const { lightbox } = e.detail;
            new PhotoSwipeDynamicCaption(lightbox, {
              captionContent: (slide) => {
                const title =
                  slide.data.element.getAttribute('data-overlay-title') || '';
                // Sanitize with DOMPurify when opted in and available;
                // otherwise escape as plain text.
                if (allowHtmlCaptions && typeof DOMPurify !== 'undefined') {
                  return DOMPurify.sanitize(title);
                }
                return Drupal.checkPlain(title);
              },
              ...captionOptions,
            });
          });
        },
      );
    },
  };
  // eslint-disable-next-line
})(Drupal, PhotoSwipeDynamicCaption);
