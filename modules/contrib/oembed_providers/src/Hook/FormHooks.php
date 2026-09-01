<?php

namespace Drupal\oembed_providers\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Drupal\oembed_providers\Helper;

/**
 * Hooks for forms.
 */
class FormHooks {

  /**
   * Alters Media Type edit form.
   */
  #[Hook('form_media_type_edit_form_alter')]
  public function mediaTypeEditFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    /** @var \Drupal\Core\Entity\EntityFormInterface */
    $callback_object = $form_state->getBuildInfo()['callback_object'];
    /** @var \Drupal\media\MediaSourceInterface */
    $source = $callback_object->getEntity()->getSource();
    // Only render warning message for media types with oEmbed source.
    if ($source instanceof OEmbedInterface) {
      $warning = [
        '#markup' => Helper::disabledProviderSecurityWarning(),
        // Simulate warning message.
        '#prefix' => '<div role="contentinfo" aria-label="Warning message" class="messages messages--warning">',
        '#suffix' => '</div>',
      ];
      array_unshift($form['source_dependent']['source_configuration'], $warning);
    }
  }

}
