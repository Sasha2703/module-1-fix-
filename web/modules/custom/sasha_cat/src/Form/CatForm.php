<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\sasha_cat\Controller\SashaCatController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Annotation @todo.
 */
class CatForm extends FormBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The main content to AJAX Response renderer.
   *
   * @var \Drupal\Core\Render\MainContent\MainContentRendererInterface
   */
  protected $ajaxRenderer;

  /**
   * Constructs a new CatForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   * @param \Drupal\Core\Render\MainContent\MainContentRendererInterface $ajax_renderer
   *   The ajax renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    Connection $connection,
    EntityStorageInterface $file_storage,
    MainContentRendererInterface $ajax_renderer,
    RouteMatchInterface $route_match,
    RendererInterface $renderer
  ) {
    $this->connection = $connection;
    $this->fileStorage = $file_storage;
    $this->ajaxRenderer = $ajax_renderer;
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('main_content_renderer.ajax'),
      $container->get('current_route_match'),
      $container->get('renderer')
    );
  }

  /**
   * ID of the item to edit.
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'sasha_cat';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    $cat_data = [];
    if ($id) {
      $cat_data = $this->connection
        ->select('sasha_cat', 'sc')
        ->condition('sc.id', $id)
        ->fields('sc', ['cat_name', 'email', 'cat_image', 'id'])
        ->execute()
        ->fetchAssoc();
    }

    $wrapper_id = 'sasha-cat-form-ajax';
    $form['ajax_wrapper'] = [
      '#type' => 'container',
      '#id' => $wrapper_id,
    ];

    $form['ajax_wrapper']['cat_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#placeholder' => $this->t(
        'The name must be in range from 2 to 32 symbols'
      ),
      '#required' => TRUE,
      '#maxlength' => 32,
      '#default_value' => @$cat_data['cat_name'],
    ];

    $form['ajax_wrapper']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#placeholder' => $this->t('example@email.com'),
      '#required' => TRUE,
      '#default_value' => @$cat_data['email'],
      '#attributes' => [
        'drupal-ajax-nodisable' => '',
      ],
      '#attached' => [
        'library' => ['sasha_cat/ajax_plugin'],
      ],
    ];

    $form['ajax_wrapper']['cat_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Your cat’s photo:'),
      '#description' => $this->t(
        'Please use only these extensions: jpeg, jpg, png'
      ),
      '#upload_location' => 'public://sasha_cat/',
      '#default_value' => [(int) @$cat_data['cat_image']],
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['jpeg jpg png'],
        'file_validate_size' => [2097152],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'wrapper' => $wrapper_id,
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ((mb_strlen($form_state->getValue('cat_name')) < 2)) {
      $form_state->setErrorByName(
        'cat_name', $this->t('The minimum name length is 2 characters.')
      );
    }

    // Use custom validation to meet the requirements of the specification.
    $email = $form_state->getValue('email');
    if (!preg_match('/^[A-Za-z_\-]+@\w+(?:\.\w+)+$/', $email)) {
      $form_state->setErrorByName(
        'email', $this->t('The email name can only contain latin letters, hyphens, and underscores.')
      );
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove old image if exists.
    if ($this->id) {
      $cat_image = $this->connection->select('sasha_cat', 'sc')
        ->fields('sc', ['id'])
        ->condition('sc.id', $this->id)
        ->execute()
        ->fetchField();
      /** @var \Drupal\file\FileInterface $cat_image_file */
      $cat_image_file = $this->fileStorage->load($cat_image);
      if ($cat_image_file) {
        $cat_image_file->setTemporary();
        $cat_image_file->save();
      }
    }

    // Set usage for new image.
    $cat_image = $form_state->getValue('cat_image')[0];
    /** @var \Drupal\file\FileInterface $cat_image_file */
    $cat_image_file = $this->fileStorage->load($cat_image);
    $cat_image_file->setPermanent();
    $cat_image_file->save();

    // Update or insert cat to database.
    $cat_data = [
      'cat_name' => $form_state->getValue('cat_name'),
      'email' => $form_state->getValue('email'),
      'cat_image' => $cat_image,
      'created' => time(),
    ];
    $this->connection->merge('sasha_cat')
      ->condition('id', $this->id)
      ->fields($cat_data)
      ->execute();
    if ($this->id) {
      $this->messenger()->addStatus($this->t('Cat edited.'));
    }
    else {

      $this->messenger()->addStatus(
        $this->t('Congratulations! You added your cat!'));
    }
    Cache::invalidateTags(['sasha_cat_table']);

    // Reset form.
    $user_input = &$form_state->getUserInput();
    $internal_elements = $form_state->getCleanValueKeys();
    foreach ($user_input as $key => $value) {
      if (!in_array($key, $internal_elements)) {
        unset($user_input[$key]);
      }
    }
    $form_state->setRebuild();
  }

  /**
   * Callback for additional ajax commands on form submission.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state, Request $request) {
    // Render form with drupal messages.
    /** @var \Drupal\Core\Ajax\AjaxResponse $response */
    $response = $this
      ->ajaxRenderer
      ->renderResponse($form['ajax_wrapper'], $request, $this->routeMatch);

    // Update cats table if success.
    if (!$form_state::hasAnyErrors()) {
      $cat_controller = SashaCatController::create(\Drupal::getContainer());
      $response->addCommand(new ReplaceCommand('.sasha-cat-table', $cat_controller->buildCatTable()));

      if ($this->id) {
        $destination = $this->getRequest()->query->get('destination') ?: 'sasha_cat.content';
        $destination = Url::fromRoute($destination)->toString();
        $response->addCommand(new RedirectCommand($destination));

      }
    }

    return $response;
  }

}
