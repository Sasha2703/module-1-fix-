<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Form for editing cats.
 *
 * @throw \Drupal\Core\Form\FormBase
 */
class EditCats extends FormBase {

  /**
   * Cat to edit if any.
   *
   * @var int
   */
  protected int $id;

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'edit cat';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $catID = NULL): array {
    $this->id = $catID;
    $query = \Drupal::database();
    $data = $query
      ->select('sasha_cat', 'edt')
      ->condition('edt.id', $catID)
      ->fields('edt', ['name', 'email', 'image', 'id'])
      ->execute()->fetchAll();
    $form['adding_cat'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#default_value' => $data[0]->name,
      '#required' => TRUE,
      '#maxlength' => 32,
      '#ajax' => [
        'callback' => '::ajaxValidName',
        'event' => 'change',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#default_value' => $data[0]->email,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxValidEmail',
        'event' => 'change',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $form['cat_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Your cat’s photo:'),
      '#description' => t('Please use only these extensions: jpeg, jpg, png'),
      '#upload_location' => 'public://images/',
      '#default_value' => [$data[0]->image],
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['jpeg jpg png'],
        'file_validate_size' => [2097152],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Edit'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::setMessage',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    return $form;
  }

  /**
   * Function that validate Name field.
   */
  public function validateName(array &$form, FormStateInterface $form_state): bool {
    if ((mb_strlen($form_state->getValue('adding_cat')) < 2)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Set messages of errors or success using ajax for the name field.
   */
  public function ajaxValidName(array &$form, FormStateInterface $form_state): AjaxResponse {
    $valid = $this->validateName($form, $form_state);
    $response = new AjaxResponse();
    if ($valid) {
      $response->addCommand(new MessageCommand('Your name is valid'));
    }
    else {
      $response->addCommand(new MessageCommand('Your name is too short', ".null", ['type' => 'error']));
    }
    return $response;
  }

  /**
   * Function that validate Email field.
   */
  public function validateEmail(array &$form, FormStateInterface $form_state): bool {
    if (filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function that validate Email field with AJAX.
   */
  public function ajaxValidEmail(array &$form, FormStateInterface $form_state): AjaxResponse {
    $valid = $this->validateEmail($form, $form_state);
    $response = new AjaxResponse();
    if ($valid) {
      $response->addCommand(new MessageCommand('Your email is valid'));
    }
    else {
      $response->addCommand(new MessageCommand('Your email is NOT valid', ".null", ['type' => 'error']));
    }
    return $response;
  }

  /**
   * Function that validate Image field.
   */
  public function validateImage(array &$form, FormStateInterface $form_state): bool {
    $picture = $form_state->getValue('cat_image');
    if (!empty($picture[0])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): bool {
    if ($this->validateName($form, $form_state) && $this->validateEmail($form, $form_state) && $this->validateImage($form, $form_state)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->validateForm($form, $form_state)) {
      $picture = $form_state->getValue('cat_image');
      $file = File::load($picture[0]);
      $file->setPermanent();
      $file->save();

      $cat = [
        'name' => $form_state->getValue('adding_cat'),
        'email' => $form_state->getValue('email'),
        'image' => $picture[0],
      ];
      \Drupal::database()
        ->update('sasha_cat')
        ->condition('id', $this->id)
        ->fields($cat)
        ->execute();
      $form_state->setRedirect('sasha.cats');
    }
  }

  /**
   * Function that validate Name and Image field with Ajax.
   */
  public function setMessage(array &$form, FormStateInterface $form_state): AjaxResponse {
    $nameValid = $this->validateName($form, $form_state);
    $imageValid = $this->validateImage($form, $form_state);
    $response = new AjaxResponse();
    if (!$nameValid) {
      $response->addCommand(new MessageCommand('Your name is NOT valid', ".null", ['type' => 'error']));
    }
    elseif (!$imageValid) {
      $response->addCommand(new MessageCommand('Please, upload your cat image', ".null", ['type' => 'error']));
    }
    else {
      $url = Url::fromRoute('sasha.cats');
      $response->addCommand(new RedirectCommand($url->toString()));
      $response->addCommand(new MessageCommand('Congratulations! You added your cat!'));
    }
    return $response;
  }

}
