<?php

/**
 * @file
 * Helper function for demo. Parses a string of words.
 */

require '../vendor/autoload.php';

use markfullmer\porter2\Porter2;

/**
 * Regular expression defining a word boundary for Porter Stemmer.
 *
 * A word boundary is anything not a letter or an apostrophe.
 */
define('WORD_BOUNDARY', "[^a-zA-Z']+");

/**
 * Stem a string of words using the Porter 2 (English) stemming algorithm.
 */
function porterstemmer_process($text) {
  // Split into words.
  $words = preg_split('/(' . WORD_BOUNDARY . '+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
  if (!count($words)) {
    return $text;
  }

  // Process each word, skipping delimiters.
  $isword = !preg_match('/' . WORD_BOUNDARY . '/', $words[0]);
  foreach ($words as $k => $word) {
    if ($isword) {
      $words[$k] = Porter2::stem($word);
    }
    $isword = !$isword;
  }

  return implode('', $words);
}
