<?php

namespace Drupal\Tests\notify\Traits;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Config\Config;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Provides methods useful for Kernel and Functional Notify tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait NotifyCommonTrait {

  use CommentTestTrait;

  /**
   * Creates a user that's being notified of nodes and comments.
   *
   * @param array $permissions
   *   (optional) The permissions to set for the user.
   * @param array $notify_settings
   *   (optional) the notify settings to set.
   *
   * @return \Drupal\user\Entity\User
   *   A fully loaded user object.
   */
  protected function createNotifyUser(array $permissions = [], array $notify_settings = []): User {
    // Make sure that the user always may access content and notify.
    if (!in_array('access notify', $permissions)) {
      $permissions[] = 'access notify';
    }
    if (!in_array('access content', $permissions)) {
      $permissions[] = 'access content';
    }

    $account = $this->drupalCreateUser($permissions);

    $notify_settings += [
      'status' => 1,
      'node' => 1,
      'comment' => 1,
    ];
    $this->container->get('notify')->setUserNotify($account->id(), $notify_settings);

    return $account;
  }

  /**
   * Creates a new article node.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the node.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   */
  protected function createNode(array $settings = []): NodeInterface {
    $settings += [
      'title'  => $this->randomMachineName(8),
      'type'  => 'article',
      'uid'  => 0,
    ];
    $node = Node::create($settings);
    $node->save();

    return $node;
  }

  /**
   * Changes notify settings for certain keys.
   *
   * @param array $settings
   *   The settings to set.
   *
   * @return \Drupal\Core\Config\Config
   *   The notify configuration object.
   */
  protected function setNotifySettings(array $settings): Config {
    $config = $this->container->get('config.factory')
      ->getEditable('notify.settings');

    foreach ($settings as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    return $config;
  }

  /**
   * Changes notify states for certain keys.
   *
   * @param array $states
   *   The states to set.
   */
  protected function setNotifyStates(array $states) {
    $this->container->get('state')->setMultiple($states);
  }

  /**
   * Adds a comment field to the article content type.
   */
  protected function installCommentField() {
    CommentType::create([
      'id' => 'comment',
      'label' => 'Default comments',
      'description' => 'Default comment field',
      'target_entity_type_id' => 'node',
    ])->save();
    $this->addDefaultCommentField('node', 'article', 'comment');
  }

  /**
   * Creates a new comment.
   *
   * @param int $nid
   *   The ID of the node being commented on.
   * @param array $settings
   *   (optional) An associative array of settings for the comment.
   *
   * @return \Drupal\comment\CommentInterface
   *   The created comment entity.
   */
  protected function createComment($nid, array $settings = []): CommentInterface {
    $settings += [
      'subject' => 'My comment title',
      'name' => $this->randomString(),
      'mail' => 'node@localhost',
      'entity_type' => 'node',
      'field_name' => 'comment',
      'entity_id' => $nid,
      'comment_type' => 'comment',
      'status' => 1,
    ];
    $comment = Comment::create($settings);
    $comment->save();

    return $comment;
  }

}
