<?php

namespace Drupal\views_block_filter_block\Plugin\views\display;

use Drupal\ctools_views\Plugin\Display\Block;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Enables exposed filter rendering as a separate block.
 */
class ViewsBlockFilterBlockPluginDisplayBlock extends Block {

  /**
   * Allows block views to put exposed filter forms in blocks.
   */
  public function usesExposedFormInBlock() {
    return TRUE;
  }

  /**
   * Block views use exposed widgets only if AJAX is set.
   */
  public function usesExposed() {
    return DisplayPluginBase::usesExposed();
  }

  /**
   * Keeps a NULL link display when one does not exist or not provided.
   */
  public function getLinkDisplay() {
    $display_id = $this->getOption('link_display');
    // If unknown, return NULL.
    if (empty($display_id) || !$this->view->displayHandlers->has($display_id)) {
      return NULL;
    }
    else {
      return $display_id;
    }
  }

}
