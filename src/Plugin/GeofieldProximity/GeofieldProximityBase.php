<?php

namespace Drupal\geofield\Plugin\GeofieldProximity;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Plugin\GeofieldProximityInterface;
use Drupal\views\Plugin\views\field\FieldHandlerInterface;
use Drupal\views\Plugin\views\ViewsHandlerInterface;

abstract class GeofieldProximityBase implements GeofieldProximityInterface {
  public function defineOptions(&$options, ViewsHandlerInterface $views_plugin) { }

  public function buildOptionsForm(&$form, FormStateInterface $form_state, ViewsHandlerInterface $views_plugin) {
    return [];
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state, ViewsHandlerInterface $views_plugin) { }

}