<?php

/**
 * @file
 * Demonstration file of the PHP Porter 2 English stemming algorithm.
 */

require 'process.inc';

// Some default text.
$text = 'consist
consisted
consistency
consistent
consistently
consisting
consists
consolation
consolations
consolatory
console
consoled
consoles';

if (isset($_POST['text'])) {
  $text = $_POST['text'];
}
echo '<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="css/normalize.css">
  <link rel="stylesheet" href="css/skeleton.css">
</head>
<body>';

echo '
<div class="container">
  <form action="//' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . '" method="POST">
    <div class="row">
      <div class="six columns">
        <label for="text">Words to be stemmed</label>
        <textarea class="u-full-width textbox" placeholder="Place words here..." name="text">' . $text . '</textarea>
      </div>
      <div class="six columns"><input type="submit" name="json" value="Stem words" />';
if (isset($_POST['text'])) {
  $start = microtime(TRUE);
  echo '<pre><code>' . porterstemmer_process($text) . '</code></pre>';
  echo (microtime(TRUE) - $start) . ' seconds to complete operation.';
}
echo '
      </div>
    </div>
  </form>
</div>
</body>
</html>';
