<?php

namespace Drupal\geofield\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\ViewsHandlerInterface;

interface GeofieldProximityInterface {

  /**
   * Information about options for all kinds of purposes will be held here.
   */
  public function defineOptions(&$options, ViewsHandlerInterface $views_plugin);

  public function buildOptionsForm(&$form, FormStateInterface $form_state, ViewsHandlerInterface $views_plugin);

  public function validateOptionsForm(&$form, FormStateInterface $form_state, ViewsHandlerInterface $views_plugin);

  /**
   * @param \Drupal\views\Plugin\views\ViewsHandlerInterface $views_plugin
   * @return mixed
   */
  public function getSourceValue(ViewsHandlerInterface $views_plugin);
}