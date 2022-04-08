<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class DeleteCats extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  public $catID;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'Delete Cat';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $catID = NULL) {
    $this->id = $catID;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Validation of the whole form using validation of certain fields.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Function that submit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::database();
    $query->delete('sasha_cat')
      ->condition('id', $this->id)
      ->execute();
    \Drupal::messenger()->addStatus('You deleted your cat');
    $form_state->setRedirect('sasha.cats');
  }

  /**
   * Return Question.
   */
  public function getQuestion() {
    return $this->t('Do you want to delete this Cat?');
  }

  /**
   * Return URL if cancel.
   */
  public function getCancelUrl() {
    return new Url('sasha.cats');
  }

  /**
   * Return Description.
   */
  public function getDescription() {
    return $this->t('Do you want to delete ?');
  }

  /**
   * Return confirm text.
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Return cancel text.
   */
  public function getCancelText() {
    return t('Cancel');
  }

}
