<?php

namespace Drupal\creative\Service;

/**
 * Provides regex function(s)
 */
class Pattern {

  const PATTERN = '/(wolf|woof|moon)/i';

  /**
   * Returns an array of text that match
   * the pattern above.
   *
   * @param $input
   *
   * @return array
   */
  public function doesMatch($input): array {
    // $matches is passed by reference
    preg_match_all(self::PATTERN, $input, $matches);
    return $matches;
  }

}
