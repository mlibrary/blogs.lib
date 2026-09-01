<?php

/**
 * @file
 * File hooks implemented by the File entity module.
 */
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\file_entity\Hook\FileEntityFileHooks;
use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileType;

/**
 * Implements hook_file_type().
 */
#[LegacyHook]
function file_entity_file_type(FileInterface $file)
{
    return \Drupal::service(FileEntityFileHooks::class)->fileType($file);
}

/**
 * Implements hook_file_metadata_info().
 */
#[LegacyHook]
function file_entity_file_metadata_info()
{
    return \Drupal::service(FileEntityFileHooks::class)->fileMetadataInfo();
}
