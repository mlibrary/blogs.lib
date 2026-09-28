<?php

namespace Drupal\glightbox\Hook;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
/**
 * Hook implementations for glightbox.
 */
class GlightboxHooks
{
    use StringTranslationTrait;
    /**
     * Implements hook_help().
     */
    #[Hook('help')]
    public function help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match)
    {
        switch ($route_name) {
            case 'help.page.glightbox':
                $output = '';
                $output .= '<h3>' . $this->t('About') . '</h3>';
                $output .= '<p>' . $this->t('GLightbox is a pure javascript lightbox. It can display images, iframes, inline content and videos with optional autoplay for YouTube, Vimeo and even self hosted videos.') . '</p>';
                $output .= '<h3>' . $this->t('Configuration') . '</h3>';
                $output .= '<p>' . $this->t('Go to "Configuration" -> "Media" -> "GLightbox" to find all the configuration options.') . '</p>';
                return $output;
        }
    }
    /**
     * Implements hook_theme().
     */
    #[Hook('theme')]
    public function theme()
    {
        return [
            'glightbox_formatter' => [
                'variables' => [
                    'item' => NULL,
                    'item_attributes' => NULL,
                    'entity' => NULL,
                    'settings' => NULL,
                ],
                'file' => 'glightbox.theme.inc',
            ],
            'glightbox_responsive_formatter' => [
                'variables' => [
                    'item' => NULL,
                    'item_attributes' => NULL,
                    'entity' => NULL,
                    'settings' => NULL,
                ],
                'file' => 'glightbox.theme.inc',
            ],
        ];
    }
    /**
     * Implements hook_library_info_alter().
     */
    #[Hook('library_info_alter')]
    public function libraryInfoAlter(&$libraries, $module)
    {
        if ($module == 'glightbox') {
            // In case that the libraries are included locally, use those instead of the
            // CDN.
            // @see https://www.drupal.org/node/3099614
            $library_file_finder = \Drupal::service('library.libraries_directory_file_finder');
            $current_libraries = [
                'js' => 'plyr/plyr.js',
                'css' => 'plyr/plyr.css',
            ];
            $path = $library_file_finder->find($current_libraries['js']);
            if ($path) {
                $libraries['plyr']['js'] = [
                    '/' . $path => [
                        'minified' => TRUE,
                        'external' => FALSE,
                    ],
                ];
            }
            $path = $library_file_finder->find($current_libraries['css']);
            if ($path) {
                $libraries['plyr']['css'] = [
                    'component' => [
                        '/' . $path => [
                            'minified' => TRUE,
                            'external' => FALSE,
                        ],
                    ],
                ];
            }
        }
    }
    /**
     * Implements hook_token_info().
     */
    #[Hook('token_info')]
    public function tokenInfo()
    {
        $types['paragraph_container'] = [
            'name' => $this->t('Paragraph Container'),
            'description' => $this->t('Custom container tokens for Paragraph entities.'),
            'needs-data' => 'paragraph',
        ];
        // You can optionally define static tokens here, but dynamic tokens (fields)
        // are usually defined in hook_tokens() directly.
        return [
            'types' => $types,
        ];
    }
    /**
     * Implements hook_tokens().
     */
    #[Hook('tokens')]
    public function tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata)
    {
        $replacements = [
        ];
        if ($type == 'paragraph_container' && !empty($data['paragraph_container'])) {
            /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
            $paragraph = $data['paragraph_container'];
            foreach ($tokens as $name => $original) {
                // Handle nested tokens for fields like "field_example".
                $field_parts = explode(':', $name, 2);
                $field_name = $field_parts[0];
                if ($paragraph->hasField($field_name) && !$paragraph->get($field_name)->isEmpty()) {
                    $field_value = $paragraph->get($field_name)->view([
                        'label' => 'hidden',
                    ]);
                    // Render the field value to plain text.
                    $rendered_value = (string) \Drupal::service('renderer')->renderRoot($field_value);
                    $rendered_value = trim($rendered_value);
                    // Check for deeper nesting tokens if present.
                    if (isset($field_parts[1])) {
                        // Implement further sub-tokens if required (e.g., formatting).
                        // For simplicity, this example assumes one-level fields.
                        continue;
                    }
                    // Mark explicitly as safe to prevent escaping.
                    $replacements[$original] = \Drupal\Core\Render\Markup::create($rendered_value);
                    // Add cacheability metadata.
                    $bubbleable_metadata->addCacheableDependency($paragraph);
                } else {
                    // Fallback for empty or missing fields.
                    $replacements[$original] = '';
                }
            }
        }
        return $replacements;
    }
}
