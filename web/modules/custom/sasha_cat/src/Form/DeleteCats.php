<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 *
 * @throw \Drupal\Core\Form\ConfirmFormBase
 */
class DeleteCats extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected int $id;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'sasha_cat_delete';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::database()
      ->delete('sasha_cat')
      ->condition('id', $this->id)
      ->execute();
    Cache::invalidateTags(['sasha_cat_table']);
    $this->messenger()->addStatus('You deleted your cat.');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritDoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete this Cat?');
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl() {
    $destination = \Drupal::request()->query->get('destination') ?? 'sasha_cat.content';
    return new Url($destination);
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription() {
    return $this->t('Do you want to delete?');
  }

  /**
   * {@inheritDoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

}
