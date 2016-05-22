<?php

/**
 * @file
 * Contains \Drupal\typogrify\Plugin\Filter\FilterHtmlImageSecure.
 */

namespace Drupal\typogrify\Plugin\Filter;

use Drupal\typogrify;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to restrict images to site.
 *
 * @Filter(
 *   id = "typogrify_filter",
 *   title = @Translation("Typogrify"),
 *   description = @Translation("Adds typographic refinements"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 9
 * )
 */
class TypogrifyFilter extends FilterBase {
  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    // Force restrictions to be calculated again.
    $this->restrictions = NULL;
  }

  /**
   * {@inheritdoc}
   */
  //private function _typogrify_process($text, $filter, $format, $langcode, $cache, $cache_id) {
  public function process($text, $langcode) {
    $characters_to_convert = array();
    $ctx = array();

    if ($langcode == 'und') {
      $language = \Drupal::languageManager()->getCurrentLanguage();
      $ctx['langcode'] = $language->language;
    }
    else {
      $ctx['langcode'] = $langcode;
    }
    // Load Helpers.
    module_load_include('class.php', 'typogrify');
    module_load_include('php', 'typogrify', 'unicode-conversion');
    module_load_include('php', 'typogrify', 'smartypants');

    // Build a list of ligatures to convert.
    foreach (unicode_conversion_map('ligature') as $ascii => $unicode) {
      if (isset($filter->settings['ligatures'][$ascii]) && $filter->settings['ligatures'][$ascii]) {
        $characters_to_convert[] = $ascii;
      }
    }

    // Wrap caps.
    if ($filter->settings['wrap_caps']) {
      $text = Typogrify::caps($text);
    }

    // Build a list of arrows to convert.
    foreach (unicode_conversion_map('arrow') as $ascii => $unicode) {
      $htmle = _typogrify_unquote($ascii);
      if ((isset($filter->settings['arrows'][$ascii]) && $filter->settings['arrows'][$ascii]) ||
        (isset($filter->settings['arrows'][$htmle]) && $filter->settings['arrows'][$htmle])) {
        $characters_to_convert[] = $ascii;
      }
    }

    // Build a list of fractions to convert.
    foreach (unicode_conversion_map('fraction') as $ascii => $unicode) {
      if (isset($filter->settings['fractions'][$ascii]) && $filter->settings['fractions'][$ascii]) {
        $characters_to_convert[] = $ascii;
      }
    }

    // Build a list of quotation marks to convert.
    foreach (unicode_conversion_map('quotes') as $ascii => $unicode) {
      if (isset($filter->settings['quotes'][$ascii]) && $filter->settings['quotes'][$ascii]) {
        $characters_to_convert[] = $ascii;
      }
    }

    // Convert ligatures and arrows.
    if (count($characters_to_convert) > 0) {
      $text = convert_characters($text, $characters_to_convert);
    }

    // Wrap ampersands.
    if ($filter->settings['wrap_ampersand']) {
      $text = SmartAmpersand($text);
    }

    // Smartypants formatting.
    if ($filter->settings['smartypants_enabled']) {
      $text = SmartyPants($text, $filter->settings['smartypants_hyphens'], $ctx);
    }

    // Wrap abbreviations.
    if ($filter->settings['wrap_abbr'] > 0) {
      $text = typogrify_smart_abbreviation($text, $filter->settings['wrap_abbr']);
    }

    // Wrap huge numbers.
    if ($filter->settings['wrap_numbers'] > 0) {
      $text = typogrify_smart_numbers($text, $filter->settings['wrap_numbers']);
    }

    // Wrap initial quotes.
    if ($filter->settings['wrap_initial_quotes']) {
      $text = Typogrify::initial_quotes($text);
    }

    // Wrap initial quotes.
    if ($filter->settings['hyphenate_shy']) {
      $text = typogrify_hyphenate($text);
    }

    // Remove widows.
    if ($filter->settings['widont_enabled']) {
      $text = Typogrify::widont($text);
    }

    // Replace normal spaces with non-breaking spaces before "double punctuation
    // marks". This is especially useful in french.
    if (isset($filter->settings['space_to_nbsp']) && $filter->settings['space_to_nbsp']) {
      $text = typogrify_space_to_nbsp($text);
    }

    // Replace normal whitespace '-' whitespace with em-dash.
    if (isset($filter->settings['space_hyphens']) && $filter->settings['space_hyphens']) {
      $text = typogrify_space_hyphens($text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Typogrify');
  }

}