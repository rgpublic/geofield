<?php

namespace Drupal\geofield\Plugin;

/**
 * Trait GeofieldProximityManagerTrait
 *
 * Views plugins do not support depengency injection, so this is a trait for
 * easily accessing the module's plugin managers.
 */
trait GeofieldProximityManagerTrait {
  /**
   * @var \Drupal\geofield\Plugin\GeofieldProximityManager
   */
  protected $proximityManager;

  /**
   * @return \Drupal\geofield\Plugin\GeofieldProximityManager|mixed
   */
  protected function getProximityManager() {
    if (empty($this->proximityManager)) {
      $this->proximityManager = \Drupal::service('plugin.manager.geofield_proximity');
    }

    return $this->proximityManager;
  }
}