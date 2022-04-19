<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Contains \Drupal\sasha_cat\Form\AdminForm.
 *
 * @file
 */

/**
 * Implements administration page for cats.
 */
class AdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'sasha_cat';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['item'] = [
      '#type' => 'page_title',
      '#title' => $this->t("You can add here a photo of your cat!"),
    ];

    $form['adding_cat'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#placeholder' => $this->t('The name must be in range from 2 to 32 symbols'),
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
      '#placeholder' => $this->t('example@email.com'),
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
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['jpeg jpg png'],
        'file_validate_size' => [2097152],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add cat'),
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
   * Function that validate Name field on its length.
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
   * Function that validate Email field with Ajax.
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
        'date' => date('d-m-Y H:i:s'),
      ];
      \Drupal::database()->insert('sasha_cat')->fields($cat)->execute();
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
      $url = Url::fromRoute('admin.cats');
      $response->addCommand(new RedirectCommand($url->toString()));
      $response->addCommand(new MessageCommand('Congratulations! You added your cat!'));
    }
    return $response;
  }

}
