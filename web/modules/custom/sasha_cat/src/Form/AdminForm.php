<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
   * ID of the item to delete.
   *
   * @var int
   */
  protected int $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sasha_cat';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $connection = \Drupal::service('database');
    $query = $connection->select('sasha_cat', 'sasha_cattb');
    $query->fields('sasha_cattb', ['id', 'name', 'email', 'image', 'date'])
      ->orderBy('id', 'DESC');
    $info = $query->execute()->fetchAll();
    $headers = [
      t('Cat'),
      t('Image'),
      t('Mail'),
      t('Time'),
      t('Edit'),
    ];
    $rows = [];
    foreach ($info as &$value) {
      $fid = $value['image'];
      $id = $value['id'];
      $name = $value['name'];
      $email = $value['email'];
      $date = $value['date'];
      array_splice($value, 0, 5);
      $renderer = \Drupal::service('renderer');
      $file = File::load($fid);
      $img = [
        '#type' => 'image',
        '#theme' => 'image_style',
        '#style_name' => 'thumbnail',
        '#uri' => $file->getFileUri(),
      ];
      $edit = [
        '#type' => 'link',
        '#url' => Url::fromUserInput("/sasha-cat/cat/$id/edit"),
        '#title' => $this->t('Edit'),
        '#attributes' => ['class' => ['button']],
      ];
      $newId = [
        '#type' => 'hidden',
        '#value' => $id,
      ];
      $value[0] = $name;
      $value[1] = $renderer->render($img);
      $value[2] = $email;
      $value[3] = $date;
      $value[4] = $renderer->render($edit);
      $value[5] = $newId;
      array_push($rows, $value);
    }
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $headers,
      '#options' => $rows,
      '#empty' => t('No entries available.'),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected'),
      '#description' => $this->t('Submit, #type = submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $value = $form['table']['#value'];
    $connection = \Drupal::service('database');
    foreach ($value as $key => $val) {
      $result = $connection->delete('sasha_cat');
      $result->condition('id', $form['table']['#options'][$key][5]["#value"]);
      $result->execute();
    }
  }

}
