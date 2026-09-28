<?php

namespace Drupal\glightbox_inline\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
/**
 * Hook implementations for glightbox_inline.
 */
class GlightboxInlineHooks
{
    use StringTranslationTrait;
    /**
     * Implements hook_page_attachments().
     */
    #[Hook('page_attachments')]
    public function pageAttachments(array &$page)
    {
        \Drupal::service('glightbox.attachment')->attach($page);
        $page['#attached']['library'][] = 'glightbox_inline/glightbox_inline';
    }
    /**
     * Implements hook_help().
     */
    #[Hook('help')]
    public function help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match)
    {
        switch ($route_name) {
            case 'help.page.glightbox_inline':
                return $this->t('<p>The GLightbox Inline module allows you to open content already on the page within a glightbox.</p>
<p>See the <a href=":project_page">project page on Drupal.org</a> for more details.</p>', [
                    ':project_page' => 'https://www.drupal.org/project/glightbox',
                ]);
        }
    }
}
