<?php

/**
 * @file
 * Token integration for the file_entity module.
 */
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\file_entity\Hook\FileEntityTokensHooks;
use Drupal\Core\Url;
use Drupal\file_entity\Entity\FileType;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Component\Utility\Html;

/**
 * Implements hook_token_info().
 */
#[LegacyHook]
function file_entity_token_info()
{
    return \Drupal::service(FileEntityTokensHooks::class)->tokenInfo();
}

/**
 * Implements hook_token_info_alter().
 */
#[LegacyHook]
function file_entity_token_info_alter(&$info) {
  \Drupal::service(FileEntityTokensHooks::class)->tokenInfoAlter($info);
}

/**
 * Implements hook_tokens().
 */
#[LegacyHook]
function file_entity_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata)
{
    return \Drupal::service(FileEntityTokensHooks::class)->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}
