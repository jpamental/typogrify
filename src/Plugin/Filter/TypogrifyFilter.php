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
  //function _typogrify_filter_tips($filter, $format, $long) {
  public function tips($long = FALSE) {
    if ($long) {
      module_load_include('php', 'typogrify', 'unicode-conversion');

      $output = t('Typogrify.module brings the typographic refinements of Typogrify to Drupal.');
      $output .= '<ul>';
      if ($filter->settings['wrap_ampersand']) {
        $output .= '<li>' . t('Wraps ampersands (the “&amp;” character) with !span.', array('!span' => '<code>&lt;span class="amp"&gt;&amp;&lt;/span&gt;</code>')) . '</li>';
      }
      if ($filter->settings['widont_enabled']) {
        $output .= '<li>' . t("Prevents single words from wrapping onto their own line using Shaun Inman's Widont technique.") . '</li>';
      }
      if ($filter->settings['wrap_initial_quotes']) {
        $output .= '<li>' . t("Converts straight quotation marks to typographer's quotation marks, using SmartyPants.");
        $output .= '</li><li>' . t('Wraps initial quotation marks with !quote or !dquote.', array(
              '!quote' => '<code>&lt;span class="quo"&gt;&lt;/span&gt;</code>',
              '!dquote' => '<code>&lt;span class="dquo"&gt;&lt;/span&gt;</code>')
          ) . '</li>';
      }
      $output .= t('<li>Converts multiple hyphens to en dashes and em dashes (according to your preferences), using SmartyPants.</li>');
      if ($filter->settings['hyphenate_shy']) {
        $output .= '<li>' . t('Words may be broken at the hyphenation points marked by “=”.') . '</li>';
      }
      if ($filter->settings['wrap_abbr']) {
        $output .= '<li>' . t('Wraps abbreviations as “e.g.” to !span and adds a thin space (1/6 em) after the dots.</li>', array('!span' => '<code>&lt;span class="abbr"&gt;e.g.&lt;/span&gt;</code>')) . '</li>';
      }
      if ($filter->settings['wrap_numbers']) {
        $output .= '<li>' . t('Wraps large numbers &gt; 1&thinsp;000 with !span and inserts thin space for digit grouping.', array('!span' => '<code>&lt;span class="number"&gt;…&lt;/span&gt;</code>')) . '</li>';
      }
      if ($filter->settings['wrap_caps']) {
        $output .= '<li>' . t('Wraps multiple capital letters with !span.', array('!span' => '<code>&lt;span class="caps"&gt;CAPS&lt;/span&gt;</code>')) . '</li>';
      }
      $output .= '<li>' . t('Adds a css style sheet that uses the &lt;span&gt; tags to substitute a showy ampersand in headlines, switch caps to small caps, and hang initial quotation marks.') . '</li>';
      // Build a list of quotation marks to convert.
      foreach (unicode_conversion_map('quotes') as $ascii => $unicode) {
        if ($filter->settings['quotes'][$ascii]) {
          $output .= '<li>' . t('Converts <code>!ascii</code> to !unicode', array(
              '!ascii' => $ascii,
              '!unicode' => $unicode,
            )) . "</li>\n";
        }
      }
      $output .= '</ul>';
    }
    else {
      $output = t('Typographic refinements will be added.');
    }

    return $output;
  }

}