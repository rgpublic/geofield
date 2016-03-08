<?php

/**
 * @file
 * Definition of Drupal\geofield\Plugin\views\sort\GeofieldProximity.
 */

namespace Drupal\geofield\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Plugin\GeofieldProximityManagerTrait;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Field handler to sort Geofields by proximity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsSort("geofield_proximity")
 */
class GeofieldProximity extends SortPluginBase {
  use GeofieldProximityManagerTrait;

  protected function defineOptions() {
    $options = parent::defineOptions();
    // Data sources and info needed.
    $options['source'] = array('default' => 'geofield_manual_filter');

    foreach ($this->getProximityManager()->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
      $instance = $this->getProximityManager()->createInstance($plugin_id);
      $instance->defineOptions($options, $this);
    }

    return $options;
  }

  function query() {
    $this->ensureMyTable();
    $lat_alias = $this->realField . '_lat';
    $lon_alias = $this->realField . '_lon';

    /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $proximityPlugin */
    $proximityPlugin = $this->getProximityManager()->createInstance($this->options['source']);
    $options = $proximityPlugin->getSourceValue($this);

    if ($options != FALSE) {
      $haversine_options = array(
        'origin_latitude' => $options['latitude'],
        'origin_longitude' => $options['longitude'],
        'destination_latitude' => $lat_alias,
        'destination_longitude' => $lon_alias,
        'earth_radius' => GEOFIELD_KILOMETERS,
      );
      $this->query->addOrderBy(NULL, geofield_haversine($haversine_options), $this->options['order'], $this->tableAlias . '_' . $this->field);
    }
  }

  function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['source'] = array(
      '#type' => 'select',
      '#title' => t('Source of Origin Point'),
      '#description' => t('How do you want to enter your origin point?'),
      '#options' => array(),
      '#default_value' => $this->options['source'],
    );

    foreach ($this->getProximityManager()->getDefinitions() as $plugin_id => $definition) {
      $form['source']['#options'][$plugin_id] = $definition['admin_label'];
      /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
      $instance = $this->proximityManager->createInstance($plugin_id);
      $instance->buildOptionsForm($form, $form_state, $this);
    }
  }

  function validateOptionsForm(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
    $instance = $this->proximityManager->createInstance($form_state->getValue('options')['source']);
    $instance->validateOptionsForm($form, $form_state, $this);
  }
}
