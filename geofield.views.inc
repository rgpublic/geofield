<?php

/**
 * Implements hook_field_views_data().
 */
function geofield_field_views_data(\Drupal\field\FieldStorageConfigInterface $field_storage) {
  // Make sure views.views.inc is loaded.
  module_load_include('inc', 'views', 'views.views');

  // Get the default data from the views module.
  $data = views_field_default_views_data($field_storage);

  // Loop through all of the results and set our overrides.
  foreach ($data as $table_name => $table_data) {
    $args = ['@field_name' => $field_storage->getName()];

    $target_entity_type = \Drupal::entityTypeManager()->getDefinition($field_storage->getTargetEntityTypeId());
    $field_coordinates_table_data = $data[$target_entity_type->getBaseTable() . '__' . $field_storage->getName()][$field_storage->getName()];

    // Add proximity handlers.
    $data[$table_name][$args['@field_name'] . '_distance'] = [
      'group' => 'Content',
      'title' => t('Proximity (@field_name)', $args),
      'title short' => $field_coordinates_table_data['title short'] . t(":proximity"),
      'help' => $field_coordinates_table_data['help'],
      'argument' => [
        'id' => 'geofield_argument_proximity',
        'table' => $table_name,
        'entity_type' => $field_storage->get('entity_type'),
        'field_name' => $args['@field_name'].'_distance',
        'real field' => $args['@field_name'],
        'label' => t('Distance to !field_name', $args),
        'empty field name' => '- No value -',
        'additional fields' => [
          $args['@field_name'].'_geo_type',
          $args['@field_name'].'_lat',
          $args['@field_name'].'_lon',
          $args['@field_name'].'_left',
          $args['@field_name'].'_top',
          $args['@field_name'].'_right',
          $args['@field_name'].'_bottom',
          $args['@field_name'].'_geohash',
        ],
      ],
      'filter' => [
        'id' => 'geofield_filter_proximity',
        'table' => $table_name,
        'entity_type' => $field_storage->get('entity_type'),
        'field_name' => $args['@field_name'].'_distance',
        'real field' => $args['@field_name'],
        'label' => t('Distance to !field_name', $args),
        'allow empty' => TRUE,
        'additional fields' => [
          $args['@field_name'].'_geo_type',
          $args['@field_name'].'_lat',
          $args['@field_name'].'_lon',
          $args['@field_name'].'_left',
          $args['@field_name'].'_top',
          $args['@field_name'].'_right',
          $args['@field_name'].'_bottom',
          $args['@field_name'].'_geohash',
        ],
      ],
      'field' => [
        'table' => $table_name,
        'id' => 'geofield_proximity',
        'field_name' => $args['@field_name'].'_distance',
        'entity_type' => $field_storage->get('entity_type'),
        'real field' => $args['@field_name'],
        'additional fields' => [
          $args['@field_name'].'_geo_type',
          $args['@field_name'].'_lat',
          $args['@field_name'].'_lon',
          $args['@field_name'].'_left',
          $args['@field_name'].'_top',
          $args['@field_name'].'_right',
          $args['@field_name'].'_bottom',
          $args['@field_name'].'_geohash',
        ],
        'element type' => 'div',
        'is revision' => (isset($table_data[$args['@field_name']]['field']['is revision']) && $table_data[$args['@field_name']]['field']['is revision']),
        'click sortable' => TRUE,
      ],
      'sort' => [
        'table' => $table_name,
        'id' => 'geofield_proximity',
        'field_name' => $args['@field_name'].'_distance',
        'entity_type' => $field_storage->get('entity_type'),
        'real field' => $args['@field_name'],
      ],
    ];
  }

  return $data;
}