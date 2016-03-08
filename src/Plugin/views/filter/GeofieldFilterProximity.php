<?php

/**
 * @file
 * Definition of Drupal\geofield\Plugin\views\filter\GeofieldProximity.
 */

namespace Drupal\geofield\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Plugin\GeofieldProximityManagerTrait;
use Drupal\views\Plugin\views\filter\NumericFilter;
/**
 * Field handler to filter Geofields by proximity.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("geofield_filter_proximity")
 */
class GeofieldFilterProximity extends NumericFilter {
  use GeofieldProximityManagerTrait;

  protected function defineOptions() {
    $options = parent::defineOptions();

    // Data sources and info needed.
    $options['source'] = array('default' => 'manual');
    $options['value'] = array(
      'default' => array(
        'distance' => 100,
        'distance2' => 200,
        'unit' => GEOFIELD_KILOMETERS,
        'origin' => array(),
      ),
    );

    foreach ($this->getProximityManager()->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
      $instance = $this->getProximityManager()->createInstance($plugin_id);
      $instance->defineOptions($options, $this);
    }

    return $options;
  }

  public function operators() {
    $operators = array(
      '<' => array(
        'title' => t('Is less than'),
        'method' => 'opSimple',
        'short' => t('<'),
        'values' => 1,
      ),
      '<=' => array(
        'title' => t('Is less than or equal to'),
        'method' => 'opSimple',
        'short' => t('<='),
        'values' => 1,
      ),
      '=' => array(
        'title' => t('Is equal to'),
        'method' => 'opSimple',
        'short' => t('='),
        'values' => 1,
      ),
      '!=' => array(
        'title' => t('Is not equal to'),
        'method' => 'opSimple',
        'short' => t('!='),
        'values' => 1,
      ),
      '>=' => array(
        'title' => t('Is greater than or equal to'),
        'method' => 'opSimple',
        'short' => t('>='),
        'values' => 1,
      ),
      '>' => array(
        'title' => t('Is greater than'),
        'method' => 'opSimple',
        'short' => t('>'),
        'values' => 1,
      ),
      'between' => array(
        'title' => t('Is between'),
        'method' => 'opBetween',
        'short' => t('between'),
        'values' => 2,
      ),
      'not between' => array(
        'title' => t('Is not between'),
        'method' => 'opBetween',
        'short' => t('not between'),
        'values' => 2,
      ),
    );

    return $operators;
  }

  public function query() {
    $this->ensureMyTable();
    $proximityPlugin = $this->getProximityManager()->createInstance($this->options['source']);
    $options = $proximityPlugin->getSourceValue($this);

    if ($options) {
      $lat_alias = $this->realField . '_lat';
      $lon_alias = $this->realField . '_lon';

      $info = $this->operators();
      if (!empty($info[$this->operator]['method'])) {
        $haversine_options = array(
          'origin_latitude' => $options['latitude'],
          'origin_longitude' => $options['longitude'],
          'destination_latitude' => $this->tableAlias . '.' . $lat_alias,
          'destination_longitude' => $this->tableAlias . '.' . $lon_alias,
          'earth_radius' => $this->value['unit'],
        );
        $this->{$info[$this->operator]['method']}($haversine_options);
      }
    }
  }

  protected function opBetween($options) {
    $this->query->addWhereExpression($this->options['group'], geofield_haversine($options) . ' ' . strtoupper($this->operator) . ' ' . $this->value['distance'] . ' AND ' . $this->value['distance2']);
  }

  protected function opSimple($options) {
    $this->query->addWhereExpression($this->options['group'], geofield_haversine($options) . ' ' . $this->operator . ' ' . $this->value['distance']);
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['source'] = array(
      '#type' => 'select',
      '#title' => t('Source of Origin Point'),
      '#description' => t('How do you want to enter your origin point?'),
      '#options' => array(),
      '#attached' => array(
        'js' => array(
          drupal_get_path('module', 'geofield') . '/js/viewsProximityValue.js',
        ),
      ),
      '#default_value' => $this->options['source'],
    );

    $form['source_change'] = array(
      '#type' => 'submit',
      '#value' => 'Change Source Widget',
      '#submit' => array('::geofield_views_ui_change_proximity_widget'),
    );

    foreach ($this->proximityManager->getDefinitions() as $plugin_id => $plugin_id) {
      // Manually skip 'Exposed Filter', since it wouldn't make any sense in this context.
      if ($plugin_id != 'exposed_geofield_filter') {
        $form['source']['#options'][$plugin_id] = $plugin_id['admin_label'];
        /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
        $instance = $this->proximityManager->createInstance($plugin_id);
        $instance->buildOptionsForm($form, $form_state, $this);
      }
    }

    // Look for any top-level item with a #proximity_plugin_value_element set. If found, it doesn't
    // belong in this particular field.
    foreach ($form as $key =>$form_item) {
      if (isset($form_item['#proximity_plugin_value_element']) && $form_item['#proximity_plugin_value_element'] == TRUE) {
        unset($form[$key]);
      }
    }
  }

  function geofield_views_ui_change_proximity_widget($form, &$form_state) {
    $item = &$form_state['handler']->options;
    $changed = $item['source'] != $form_state['values']['options']['source'];
    $item['source'] = $form_state['values']['options']['source'];

    if ($changed) {
      if ($item['source'] == 'manual') {
        $item['value']['origin'] = array('lat' => '', 'lon' => '');
      }
      else {
        $item['value']['origin'] = '';
      }
    }

    $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

    views_ui_cache_set($form_state['view']);
    $form_state['rerender'] = TRUE;
    $form_state['rebuild'] = TRUE;
    $form_state['force_expose_options'] = TRUE;
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    /** @var \Drupal\geofield\Plugin\GeofieldProximityInterface $instance */
    $instance = $this->proximityManager->createInstance($form_state->getValue('options')['source']);
    $instance->validateOptionsForm($form, $form_state, $this);
  }

  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = array(
      '#type' => 'geofield_proximity',
      '#title' => t('Proximity Search'),
      '#default_value' => array(
        'distance' => $this->value['distance'],
        'unit' => $this->value['unit'],
        'origin' => (is_string($this->value['origin'])) ? trim($this->value['origin']) : $this->value['origin'],
      ),
      '#origin_options' => array(
        '#attributes' => array(
          'class' => array('geofield-proximity-origin'),
        ),
      ),
    );

    $proximityPlugin = geofield_proximity_load_plugin($this->options['source']);
    $proximityPlugin->value_form($form, $form_state, $this);

    if (in_array($this->operator, array('between', 'not between'))) {
      $form['value']['#geofield_range'] = TRUE;
      $form['value']['#default_value']['distance2'] = $this->value['distance2'];
    }
  }

  protected function valueValidate($form, FormStateInterface $form_state) {
    parent::valueValidate($form, $form_state);
    $proximityPlugin = geofield_proximity_load_plugin($form_state['values']['options']['source']);
    $proximityPlugin->value_validate($form, $form_state, $this);
  }

  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return t('exposed');
    }

    $options = $this->operator_options('short');
    $output = check_plain($options[$this->operator]);
    if (in_array($this->operator, $this->operator_values(2))) {
      $output .= ' ' . t('@min and @max', array('@min' => $this->value['distance'], '@max' => $this->value['distance2']));
    }
    elseif (in_array($this->operator, $this->operator_values(1))) {
      $output .= ' ' . check_plain($this->value['distance']);
    }
    return $output;
  }

  /**
   * Check to see if input from the exposed filters should change
   * the behavior of this filter.
   *   - @TODO: This could be more polished.
   */
  public function acceptExposedInput($input) {
    if (!(isset($this->options['expose']) && isset($this->options['expose']['identifier']))) {
      return FALSE;
    }

    $input_id = $this->options['expose']['identifier'];
    if (empty($input[$input_id]) || $input[$input_id]['distance'] === '' || $input[$input_id]['origin'] === '') {
      return FALSE;
    }

    $this->value['distance'] = $input[$input_id]['distance'];
    $this->value['unit'] = $input[$input_id]['unit'];
    $this->value['origin'] = $input[$input_id]['origin'];
    return TRUE;
  }
}
