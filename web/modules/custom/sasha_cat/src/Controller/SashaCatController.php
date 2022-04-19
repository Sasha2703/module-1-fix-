<?php

namespace Drupal\sasha_cat\Controller;

use Drupal\file\Entity\File;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the sasha_cat module.
 *
 * @throw Drupal\Core\Controller\ControllerBase
 */
class SashaCatController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function content(): array {
    $form['sasha_cat'] = \Drupal::formBuilder()
      ->getForm('Drupal\sasha_cat\Form\CatForm');
    return [
      '#theme' => 'cats',
      '#form' => $form,
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
      ->fields('sasha_cattb', ['id', 'name', 'email', 'image', 'date'])
      ->orderBy('id', 'DESC')
      ->execute()->fetchAll();
    $data = [];
    foreach ($result as $cat) {
      $file = File::load($cat->image);
      $uri = $file->getFileUri();
      $photoCats = [
        '#theme' => 'image_style',
        '#style_name' => 'wide',
        '#uri' => $uri,
        '#alt' => 'cat',
        '#title' => 'cat',
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
