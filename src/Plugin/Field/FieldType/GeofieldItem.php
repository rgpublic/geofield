<?php

/**
 * @file
 * Contains \Drupal\geofield\Field\FieldType\GeofieldItem.
 */

namespace Drupal\geofield\Plugin\Field\FieldType;

use geoPHP;
use Drupal\geofield\Plugin\Type\GeofieldBackendPluginManager;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'geofield' field type.
 *
 * @TODO: Add constraint, since hook_field_validate is no longer a thing. See link module for example
 *
 * @FieldType(
 *   id = "geofield",
 *   module = "geofield",
 *   label = @Translation("Geofield"),
 *   description = @Translation("This field stores geospatial information."),
 *   default_widget = "geofield_latlon",
 *   default_formatter = "geofield_formatter_default",
 *   instance_settings = {
 *     "backend" = "geofield_backend_default"
 *   }
 * )
 */
class GeofieldItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    $backendManager = \Drupal::service('plugin.manager.geofield_backend');
    $backendPlugin = NULL;

    if (isset($field->settings['backend']) && $backendManager->getDefinition($field->settings['backend']) != NULL) {
      $backendPlugin = $backendManager->createInstance($field['settings']['backend']);
    } 

    if ($backendPlugin === NULL) {
      $backendPlugin = $backendManager->createInstance('geofield_backend_default');
    }

    return array(
      'columns' => array(
        'value' => $backendPlugin->schema(),
        'geo_type' => array(
          'type' => 'varchar',
          'default' => '',
          'length' => 64,
        ),
        'lat' => array(
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ),
        'lon' => array(
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ),
        'left' => array(
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ),
        'top' => array(
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ),
        'right' => array(
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ),
        'bottom' => array(
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ),
        'geohash' => array(
          'type' => 'varchar',
          'length' => GEOFIELD_GEOHASH_LENGTH,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'lat' => array('lat'),
        'lon' => array('lon'),
        'top' => array('top'),
        'bottom' => array('bottom'),
        'left' => array('left'),
        'right' => array('right'),
        'geohash' => array('geohash'),
        'centroid' => array('lat','lon'),
        'bbox' => array('top','bottom','left','right'),
      ),
    );
  }

  /**
   * {@inheritDoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Geometry'));

    $properties['geo_type'] = DataDefinition::create('string')
      ->setLabel(t('Geometry Type'));

    $properties['lat'] = DataDefinition::create('float')
      ->setLabel(t('Centeroid Latitude'));

    $properties['lon'] = DataDefinition::create('float')
      ->setLabel(t('Centeroid Longitude'));

    $properties['left'] = DataDefinition::create('float')
      ->setLabel(t('Left Bounding'));

    $properties['top'] = DataDefinition::create('float')
      ->setLabel(t('Top Bounding'));

    $properties['right'] = DataDefinition::create('float')
      ->setLabel(t('Right Bounding'));

    $properties['bottom'] = DataDefinition::create('float')
      ->setLabel(t('Bottom Bounding'));

    $properties['geohash'] = DataDefinition::create('float')
      ->setLabel(t('Geohash'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    // @TODO: Backend plugins need to define requirement/settings methods,
    //   allow them to inject data here.
    $element = array();

    $backendManager = \Drupal::service('plugin.manager.geofield_backend');

    $backends = $backendManager->getDefinitions();
    $backendOptions = array();

    foreach ($backends as $id => $backend) {
      $backend_options[$id] = $backend['admin_label'];
    }

    $element['backend'] = array(
      '#type' => 'select',
      '#title' => t('Storage Backend'),
      '#default_value' => $this->getSetting('backend'),
      '#options' => $backend_options,
      '#description' => t("Select the Geospatial storage backend you would like to use to store geofield geometry data. If you don't know what this means, select 'Default'."),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return !isset($value) || $value === '';
  }

  /**
   * Overrides \Drupal\Core\TypedData\FieldItemBase::setValue().
   *
   * @param array|null $values
   *   An array of property values.
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values);
    $this->populateComputedValues();
  }

  /**
   * Populates computed variables.
   */
  protected function populateComputedValues() {
    \Drupal::service('geophp.geophp');

    $geom = geoPHP::load($this->value);

    if (!empty($geom)) {
      $centroid = $geom->getCentroid();
      $bounding = $geom->getBBox();

      $this->geo_type = $geom->geometryType();
      $this->lon = $centroid->getX();
      $this->lat = $centroid->getY();
      $this->left = $bounding['minx'];
      $this->top = $bounding['maxy'];
      $this->right = $bounding['maxx'];
      $this->bottom = $bounding['miny'];
      $this->geohash = $geom->out('geohash');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareCache() {
    
  }
}