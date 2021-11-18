<?php

/**
 * @file
 * Bootstrap requirements for testing.
 */

require 'src/Porter2.php';

/**
 * Get words to test.
 *
 * @param int $skipto
 *   Line to start on.
 * @param int $runto
 *   Number of lines to process.
 *
 * @return array
 *   An array of tests.
 */
function retrieve_stem_words($skipto = 0, $runto = 5000) {
  $file = __DIR__ . '/Datasets/full.ini';
  $handle = @fopen($file, "r");
  $tests = [];
  $skipped = 0;
  $ran = 0;
  while (!feof($handle) && $ran < $runto) {
    // Read a line of the file, and split into words.
    $line = trim(fgets($handle, 4096));
    $words = preg_split("/=/", $line, -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) < 2) {
      continue;
    }
    $skipped++;
    if ($skipped < $skipto) {
      continue;
    }
    $tests[] = $words;
    $ran++;
  }
  fclose($handle);
  return $tests;
}
