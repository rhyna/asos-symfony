<?php

namespace markfullmer\porter2;

use PHPUnit\Framework\TestCase;

/**
 * Test words are stemmed correctly.
 */
class Basic4Test extends TestCase {

  /**
   * Provides data.
   */
  public function basicDataProvider() {
    $words = retrieve_stem_words(15000, 5000);
    $tests = array();
    foreach ($words as $key => $values) {
      $tests[$key]['word'] = $values[0];
      $tests[$key]['expected'] = $values[1];
    }
    return $tests;
  }

  /**
   * Test assertions.
   *
   * @dataProvider basicDataProvider
   */
  public function testBasic4($word, $expected) {
    $stem = Porter2::stem($word);
    $this->assertEquals($expected, $stem);
  }

}
