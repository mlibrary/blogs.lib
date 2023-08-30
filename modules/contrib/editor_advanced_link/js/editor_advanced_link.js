((Drupal, once, $) => {
  const toggleDetails = () => {
    $('#drupal-modal').dialog({
      position: {
        of: window,
      },
    });
  };

  const targetCheckboxChange = (evt) => {
    const checkbox = evt.currentTarget;
    const relAttributeField = document.querySelector(
      'input[data-drupal-selector="edit-attributes-rel"]',
    );

    let relAttributes = relAttributeField.value.split(' ');
    if (checkbox.checked) {
      relAttributes.push('noopener');
      Drupal.announce(
        Drupal.t('The noopener attribute has been added to rel.'),
      );
    } else {
      relAttributes = relAttributes.filter((value) => value !== 'noopener');
      Drupal.announce(
        Drupal.t('The noopener attribute has been removed from rel.'),
      );
    }

    // Remove empty items.
    relAttributes = relAttributes.filter((value) => value.length);
    // Deduplicate items.
    relAttributes = [...new Set(relAttributes)];

    relAttributeField.value = relAttributes.join(' ');
  };

  Drupal.behaviors.editor_advanced_link = {
    attach(context) {
      // Reset modal window position when advanced details element is opened or
      // closed to prevent the element content to be out of the screen.
      once(
        'editor_advanced_link',
        '.editor-link-dialog details[data-drupal-selector="edit-advanced"]',
      ).forEach((details) => {
        details.addEventListener('toggle', toggleDetails);
      });

      // Add noopener to rel attribute if open link in new window checkbox is
      // checked.
      if (
        context.querySelector(
          'input[data-drupal-selector="edit-attributes-rel"]',
        )
      ) {
        once(
          'editor_advanced_linktargetrel',
          'input[data-drupal-selector="edit-attributes-target"]',
        ).forEach((element) => {
          element.addEventListener('change', targetCheckboxChange);
        });
      }
    },
  };
})(Drupal, once, jQuery);
