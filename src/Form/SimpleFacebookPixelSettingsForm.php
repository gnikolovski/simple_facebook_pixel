<?php

namespace Drupal\simple_facebook_pixel\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SimpleFacebookPixelSettingsForm.
 *
 * @package Drupal\simple_facebook_pixel\Form
 */
class SimpleFacebookPixelSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SimpleFacebookPixelSettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

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

    $form['events']['page_view_notice'] = [
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

    $form['events']['initiate_checkout_notice'] = [
      '#type' => 'markup',
      '#markup' => '<strong>' . $this->t('Initiate Checkout') . '</strong>',
      '#states' => [
        'visible' => [
          ':input[name="pixel_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['events']['initiate_checkout_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('initiate_checkout_enabled'),
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
      ->set('initiate_checkout_enabled', $values['initiate_checkout_enabled'])
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

    if ($this->entityTypeManager->hasDefinition('node_type')) {
      $content_types = $this->entityTypeManager
        ->getStorage('node_type')
        ->loadMultiple();

      foreach ($content_types as $content_type) {
        $result['node:' . $content_type->getOriginalId()] = $this->t('Node: @label', ['@label' => $content_type->label()]);
      }
    }

    if ($this->entityTypeManager->hasDefinition('taxonomy_vocabulary')) {
      $vocabularies = $this->entityTypeManager
        ->getStorage('taxonomy_vocabulary')
        ->loadMultiple();

      foreach ($vocabularies as $vocabulary) {
        $result['taxonomy_term:' . $vocabulary->getOriginalId()] = $this->t('Taxonomy: @label', ['@label' => $vocabulary->label()]);
      }
    }

    if ($this->entityTypeManager->hasDefinition('commerce_product')) {
      $commerce_products = $this->entityTypeManager
        ->getStorage('commerce_product_type')
        ->loadMultiple();

      foreach ($commerce_products as $commerce_product) {
        $result['commerce_product:' . $commerce_product->getOriginalId()] = $this->t('Commerce Product: @label', ['@label' => $commerce_product->label()]);
      }
    }

    return $result;
  }

}
