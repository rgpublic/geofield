<?php

namespace Drupal\geofield\Plugin\GeofieldProximity;

use Drupal\views\Plugin\views\ViewsHandlerInterface;

/**
 * Default backend for Geofield.
 *
 * @GeofieldProximity(
 *   id = "geofield_contextual_proximity_filter",
 *   admin_label = @Translation("Contextual Proximity Filter")
 * )
 */
class ContextualProximityFilter extends GeofieldProximityBase {
  public function getSourceValue(ViewsHandlerInterface $views_plugin) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $views_plugin->view;
    $contextualFilter = $view->display_handler->getHandler('argument', 'field_geofield_distance');
    if (isset($view->argument['field_geofield_distance'])) {
      $argument = $view->argument['field_geofield_distance'];
      return $argument->value;
    }
    return FALSE;
  }

}