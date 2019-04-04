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

    $form['basic_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic settings'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="pixel_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['basic_settings']['pixel_id'] = [
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

    $form['basic_settings']['excluded_roles'] = [
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

    $form['events'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Events'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="pixel_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['events']['notice'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('PageView event is by default enabled on all pages. Other events can be enabled/disabled bellow.') . '</p>',
      '#states' => [
        'visible' => [
          ':input[name="pixel_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['events']['view_content_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Add ViewContent event to the following pages'),
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $this->getViewContentEntities()),
      '#default_value' => $config->get('view_content_entities'),
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
      ->set('view_content_entities', $values['view_content_entities'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets content entities for which it is possible to enable ViewContent event.
   *
   * @return array
   *   The list of entities.
   */
  protected function getViewContentEntities() {
    $result = [];

    $content_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($content_types as $content_type) {
      $result['node:' . $content_type->getOriginalId()] = $this->t('Node') . ': ' . $content_type->label();
    }

    $vocabularies = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_vocabulary')
      ->loadMultiple();

    foreach ($vocabularies as $vocabulary) {
      $result['taxonomy_term:' . $vocabulary->getOriginalId()] = $this->t('Taxonomy') . ': ' . $vocabulary->label();
    }

    return $result;
  }

}
