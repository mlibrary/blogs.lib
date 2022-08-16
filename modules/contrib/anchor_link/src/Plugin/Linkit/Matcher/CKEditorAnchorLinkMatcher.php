<?php

namespace Drupal\anchor_link\Plugin\Linkit\Matcher;

use Drupal\Component\Utility\Html;
use Drupal\linkit\MatcherBase;
use Drupal\linkit\Suggestion\DescriptionSuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * Provides specific linkit matchers for Anchor links.
 *
 * @Matcher(
 *   id = "ckeditor_anchor_link",
 *   label = @Translation("CKEditor Anchor link"),
 * )
 */
class CKEditorAnchorLinkMatcher extends MatcherBase {

  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();

    $string = ltrim($string, '#');

    $suggestion = new DescriptionSuggestion();
    $suggestion->setLabel($this->t('#@anchor_link', ['@anchor_link' => $string]))
      ->setPath('#' . $string)
      ->setGroup($this->t('Anchor links (within the same page)'));

    $suggestions->addSuggestion($suggestion);

    return $suggestions;
  }

}
