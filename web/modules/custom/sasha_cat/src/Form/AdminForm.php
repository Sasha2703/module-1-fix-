<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;


class AdminForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm($form, FormStateInterface $form_state): array {
    $header_title = [
      'id' => $this->t('id'),
      'name' => $this->t('Name'),
      'email' => $this->t('Email'),
      'image' => $this->t('Image'),
      'timestamp' => $this->t('Date Created'),
      'delete' => $this->t('Delete'),
      'edit' => $this->t('Edit'),
    ];
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header_title,
      '#options' => $this->getCats(),
      '#empty' => $this->t('There are no items.'),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected'),
      '#states' => [
        'enabled' => [
          ':input[name^="table"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'sasha_cat_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('sasha_cat.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('You really want to delete selected cat(s)?');
  }

  /**
   * Searches cats or one cat in db.
   *
   * @return array
   *   Cat's objects array.
   */
  public function getCats(): array {
    $database = \Drupal::database();
    $result = $database->select('sasha_cat', 'sasha_cattb')
      ->fields('sasha_cattb', ['id', 'name', 'email', 'image', 'date'])
      ->orderBy('id', 'DESC')
      ->execute();
    $cats = [];
    foreach ($result as $cat) {
      $cats[] = [
        'id' => $cat->id,
        'name' => $cat->name,
        'email' => $cat->email,
        'image' => [
          'data' => [
            '#theme' => 'image_style',
            '#style_name' => 'thumbnail',
            '#uri' => File::load($cat->image)->getFileUri(),
            '#attributes' => [
              'alt' => $cat->name,
              'title' => $cat->name,
            ],
          ],
        ],
        'timestamp' => date($cat->date),
        'edit' => [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Edit'),
            '#url' => Url::fromUserInput("/sasha-cat/cat/$cat->id/edit"),
            '#options' => [
              'attributes' => [
                'class' => 'button',
                'data-dialog-options' => '{ "title":"Edit cat information"}',
              ],
            ],
          ],
        ],
        'delete' => [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Delete'),
            '#url' => Url::fromRoute('sasha_cat.delete', ['id' => $cat->id]),
            '#options' => [
              'attributes' => [
                'class' => ['button', 'use-ajax'],
                'data-dialog-type' => 'modal',
              ],
            ],
          ],
        ],
      ];
    }
    return $cats;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rows = $form_state->getCompleteForm()['table']['#value'];
    if ($rows) {
      $id = [];
      foreach ($rows as $i) {
        $id[] = $form['table']['#options'][$i]['id'];
      }
      $database = \Drupal::database();
      $database->delete('sasha_cat')
        ->condition('id', $id, 'IN')
        ->execute();
      \Drupal::messenger()->addStatus('Successfully deleted.');
    }
    else {
      $this->messenger()->addMessage($this->t("No rows selected to delete"), 'error');
    }
  }

}