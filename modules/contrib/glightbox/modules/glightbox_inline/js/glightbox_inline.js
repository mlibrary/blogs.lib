(function (Drupal) {
  "use strict";

  // Create glightbox namespace if it doesn't exist.
  if (!Drupal.hasOwnProperty('glightbox')) {
    Drupal.glightbox = {};
  }

  /**
   * Global function to allow sanitizing captions and control strings.
   *
   * @param markup
   *   String containing potential markup.
   * @return {string}
   *   Sanitized string with potentially dangerous markup removed.
   */
  Drupal.glightbox.sanitizeMarkup = Drupal.glightbox.sanitizeMarkup || function(markup) {
    if (typeof DOMPurify !== 'undefined') {
      var purifyConfig = {
        ALLOWED_TAGS: [
          'a', 'b', 'strong', 'i', 'em', 'u', 'cite', 'code', 'br', 'p', 'div', 'h1', 'h2', 'h3', 'h4', 'span'
        ],
        ALLOWED_ATTR: [
          'href', 'hreflang', 'title', 'target'
        ]
      };
      if (typeof drupalSettings !== 'undefined' && drupalSettings.hasOwnProperty('dompurify_custom_config')) {
        purifyConfig = drupalSettings.dompurify_custom_config;
      }
      return DOMPurify.sanitize(markup, purifyConfig);
    } else if (typeof Drupal.checkPlain === 'function') {
      return Drupal.checkPlain(markup);
    } else {
      // Fallback: basic escaping.
      return String(markup).replace(/[&<>\"]/g, function (c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];
      });
    }
  };

  /**
   * Enable the GLightbox inline functionality.
   */
  Drupal.behaviors.glightboxInline = {
    attach: function (context, drupalSettings) {
      once('glightbox-inline-processed', '.glightbox-inline', context).forEach(el => {
        // Sanitize every data-* attribute on this element.
        el.getAttributeNames()
          .filter(name => name.startsWith('data-'))
          .forEach(name => {
            let clean = Drupal.glightbox.sanitizeMarkup(el.getAttribute(name));
            if (name === 'data-href') {
              clean = Drupal.glightbox.sanitizeHref(clean);
            }
            el.setAttribute(name, clean);
          });
      });
      const options = Object.assign({}, drupalSettings.glightbox || {}, {
        selector: '.glightbox-inline',
      });
      const lightboxInlineIframe = GLightbox(options);
    }
  };

})(Drupal);
