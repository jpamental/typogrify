<?php

namespace Drupal\typogrify;

class TypogrifySmartyPantsTestCase extends DrupalWebTestCase {
  /**
   * Implements getInfo().
   */
  public function getInfo() {
    return array(
      'name' => t('Typogrify with SmartyPants'),
      'description' => t('Test the application of the full package of Typogrify and SmartyPants.'),
      'group' => t('Typogrify'),
    );
  }

  /**
   * Implement setUp().
   */
  public function setUp() {
    parent::setUp('typogrify');
    global $filter;
    $filter = (object) array(
      'settings' => array(
      'smartypants_enabled' => 1,
      'smartypants_hyphens' => 2,
      'wrap_ampersand' => 1,
      'widont_enabled' => 1,
      'wrap_abbr' => 0,
      'wrap_caps' => 1,
      'wrap_initial_quotes' => 1,
      'hyphenate_shy' => 0,
      'wrap_numbers' => 0,
      'ligatures' => array(),
      'arrows' => array(),
      'quotes' => array(),
    ),
  );

  }

  /**
   * Original example compatibility-test.
   */
  public function testOriginalTypogrifyExample() {

    $before = <<<HTML
<h2>"Jayhawks" & KU fans act extremely obnoxiously</h2>
<p>By J.D. Salinger, Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. "Excepteur sint occaecat 'cupidatat' non proident" sunt RFID22 in.... </p>
HTML;
    $after = <<<HTML
<h2>“Jayhawks” <span class="amp">&amp;</span> <span class="caps">KU</span> fans act extremely&nbsp;obnoxiously</h2>
<p>By <span class="caps">J.D.</span> Salinger, Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. “Excepteur sint occaecat ‘cupidatat’ non proident” sunt <span class="caps">RFID22</span>&nbsp;in&#8230;. </p>
HTML;

    global $filter;
    $result = _typogrify_process($before, $filter, NULL, 'en', NULL, NULL);
    $this->assertEqual($result, $after, t('Original Typogrify example.'));

  }
}
