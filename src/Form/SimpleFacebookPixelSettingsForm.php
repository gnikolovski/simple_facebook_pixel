<?php

namespace Drupal\simple_facebook_pixel\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SimpleFacebookPixelSettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('config.factory')
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
      '#description' => $this->t('Your Facebook Pixel ID. Separate multiple Pixels with a comma.'),
      '#default_value' => $config->get('pixel_id'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="pixel_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['basic_settings']['exclude_admin_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude admin pages'),
      '#default_value' => $config->get('exclude_admin_pages'),
    ];

    $form['basic_settings']['excluded_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Exclude Facebook Pixel for the following roles.'),
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

    $form['events']['complete_registration_notice'] = [
      '#type' => 'markup',
      '#markup' => '<strong>' . $this->t('CompleteRegistration') . '</strong>',
      '#states' => [
        'visible' => [
          ':input[name="pixel_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['events']['complete_registration_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('complete_registration_enabled'),
      '#states' => [
        'visible' => [
          ':input[name="pixel_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if ($this->moduleHandler->moduleExists('commerce_checkout')) {
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

      $form['events']['purchase_notice'] = [
        '#type' => 'markup',
        '#markup' => '<strong>' . $this->t('Purchase') . '</strong>',
        '#states' => [
          'visible' => [
            ':input[name="pixel_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['events']['purchase_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable'),
        '#default_value' => $config->get('purchase_enabled'),
        '#states' => [
          'visible' => [
            ':input[name="pixel_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['events']['add_to_cart_notice'] = [
        '#type' => 'markup',
        '#markup' => '<strong>' . $this->t('AddToCart') . '</strong>',
        '#states' => [
          'visible' => [
            ':input[name="pixel_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['events']['add_to_cart_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable'),
        '#default_value' => $config->get('add_to_cart_enabled'),
        '#states' => [
          'visible' => [
            ':input[name="pixel_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    if (
      $this->moduleHandler->moduleExists('commerce_wishlist') ||
      $this->moduleHandler->moduleExists('flag')
    ) {
      $form['events']['add_to_wishlist_notice'] = [
        '#type' => 'markup',
        '#markup' => '<strong>' . $this->t('AddToWishlist') . '</strong>',
        '#states' => [
          'visible' => [
            ':input[name="pixel_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    if ($this->moduleHandler->moduleExists('commerce_wishlist')) {
      $form['events']['add_to_wishlist_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable (using Commerce Wishlist module)'),
        '#default_value' => $config->get('add_to_wishlist_enabled'),
        '#states' => [
          'visible' => [
            ':input[name="pixel_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    if ($this->moduleHandler->moduleExists('flag')) {
      $form['events']['add_to_wishlist_flag_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable (using Flag module)'),
        '#default_value' => $config->get('add_to_wishlist_flag_enabled'),
        '#states' => [
          'visible' => [
            ':input[name="pixel_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['events']['add_to_wishlist_flag_list'] = [
        '#type' => 'checkboxes',
        '#options' => $this->getFlags(),
        '#title' => $this->t('Available flags'),
        '#default_value' => $config->get('add_to_wishlist_flag_list'),
        '#states' => [
          'visible' => [
            ':input[name="pixel_enabled"]' => ['checked' => TRUE],
            ':input[name="add_to_wishlist_flag_enabled"]' => ['checked' => TRUE],
          ],
        ],
        '#prefix' => '<div class="available-flags">',
        '#suffix' => '</div>',
      ];
    }

    $form['#attached']['library'][] = 'simple_facebook_pixel/simple_facebook_pixel.admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config('simple_facebook_pixel.settings')
      ->set('pixel_enabled', $values['pixel_enabled'])
      ->set('pixel_id', $values['pixel_id'])
      ->set('exclude_admin_pages', $values['exclude_admin_pages'])
      ->set('excluded_roles', $values['excluded_roles'])
      ->set('view_content_entities', $values['view_content_entities'])
      ->set('complete_registration_enabled', $values['complete_registration_enabled']);

    if ($this->moduleHandler->moduleExists('commerce_checkout')) {
      $config
        ->set('initiate_checkout_enabled', $values['initiate_checkout_enabled'])
        ->set('purchase_enabled', $values['purchase_enabled'])
        ->set('add_to_cart_enabled', $values['add_to_cart_enabled']);
    }

    if ($this->moduleHandler->moduleExists('commerce_wishlist')) {
      $config
        ->set('add_to_wishlist_enabled', $values['add_to_wishlist_enabled']);
    }

    if ($this->moduleHandler->moduleExists('flag')) {
      $config
        ->set('add_to_wishlist_flag_enabled', $values['add_to_wishlist_flag_enabled'])
        ->set('add_to_wishlist_flag_list', $values['add_to_wishlist_flag_list']);
    }

    $config->save();

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

  /**
   * Gets a list of available flags.
   *
   * @return array
   *   An array of flags.
   */
  protected function getFlags() {
    $flags_list = [];

    if ($this->moduleHandler->moduleExists('flag')) {
      $flags = \Drupal::service('flag')
        ->getAllFlags('commerce_product');

      foreach ($flags as $flag) {
        $flags_list[$flag->id()] = $flag->label();
      }
    }

    return $flags_list;
  }

}
