<?php

namespace Drupal\sasha_cat\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class DeleteCats extends ConfirmFormBase{

  public  $catID;

  public function getFormId(){
    return 'Delete Cat';
  }
  public function buildForm(array $form, FormStateInterface $form_state, $catID = NULL){
    $this->id = $catID;
    return parent::buildForm($form, $form_state);
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state){
    parent::validateForm($form, $form_state);
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state){
    $query = \Drupal::database();
    $query->delete('sasha_cat')
      ->condition('id', $this->id)
      ->execute();
    \Drupal::messenger()->addStatus('You deleted your cat');
    $form_state->setRedirect('sasha.cats');
  }

  /**
   *
   */
  public function getQuestion(){
    return $this->t('Do you want to delete this Cat?');
  }

  /**
   *
   */
  public function getCancelUrl(){
    return new Url('sasha.cats');
  }

  /**
   *
   */
  public function getDescription() {
    return $this->t('Do you want to delete ?');
  }

  /**
   *
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   *
   */
  public function getCancelText() {
    return t('Cancel');
  }
}

