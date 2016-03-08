<?php

/**
 * @file
 * Definition of Drupal\geofield\Plugin\views\field\GeofieldProximity.
 */

namespace Drupal\geofield\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Plugin\GeofieldProximityManagerTrait;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler to render a Geofield proximity in Views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("geofield_proximity")
 */
class GeofieldProximity extends NumericField {
  use GeofieldProximityManagerTrait;

  /**
   * {@inheritdoc}.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Data sources and info needed.
    $options['source'] = array('default' => 'geofield_manual_filter');

    foreach ($this->getProximityManager()->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
      $instance = $this->getProximityManager()->createInstance($plugin_id);
      $instance->defineOptions($options, $this);
    }

    $options['radius_of_earth'] = array('default' => GEOFIELD_KILOMETERS);
    return $options;
  }

  /**
   * {@inheritdoc}.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['source'] = array(
      '#type' => 'select',
      '#title' => t('Source of Origin Point'),
      '#description' => t('How do you want to enter your origin point?'),
      '#options' => array(),
      '#default_value' => $this->options['source'],
    );

    foreach ($this->proximityManager->getDefinitions() as $plugin_id => $definition) {
      $form['source']['#options'][$plugin_id] = $definition['admin_label'];
      /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
      $instance = $this->proximityManager->createInstance($plugin_id);
      $instance->buildOptionsForm($form, $form_state, $this);
    }

    $form['radius_of_earth'] = array(
      '#type' => 'select',
      '#title' => t('Unit of Measure'),
      '#description' => '',
      '#options' => geofield_radius_options(),
      '#default_value' => $this->options['radius_of_earth'],
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
    $instance = $this->proximityManager->createInstance($form_state->getValue('options')['source']);
    $instance->validateOptionsForm($form, $form_state, $this);
  }

  /**
   * {@inheritdoc}.
   */
  public function getValue(ResultRow $values, $field = NULL) {
    if (isset($values->{$this->field_alias})) {
      return $values->{$this->field_alias};
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function query() {
    $this->ensureMyTable();

    $lat_alias = $this->realField . '_lat';
    $lon_alias = $this->realField . '_lon';

    /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
    $instance = $this->proximityManager->createInstance($this->options['source']);
    $options = $instance->getSourceValue($this);

    if ($options != FALSE) {
      $haversine_options = array(
        'origin_latitude' => $options['latitude'],
        'origin_longitude' => $options['longitude'],
        'destination_latitude' => $lat_alias,
        'destination_longitude' => $lon_alias,
        'earth_radius' => $this->options['radius_of_earth'],
      );

      $this->field_alias = $this->query->addField(NULL, geofield_haversine($haversine_options), $this->tableAlias . '_' . $this->field);
    }

    $this->addAdditionalFields();
  }

}