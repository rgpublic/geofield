<?php

/**
 * @file
 * Contains \Drupal\geofield\Type\GeofieldItem.
 */

namespace Drupal\geofield\Plugin\field\field_type;

use Drupal;
use geoPHP;
use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;
use Drupal\field\Plugin\Core\Entity\Field;
use Drupal\geofield\Plugin\Type\GeofieldBackendPluginManager;

/**
 * Plugin implementation of the 'geofield' field type.
 *
 * @FieldType(
 *   id = "geofield",
 *   module = "geofield",
 *   label = @Translation("Geofield"),
 *   description = @Translation("This field stores geospatial information."),
 *   default_widget = "geofield_widget_default",
 *   default_formatter = "geofield_formatter_default",
 *   settings = {
 *     "backend" = "geofield_backend_default"
 *   }
 * )
 */
class GeofieldItem extends ConfigFieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @see GeofieldItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public static function schema(Field $field) {
    $backendManager = \Drupal::service('plugin.manager.geofield_backend');

    // @TODO: Check to see if backend setting is a valid plugin
    if (isset($field['settings']['backend'])) {
      $backendPlugin = $backendManager->createInstance('geofield_backend_default');
    }
    else {
      $backendPlugin = $backendManager->createInstance('geofield_backend_default');
    }

    return array(
      'columns' => array(
        'value' => $backendPlugin->schema(),
       /*'value' => array(
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
        ),*/
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
   * Implements ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    if (!isset(self::$propertyDefinitions)) {
      self::$propertyDefinitions['value'] = array(
        'type' => 'string',
        'label' => t('Geometry'),
      );
      self::$propertyDefinitions['geo_type'] = array(
        'type' => 'string',
        'label' => t('Geometry Type'),
      );
      self::$propertyDefinitions['lat'] = array(
        'type' => 'float',
        'label' => t('Latitude'),
      );
      self::$propertyDefinitions['lon'] = array(
        'type' => 'float',
        'label' => t('Longitude'),
      );
      self::$propertyDefinitions['left'] = array(
        'type' => 'float',
        'label' => t('Left Bounding'),
      );
      self::$propertyDefinitions['top'] = array(
        'type' => 'float',
        'label' => t('Top Bounding'),
      );
      self::$propertyDefinitions['right'] = array(
        'type' => 'float',
        'label' => t('Right Bounding'),
      );
      self::$propertyDefinitions['bottom'] = array(
        'type' => 'float',
        'label' => t('Bottom Bounding'),
      );
      self::$propertyDefinitions['geohash'] = array(
        'type' => 'string',
        'label' => t('Geohash'),
      );
    }
    return self::$propertyDefinitions;
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
    Drupal::service('geophp.geophp');

    $geom = geoPHP::load($this->value);

    if (!empty($geom)) {
      $centroid = $geom->getCentroid();
      $bounding = $geom->getBBox();

      $this->geo_type = $geom->geometryType();
      $this->lat = $centroid->getX();
      $this->lon = $centroid->getY();
      $this->left = $bounding['minx'];
      $this->top = $bounding['maxy'];
      $this->right = $bounding['maxx'];
      $this->bottom = $bounding['miny'];
      $this->geohash = $geom->out('geohash');
    }
  }
}
