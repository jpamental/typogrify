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
  public function process($text, $langcode) {
    return new FilterProcessResult(_typogrify_process($text));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Typogrify');
  }

}