<?php

namespace Drupal\geofield\Plugin\GeofieldProximity;

use Drupal\views\Plugin\views\ViewsHandlerInterface;

/**
 * Default backend for Geofield.
 *
 * @GeofieldProximity(
 *   id = "geofield_manual_filter",
 *   admin_label = @Translation("Manual Proximity Filter")
 * )
 */
class ProximityManual extends GeofieldProximityBase {
  public function getSourceValue(ViewsHandlerInterface $views_plugin) {
    return FALSE;
  }

}