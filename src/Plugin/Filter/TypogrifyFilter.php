<?php

namespace Drupal\typogrify\Plugin\Filter;

use Drupal\typogrify;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Url;

/**
 * Provides a filter to restrict images to site.
 *
 * @Filter(
 *   id = "TypogrifyFilter",
 *   title = @Translation("Typogrify"),
 *   description = @Translation("Adds typographic refinements"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "smartypants_enabled" = 1,
 *     "smartypants_hyphens" = 3,
 *     "space_hyphens" = 0,
 *     "wrap_ampersand" = 1,
 *     "widont_enabled"= 1,
 *     "space_to_nbsp" = 1,
 *     "hyphenate_shy" = 0,
 *     "wrap_abbr" = 0,
 *     "wrap_caps" = 1,
 *     "wrap_initial_quotes" = 1,
 *     "wrap_numbers" = 0,
 *     "ligatures" = "a:0:{}",
 *     "arrows" = "a:0:{}",
 *     "fractions" = "a:0:{}",
 *     "quotes" = "a:0:{}",
 *   },
 *   weight = 10
 * )
 */
class TypogrifyFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->settings;

    // @fixme Use the auto loader for this business.
    // @fixme Also these should use composer to be included.
    module_load_include('class.php', 'typogrify');
    module_load_include('php', 'typogrify', 'unicode-conversion');
    module_load_include('php', 'typogrify', 'smartypants');

    $form['help'] = array(
      '#type' => 'markup',
      '#value' => '<p>' . t('Enable the following typographic refinements:') . '</p>',
    );

    // Smartypants settings.
    $form['smartypants_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use typographers quotation marks and dashes (!smartylink)', array(
        '!smartylink' => \Drupal::l('SmartyPants', \Drupal\Core\Url::fromUri('http://daringfireball.net/projects/smartypants/')),
      )),
      '#default_value' => $settings['smartypants_enabled'],
    );

    // Smartypants hyphenation settings.
    // Uses the same values as the parse attributes in the SmartyPants
    // function (@see SmartyPants in smartypants.php)
    $form['smartypants_hyphens'] = array(
      '#type' => 'select',
      '#title' => t('Hyphenation settings for SmartyPants'),
      '#default_value' => $settings['smartypants_hyphens'],
      '#options' => array(
        1 => t('“--” for em-dashes; no en-dash support'),
        3 => t('“--” for em-dashes; “---” for en-dashes'),
        2 => t('“---” for em-dashes; “--” for en-dashes'),
      ),
    );

    // Replace space_hyphens with em-dash.
    $form['space_hyphens'] = array(
      '#type' => 'checkbox',
      '#title' => t('Replace stand-alone dashes (normal dashes between whitespace) em-dashes.'),
      '#description' => t('" - " will turn into " — ".'),
      '#default_value' => $settings['space_hyphens'],
    );

    // Remove widows settings.
    $form['widont_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove widows'),
      '#default_value' => $settings['widont_enabled'],
    );

    // Remove widows settings.
    $form['hyphenate_shy'] = array(
      '#type' => 'checkbox',
      '#title' => t('Replace <code>=</code> with <code>&amp;shy;</code>'),
      '#description' => t('Words may be broken at the hyphenation points marked by “=”.'),
      '#default_value' => $settings['hyphenate_shy'],
    );

    // Replace normal spaces with non-breaking spaces before "double punctuation
    // marks". This is especially useful in french.
    $form['space_to_nbsp'] = array(
      '#type' => 'checkbox',
      '#title' => t('Replace normal spaces with non-breaking spaces before "double punctuation marks" !marks.',
        array('!marks' => '(<code>!?:;</code>)')),
      '#description' => t('This is especially useful for french.'),
      '#default_value' => $settings['space_to_nbsp'],
    );

    // Wrap caps settings.
    $form['wrap_caps'] = array(
      '#type' => 'checkbox',
      '#title' => t('Wrap caps'),
      '#default_value' => $settings['wrap_caps'],
    );

    // Wrap ampersand settings.
    $form['wrap_ampersand'] = array(
      '#type' => 'checkbox',
      '#title' => t('Wrap ampersands'),
      '#default_value' => $settings['wrap_ampersand'],
    );

    $form['wrap_abbr'] = array(
      '#type' => 'select',
      '#title' => t('Thin space in abbreviations'),
      '#description' => t('Wraps abbreviations with !span and inserts space after the dots.', array('!span' => '<code>&lt;span class="abbr"&gt;…&lt;/span&gt;</code>')),
      '#default_value' => $settings['wrap_abbr'],
      '#options' => array(
        0 => t('Do nothing'),
        4 => t('Insert no space'),
        1 => t('“U+202F“ Narrow no-break space'),
        2 => t('“U+2009“ Thin space'),
        3 => t('span with margin-left: 0.167em'),
      ),
    );

    $form['wrap_numbers'] = array(
      '#type' => 'select',
      '#title' => t('Digit grouping in numbers'),
      '#description' => t('Wraps numbers with !span and inserts thin space for digit grouping.', array('!span' => '<code>&lt;span class="number"&gt;…&lt;/span&gt;</code>')),
      '#default_value' => $settings['wrap_numbers'],
      '#options' => array(
        0 => t('Do nothing'),
        1 => t('“U+202F“ Narrow no-break space'),
        2 => t('“U+2009“ Thin space'),
        3 => t('span with margin-left: 0.167em'),
        4 => t('just wrap numbers'),
      ),
    );

    // Wrap initial quotes settings.
    $form['wrap_initial_quotes'] = array(
      '#type' => 'checkbox',
      '#title' => t('Wrap quotation marks'),
      '#default_value' => $settings['wrap_initial_quotes'],
    );

    // Ligature conversion settings.
    $ligature_options = array();
    foreach (unicode_conversion_map('ligature') as $ascii => $unicode) {
      $ligature_options[$ascii] = t('Convert <code>@ascii</code> to !unicode', array(
        '@ascii' => $ascii,
        '!unicode' => $unicode,
      ));
    }

    $form['ligatures'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Ligatures'),
      '#options' => $ligature_options,
      '#default_value' => $settings['ligatures'],
    );

    // Arrow conversion settings.
    $arrow_options = array();
    foreach (unicode_conversion_map('arrow') as $ascii => $unicode) {
      $arrow_options[$ascii] = t('Convert <code>@ascii</code> to !unicode', array(
        '@ascii' => $this->unquote($ascii),
        '!unicode' => $unicode,
      ));

    }

    $form['arrows'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Arrows'),
      '#options' => $arrow_options,
      '#default_value' => $settings['arrows'],
    );

    // Fraction conversion settings.
    $fraction_options = array();
    foreach (unicode_conversion_map('fraction') as $ascii => $unicode) {
      $fraction_options[$ascii] = t('Convert <code>@ascii</code> to !unicode', array(
        '@ascii' => $ascii,
        '!unicode' => $unicode,
      ));

    }

    $form['fractions'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Fractions'),
      '#options' => $fraction_options,
      '#default_value' => $settings['fractions'],
    );

    // Quotes conversion settings.
    $quotes_options = array();
    foreach (unicode_conversion_map('quotes') as $quotes => $unicode) {
      $quotes_options[$quotes] = t('Convert <code>@ascii</code> to !unicode', array(
        '@ascii' => $this->unquote($quotes),
        '!unicode' => $unicode,
      ));
    }

    $form['quotes'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Quotes'),
      '#options' => $quotes_options,
      '#default_value' => $settings['quotes'],
    );

    // Version Information Settings.
    $version_strings = array();
    $version_strings[] = t('SmartyPants PHP version: !version', array(
      '!version' => Link(SMARTYPANTS_PHP_VERSION, Url::fromUri('http://www.michelf.com/projects/php-smartypants/')),
    ));
    $version_strings[] = t('PHP Typogrify Version: !version', array(
      '!version' => Link(PHP_TYPOGRIFY_VERSION, Url::fromUri('http://blog.hamstu.com/')),
    ));

    $form['info']['typogrify_status'] = [
      '#theme' => 'item_list',
      '#items' => ['items' => $version_strings],
      '#title' => t('Versions'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $characters_to_convert = array();
    $ctx = array();

    if ($langcode == 'und') {
      $language = \Drupal::languageManager()->getCurrentLanguage();

      // @fixme, check language business for d8
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
      if (isset($settings['ligatures'][$ascii]) && $settings['ligatures'][$ascii]) {
        $characters_to_convert[] = $ascii;
      }
    }

    // Wrap caps.
    if ($settings['wrap_caps']) {
      $text = Typogrify::caps($text);
    }

    // Build a list of arrows to convert.
    foreach (unicode_conversion_map('arrow') as $ascii => $unicode) {
      $htmle = $this->unquote($ascii);
      if ((isset($settings['arrows'][$ascii]) && $settings['arrows'][$ascii]) ||
        (isset($settings['arrows'][$htmle]) && $settings['arrows'][$htmle])) {
        $characters_to_convert[] = $ascii;
      }
    }

    // Build a list of fractions to convert.
    foreach (unicode_conversion_map('fraction') as $ascii => $unicode) {
      if (isset($settings['fractions'][$ascii]) && $settings['fractions'][$ascii]) {
        $characters_to_convert[] = $ascii;
      }
    }

    // Build a list of quotation marks to convert.
    foreach (unicode_conversion_map('quotes') as $ascii => $unicode) {
      if (isset($settings['quotes'][$ascii]) && $settings['quotes'][$ascii]) {
        $characters_to_convert[] = $ascii;
      }
    }

    // Convert ligatures and arrows.
    if (count($characters_to_convert) > 0) {
      $text = convert_characters($text, $characters_to_convert);
    }

    // Wrap ampersands.
    if ($settings['wrap_ampersand']) {
      $text = SmartAmpersand($text);
    }

    // Smartypants formatting.
    if ($settings['smartypants_enabled']) {
      $text = SmartyPants($text, $settings['smartypants_hyphens'], $ctx);
    }

    // Wrap abbreviations.
    if ($settings['wrap_abbr'] > 0) {
      $text = typogrify_smart_abbreviation($text, $settings['wrap_abbr']);
    }

    // Wrap huge numbers.
    if ($settings['wrap_numbers'] > 0) {
      $text = typogrify_smart_numbers($text, $settings['wrap_numbers']);
    }

    // Wrap initial quotes.
    if ($settings['wrap_initial_quotes']) {
      $text = Typogrify::initial_quotes($text);
    }

    // Wrap initial quotes.
    if ($settings['hyphenate_shy']) {
      $text = typogrify_hyphenate($text);
    }

    // Remove widows.
    if ($settings['widont_enabled']) {
      $text = Typogrify::widont($text);
    }

    // Replace normal spaces with non-breaking spaces before "double punctuation
    // marks". This is especially useful in french.
    if (isset($settings['space_to_nbsp']) && $settings['space_to_nbsp']) {
      $text = typogrify_space_to_nbsp($text);
    }

    // Replace normal whitespace '-' whitespace with em-dash.
    if (isset($settings['space_hyphens']) && $settings['space_hyphens']) {
      $text = typogrify_space_hyphens($text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $settings = $this->settings;

    if ($long) {
      module_load_include('php', 'typogrify', 'unicode-conversion');

      $output = t('Typogrify.module brings the typographic refinements of Typogrify to Drupal.');
      $output .= '<ul>';
      if ($settings['wrap_ampersand']) {
        $output .= '<li>' . t('Wraps ampersands (the “&amp;” character) with !span.', array('!span' => '<code>&lt;span class="amp"&gt;&amp;&lt;/span&gt;</code>')) . '</li>';
      }
      if ($settings['widont_enabled']) {
        $output .= '<li>' . t("Prevents single words from wrapping onto their own line using Shaun Inman's Widont technique.") . '</li>';
      }
      if ($settings['wrap_initial_quotes']) {
        $output .= '<li>' . t("Converts straight quotation marks to typographer's quotation marks, using SmartyPants.");
        $output .= '</li><li>' . t('Wraps initial quotation marks with !quote or !dquote.', array(
              '!quote' => '<code>&lt;span class="quo"&gt;&lt;/span&gt;</code>',
              '!dquote' => '<code>&lt;span class="dquo"&gt;&lt;/span&gt;</code>', )
          ) . '</li>';
      }
      $output .= t('<li>Converts multiple hyphens to en dashes and em dashes (according to your preferences), using SmartyPants.</li>');
      if ($settings['hyphenate_shy']) {
        $output .= '<li>' . t('Words may be broken at the hyphenation points marked by “=”.') . '</li>';
      }
      if ($settings['wrap_abbr']) {
        $output .= '<li>' . t('Wraps abbreviations as “e.g.” to !span and adds a thin space (1/6 em) after the dots.</li>', array('!span' => '<code>&lt;span class="abbr"&gt;e.g.&lt;/span&gt;</code>')) . '</li>';
      }
      if ($settings['wrap_numbers']) {
        $output .= '<li>' . t('Wraps large numbers &gt; 1&thinsp;000 with !span and inserts thin space for digit grouping.', array('!span' => '<code>&lt;span class="number"&gt;…&lt;/span&gt;</code>')) . '</li>';
      }
      if ($settings['wrap_caps']) {
        $output .= '<li>' . t('Wraps multiple capital letters with !span.', array('!span' => '<code>&lt;span class="caps"&gt;CAPS&lt;/span&gt;</code>')) . '</li>';
      }
      $output .= '<li>' . t('Adds a css style sheet that uses the &lt;span&gt; tags to substitute a showy ampersand in headlines, switch caps to small caps, and hang initial quotation marks.') . '</li>';
      // Build a list of quotation marks to convert.
      foreach (unicode_conversion_map('quotes') as $ascii => $unicode) {
        if ($settings['quotes'][$ascii]) {
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

  /**
   * Helper function to unquote a string.
   *
   * Unquotes a string.
   *
   * @param string|array $attribute_values
   *   String to be unquoted.
   *
   * @return string|string
   */
  private function unquote($_) {
    $_ = str_replace(
      array('&lt;', '&gt;'),
      array('<',    '>'),
      $_);

    return $_;
  }

}
