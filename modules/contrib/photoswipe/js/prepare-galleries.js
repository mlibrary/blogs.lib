(function (Drupal) {
  /**
   * Adds a a photoswipe gallery fallback wrapper.
   *
   * Adds a a photoswipe gallery fallback wrapper, if there is no photoswipe
   * gallery wrapper in the current context (e.g. when the
   * "photoswipe_remove_gallery_wrapper_class" formatter setting is enabled and no
   * gallery wrapper is manually added in the current context.)
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.photoswipePrepareGalleries = {
    /**
     * Ensures all photoswipe photos are inside an photoswipe-gallery wrapper.
     * @param context
     */
    attach(context) {
      once('photoswipePrepareGalleries', 'a.photoswipe', context).forEach(
        (element) => {
          if (!element.closest('.photoswipe-gallery')) {
            element.outerHTML = `<span class="photoswipe-gallery photoswipe-gallery--fallback-wrapper">${element.outerHTML}</span>`;
          }
        },
      );
    },
  };
})(Drupal);
