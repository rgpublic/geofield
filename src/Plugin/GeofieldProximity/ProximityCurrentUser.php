<?php

namespace Drupal\geofield\Plugin\GeofieldProximity;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\ViewsHandlerInterface;

class ProximityCurrentUser extends GeofieldProximityBase {
  public function defineOptions(&$options, ViewsHandlerInterface $views_plugin) {
    $options['geofield_proximity_current_user_field'] = array(
      'default' => '',
    );
    $options['geofield_proximity_current_user_delta'] = array(
      'default' => 0,
    );
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state, ViewsHandlerInterface $views_plugin) {
    $geofields = _geofield_get_geofield_fields();
    $field_options = array();
    foreach ($geofields as $key => $field) {
      $field_options[$key] = $key;
    }

    $geofield_map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('geofield');
    foreach ($geofield_map['user'] as $user_geofields) {
      // @todo
    }

    $form['geofield_proximity_current_user_field'] = array(
      '#type' => 'select',
      '#title' => t('Source Field'),
      '#default_value' => $views_plugin->options['geofield_proximity_current_user_field'],
      '#options' => $field_options,
      '#dependency' => array(
        'edit-options-source' => array('current_user'),
      ),
    );
  }

  public function getSourceValue(ViewsHandlerInterface $views_plugin) {
    $user = \Drupal::currentUser();

    $geofield_name = $views_plugin->options['geofield_proximity_current_user_field'];
    $delta = $views_plugin->options['geofield_proximity_current_user_delta'];

    if (!empty($geofield_name)) {
      $field_data = $user->{$geofield_name}->value;

      if ($field_data != FALSE) {
        return array(
          'latitude' => $field_data[$delta]['lat'],
          'longitude' => $field_data[$delta]['lon'],
        );
      }
    }

    return FALSE;
  }

}