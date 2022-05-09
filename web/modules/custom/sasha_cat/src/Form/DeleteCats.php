<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
    return 'delete_cat';
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
    $query = \Drupal::database();
    $query->delete('sasha_cat')
      ->condition('id', $this->id)
      ->execute();
    \Drupal::messenger()->addStatus('You deleted your cat');
    $form_state->setRedirect('sasha_cat.content');
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
    return new Url('sasha_cat.content');
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription() {
    return $this->t('Do you want to delete ?');
  }

  /**
   * {@inheritDoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

}
