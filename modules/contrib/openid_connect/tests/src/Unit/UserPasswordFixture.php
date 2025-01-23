<?php

/**
 * @file
 * UserPasswordFixture.php
 */

use Drupal\Core\File\FileExists;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Override the user_password function if it does not exist.
 *
 * @return string
 *   Mocked password.
 */
function user_password(): string {
  return 'TestPassword123';
}

/**
 * Override the user_login_finalize function.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account.
 */
function user_login_finalize(UserInterface $account) {
}

/**
 * Mock of the file_save_data function.
 *
 * @param string $data
 *   The data to save.
 * @param string|null $destination
 *   The destination to save.
 * @param int $replace
 *   Whether to replace the file or not.
 *
 * @return \PHPUnit\Framework\MockObject\MockObject
 *   Return a mock object that mimics the file_save_data.
 */
function file_save_data(string $data, ?string $destination = NULL, int $replace = FileExists::Rename): MockObject {
  return $GLOBALS['oldFileMock'];
}
