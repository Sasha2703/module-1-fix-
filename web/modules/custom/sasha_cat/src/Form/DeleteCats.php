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
  public function getFormId(): string {
    return 'Delete Cat';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $catID = NULL): array {
    $this->id = $catID;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
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
    $form_state->setRedirect('sasha.cats');
  }

  /**
   * {@inheritDoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Do you want to delete this Cat?');
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl(): Url {
    return new Url('sasha.cats');
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Do you want to delete ?');
  }

  /**
   * {@inheritDoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete');
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelText(): TranslatableMarkup {
    return t('Cancel');
  }

}
