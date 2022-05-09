<?php

namespace Drupal\sasha_cat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the sasha_cat module.
 *
 * @throw Drupal\Core\Controller\ControllerBase
 */
class SashaCatController extends ControllerBase {

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new CatForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   */
  public function __construct(Connection $connection, EntityStorageInterface $file_storage) {
    $this->connection = $connection;
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * Builds the response.
   */
  public function build() {
    $cat_form = $this->formBuilder()->getForm('Drupal\sasha_cat\Form\CatForm');
    return [
      '#theme' => 'sasha_cat_page',
      '#cat_form' => $cat_form,
      '#cat_table' => $this->buildCatTable(),
    ];
  }

  /**
   * Builds cats table.
   *
   * @return array
   *   Return markup array.
   */
  public function buildCatTable() {
    $cats = $this->connection
      ->select('sasha_cat', 'sc')
      ->fields('sc', ['id', 'cat_name', 'email', 'cat_image', 'created'])
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    // Build image.
    foreach ($cats as &$cat) {
      $cat_image_uri = $this->fileStorage
        ->load($cat['cat_image'])
        ->getFileUri();
      $cat['cat_image'] = [
        '#theme' => 'image_style',
        '#style_name' => 'wide',
        '#uri' => $cat_image_uri,
        '#alt' => 'cat',
        '#title' => 'cat',
        '#width' => 255,
      ];
    }
    return [
      '#theme' => 'sasha_cat_table',
      '#cats' => $cats,
      '#cache' => [
        'tags' => ['sasha_cat_table'],
      ],
    ];
  }

}
