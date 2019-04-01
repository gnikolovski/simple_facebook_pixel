<?php

namespace Drupal\simple_facebook_pixel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SimpleFacebookPixelSettingsForm.
 *
 * @package Drupal\simple_facebook_pixel\Form
 */
class SimpleFacebookPixelSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_facebook_pixel_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_facebook_pixel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_facebook_pixel.settings');

    $form['pixel_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Facebook Pixel'),
      '#default_value' => $config->get('pixel_enabled'),
    ];

    $form['pixel_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Pixel ID'),
      '#default_value' => $config->get('pixel_id'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="pixel_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['excluded_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Exclude Facebook Pixel for the following roles'),
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#default_value' => $config->get('excluded_roles'),
      '#states' => [
        'visible' => [
          ':input[name="pixel_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('simple_facebook_pixel.settings')
      ->set('pixel_enabled', $values['pixel_enabled'])
      ->set('pixel_id', $values['pixel_id'])
      ->set('excluded_roles', $values['excluded_roles'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
