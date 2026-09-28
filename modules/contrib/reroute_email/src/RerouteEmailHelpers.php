<?php

namespace Drupal\reroute_email;

/**
 * Trait that contains some common and useful methods to work with e-mails.
 */
trait RerouteEmailHelpers {

  /**
   * Extracts unique addresses from a string which may include display names.
   *
   * Items may be separated by any number and combination of:
   * spaces, commas, semicolons, or newlines.
   *
   * @param string|null $string
   *   A string to be split into an array.
   * @param bool $force_lowercase
   *   Makes all addresses in lowercase.
   *
   * @return array
   *   An array of unique addresses from a string.
   */
  protected function extractEmailAddresses(?string $string, $force_lowercase = TRUE): array {
    // Splits string (with display names) into array of emails.
    preg_match_all('/[^\s,;\n<]+@[^\s,;\n>]+/', $string ?? '', $addresses, PREG_PATTERN_ORDER);

    // Removes duplications.
    $addresses = array_unique($addresses[0]);

    // Makes everything lowercase (if requested).
    if ($force_lowercase) {
      $addresses = array_map('mb_strtolower', $addresses);
    }

    return $addresses;
  }

}
