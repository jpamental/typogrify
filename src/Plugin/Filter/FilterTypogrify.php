<?php

namespace Drupal\typogrify\Plugin\filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provide a filter for typographic refinements.
 *
 * @Filter(
 *    id = "filter_typogrify",
 *    title = @Translation("Typogrify Filter"),
 *    description = @Translation("Adds typographic refinements."),
 *    type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *    weight = 10,
 *  )
 */
class FilterTypogrify extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // TODO: apply filters
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['typogrify_setting'] = array(
      // TODO: build out settings form.
    );
    return $form;
  }
}
