<?php

namespace Drupal\mimemail\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for mimemail.
 */
class MimemailHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.mimemail':
        $output = '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('This is a Mime Mail component module (for use by other modules).') . '</p>';
        $output .= '<ul>';
        $output .= '<li>' . $this->t('It permits users to receive HTML email and can be used by other modules. The mail functionality accepts an HTML message body, mime-encodes it and sends it.') . '</li>';
        $output .= '<li>' . $this->t('If the HTML has embedded graphics, these graphics are MIME-encoded and included as a message attachment.') . '</li>';
        $output .= '<li>' . $this->t("Adopts your site's style by automatically including your theme's stylesheet files in a themeable HTML message format.") . '</li>';
        $output .= '<li>' . $this->t("If the recipient's preference is available and they prefer plaintext, the HTML will be converted to plain text and sent as-is. Otherwise, the email will be sent in themeable HTML with a plaintext alternative.") . '</li>';
        $output .= '<li>' . $this->t('Allows you to theme messages with a specific mailkey.') . '</li>';
        $output .= '<li>' . $this->t('Converts CSS styles into inline style attributes.') . '</li>';
        $output .= '<li>' . $this->t('Provides simple system actions and Rules actions to send HTML email with embedded images and attachments.') . '</li>';
        $output .= '</ul>';
        return $output;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme() {
    return [
      'mimemail_message' => [
        'variables' => [
          'module' => '',
          'key' => '',
          'recipient' => '',
          'subject' => '',
          'body' => '',
          'params' => [],
          'langcode' => '',
        ],
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_mimemail_message', module: 'template')]
  public static function templatePreprocessMimemailMessage(array &$variables) {
    $variables['module'] = str_replace('_', '-', $variables['module']);
    $variables['key'] = str_replace('_', '-', $variables['key']);
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   *
   * The template used is the one that is most specific. The theme system
   * looks for templates starting at the end of the $hook array and works
   * towards the beginning, so for the core user module's 'password_reset'
   * email the order of precedence for selecting the template used will be:
   * 1. mimemail-message--user--password-reset.html.twig
   * 2. mimemail-message--user.html.twig
   * 3. mimemail-message.html.twig
   * Note that mimemail-message.html.twig is the default template for
   * messages sent by the Mime Mail module, and will be used by default
   * unless a more-specific template is found.
   */
  #[Hook('theme_suggestions_mimemail_message')]
  public static function themeSuggestionsMimemailMessage(array $variables) {
    return [
      'mimemail_message__' . $variables['module'],
      'mimemail_message__' . $variables['module'] . '__' . $variables['key'],
    ];
  }

}
