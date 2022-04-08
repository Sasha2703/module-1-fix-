<?php

namespace Drupal\sasha_cat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;

/**
 * An example controller.
 */
class CatAdminController extends ControllerBase {

  /**
   * Display the markup.
   */
  public function content() {
    $form = \Drupal::formBuilder()->getForm('Drupal\sasha_cat\Form\AdminForm');
    $element = 'Hello! You can add here a photo of your cat.';
    return [
      '#theme' => 'admin-cats',
      '#form' => $form,
      '#markup' => $element,
      '#list' => $this->catTable(),
    ];
  }

  /**
   * Get data from database.
   *
   * @return array
   *   Return markup array.
   */
  public function catTable(): array {
    $query = \Drupal::database();
    $result = $query->select('sasha_cat', 'sasha_cattb')
      ->fields('sasha_cattb', ['name', 'email', 'image', 'date', 'id'])
      ->orderBy('date', 'DESC')
      ->execute()->fetchAll();
    $data = [];
    foreach ($result as $cat) {
      $file = File::load($cat->image);
      $uri = $file->getFileUri();
      $photoCats = [
        '#theme' => 'image_style',
        '#style_name' => 'wide',
        '#uri' => $uri,
        '#alt' => 'Cat',
        '#title' => 'Cat',
        '#width' => 255,
      ];
      $data[] = [
        'name' => $cat->name,
        'email' => $cat->email,
        'image' => $photoCats,
        'date' => $cat->date,
        'id' => $cat->id,
      ];
    }
    return $data;
  }

}
