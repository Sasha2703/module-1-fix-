<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Annotation @todo.
 */
class AdminForm extends ConfirmFormBase {

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sasha_cat_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('TODO.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('sasha_cat.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('cat_table')) {
      $form = parent::buildForm($form, $form_state);
      $form['actions']['submit']['#submit'][] = '::confirmForm';
      return $form;
    }

    $cats = $this->connection
      ->select('sasha_cat', 'sc')
      ->fields('sc', ['id', 'cat_name', 'email', 'cat_image', 'created'])
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
    $destination = $this->getRouteMatch()->getRouteName();
    foreach ($cats as &$cat) {
      // Build image.
      $cat_image_uri = $this->fileStorage
        ->load($cat['cat_image'])
        ->getFileUri();
      $cat['cat_image'] = [
        'data' => [
          '#theme' => 'image_style',
          '#style_name' => 'wide',
          '#uri' => $cat_image_uri,
          '#alt' => 'cat',
          '#title' => 'cat',
          '#width' => 255,
        ],
      ];

      // Format date.
      $cat['created'] = date('d-m-Y H:i:s', $cat['created']);

      // Build operations.
      $operation_params = [
        'id' => $cat['id'],
        'destination' => $destination,
      ];
      $edit_link = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('sasha_cat.admin.edit', $operation_params),
      ];
      $cat['operations'] = [
        'data' => [
          '#type' => 'dropbutton',
          '#links' => [
            'edit' => $edit_link,
          ],
        ],
      ];
    }

    $form['cat_table'] = [
      '#type' => 'tableselect',
      '#empty' => $this->t('Empty.'),
      '#options' => $cats,
      '#header' => [
        'cat_name' => $this->t('Cat name'),
        'email' => $this->t('Email'),
        'cat_image' => $this->t('Cat image'),
        'created' => $this->t('Created'),
        'operations' => $this->t('Operations'),
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected'),
      '#states' => [
        'enabled' => [
          ':input[name^="cat_table"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('ids', $form_state->getValue('cat_table'));
    $form_state->setRebuild();
  }

  /**
   * Confirmation form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function confirmForm(array &$form, FormStateInterface $form_state) {
    $ids = $form_state->get('ids');

    // Cleanup cat images.
    $query = $this->connection
      ->select('sasha_cat', 'sc')
      ->fields('sc', ['cat_image'])
      ->condition('id', $ids, 'IN')
      ->execute();
    foreach ($query as $cat) {
      $cat_image_file = $this->fileStorage->load($cat->cat_image);
      if ($cat_image_file) {
        $cat_image_file->setTemporary();
        $cat_image_file->save();
      }
    }

    // Delete cats from database.
    $this->connection
      ->delete('sasha_cat')
      ->condition('id', $ids, 'IN')
      ->execute();

    $this->messenger()->addMessage($this->t('Deleted!'));
    Cache::invalidateTags(['sasha_cat_table']);
  }

}