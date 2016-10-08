<?php

namespace Drupal\typogrify;

/**
 * Return the unicode conversion maps.
 */
class UnicodeConversion {

  /**
   * Provides Unicode-mapping.
   *
   * @param string $type
   *   The map type we're looking for, one of 'ligature', 'punctuation',
   *   'arrow' 'nested' or 'all'.
   *
   * @return array
   *   Array of conversions, keyed by the original string.
   */
  public static function map($type = 'all') {
    $map = array(
      // See http://www.unicode.org/charts/PDF/UFB00.pdf .
      'ligature' => array(
        'ffi' => '&#xfb03;',
        'ffl' => '&#xfb04;',
        'ff'  => '&#xfb00;',
        'fi'  => '&#xfb01;',
        'fl'  => '&#xfb02;',
        'ij'  => '&#x0133;',
        'IJ'  => '&#x0132;',
        'st'  => '&#xfb06;',
        'ss'  => '&szlig;',
      ),
      // See http:#www.unicode.org/charts/PDF/U2000.pdf .
      'punctuation' => array(
        '...'   => '&#x2026;',
        '..'    => '&#x2025;',
        '. . .' => '&#x2026;',
        '---'   => '&mdash;',
        '--'    => '&ndash;',
      ),
      'fraction' => array(
        '1/4'   => '&frac14;',
        '1/2'   => '&frac12;',
        '3/4'   => '&frac34;',
        '1/3'   => '&#8531;',
        '2/3'   => '&#8532;',
        '1/5'   => '&#8533;',
        '2/5'   => '&#8534;',
        '3/5'   => '&#8535;',
        '4/5'   => '&#8536;',
        '1/6'   => '&#8537;',
        '5/6'   => '&#8538;',
        '1/8'   => '&#8539;',
        '3/8'   => '&#8540;',
        '5/8'   => '&#8541;',
        '7/8'   => '&#8542;',
      ),
      'quotes' => array(
        ',,' => '&bdquo;',
        "''" => '&rdquo;',
        '&lt;&lt;' => '&laquo;',
        '&gt;&gt;' => '&raquo;',
      ),
      // See http:#www.unicode.org/charts/PDF/U2190.pdf .
      'arrow' => array(
        '-&gt;&gt;' => '&#x21a0;',
        '&lt;&lt;-' => '&#x219e;',
        '-&gt;|'    => '&#x21e5;',
        '|&lt;-'    => '&#x21e4;',
        '&lt;-&gt;' => '&#x2194;',
        '-&gt;'     => '&#x2192;',
        '&lt;-'     => '&#x2190;',
        '&lt;=&gt;' => '&#x21d4;',
        '=&gt;'     => '&#x21d2;',
        '&lt;='     => '&#x21d0;',
      ),
    );

    if ($type == 'all') {
      return array_merge($map['ligature'], $map['arrow'], $map['punctuation'], $map['quotes'], $map['fraction']);
    }
    elseif ($type == 'nested') {
      return $map;
    }
    else {
      return $map[$type];
    }
  }

  /**
   * Perform character conversion.
   *
   * @param string $text
   *   Text to be parsed.
   * @param array $characters_to_convert
   *   Array of ASCII characters to convert.
   *
   * @return string
   *   The result of the conversion.
   */
  public static function convertCharacters($text, $characters_to_convert) {
    if (($characters_to_convert == NULL) || (count($characters_to_convert) < 1)) {
      // Do nothing.
      return $text;
    }

    // Get ascii to unicode mappings.
    $unicode_map = self::map();

    foreach ($characters_to_convert as $ascii_string) {
      $unicode_strings[] = $unicode_map[$ascii_string];
    }

    $tokens = SmartyPants::tokenizeHtml($text);
    $result = '';
    // Keep track of when we're inside <pre> or <code> tags.
    $in_pre = 0;
    foreach ($tokens as $cur_token) {
      if ($cur_token[0] == "tag") {
        // Don't mess with text inside tags, <pre> blocks, or <code> blocks.
        $result .= $cur_token[1];
        // Get the tags to skip regex from SmartyPants.
        if (preg_match(SmartyPants::SMARTYPANTS_TAGS_TO_SKIP, $cur_token[1], $matches)) {
          $in_pre = isset($matches[1]) && $matches[1] == '/' ? 0 : 1;
        }
      }
      else {
        $t = $cur_token[1];
        if ($in_pre == 0) {
          $t = SmartyPants::processEscapes($t);
          $t = str_replace($characters_to_convert, $unicode_strings, $t);
        }
        $result .= $t;
      }
    }
    return $result;
  }

}
