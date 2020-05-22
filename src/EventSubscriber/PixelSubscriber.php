<?php

namespace Drupal\simple_facebook_pixel\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactory;
use Drupal\simple_facebook_pixel\PixelBuilderService;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PixelSubscriber.
 *
 * @package Drupal\simple_facebook_pixel\EventSubscriber
 */
class PixelSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The Pixel builder.
   *
   * @var \Drupal\simple_facebook_pixel\PixelBuilderService
   */
  protected $pixelBuilder;

  /**
   * PixelSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\simple_facebook_pixel\PixelBuilderService $pixel_builder
   *   The Pixel builder.
   */
  public function __construct(ConfigFactory $config_factory, PixelBuilderService $pixel_builder) {
    $this->configFactory = $config_factory->get('simple_facebook_pixel.settings');
    $this->pixelBuilder = $pixel_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse'];
    $events['commerce_cart.entity.add'][] = ['addToCartEvent'];
    $events['commerce_wishlist.entity.add'][] = ['addToWishlist'];
    $events['flag.entity_flagged'][] = ['addToWishlistFlag'];
    $events['commerce_order.place.post_transition'][] = ['purchaseEvent', 50];
    return $events;
  }

  /**
   * Invalidates page cache tags if needed.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    if (strpos($response->getContent(), 'CompleteRegistration') !== FALSE) {
      Cache::invalidateTags(['simple_facebook_pixel:complete_registration']);
    }

    if (strpos($response->getContent(), '"track", "AddToCart"') !== FALSE) {
      Cache::invalidateTags(['simple_facebook_pixel:add_to_cart']);
    }

    if (strpos($response->getContent(), '"track", "AddToWishlist"') !== FALSE) {
      Cache::invalidateTags(['simple_facebook_pixel:add_to_wishlist']);
    }

    if (strpos($response->getContent(), '"track", "Purchase"') !== FALSE) {
      Cache::invalidateTags(['simple_facebook_pixel:purchase']);
    }
  }

  /**
   * Adds AddToCart event.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The add to cart event.
   */
  public function addToCartEvent(Event $event) {
    if ($this->pixelBuilder->isEnabled() && $this->configFactory->get('add_to_cart_enabled')) {
      $product_variation = $event->getEntity();
      $quantity = $event->getQuantity();
      $this->addItem($product_variation, $quantity, 'AddToCart');
    }
  }

  /**
   * Adds AddToWishlist event. Using Commerce Wishlist module.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The add to wishlist event.
   */
  public function addToWishlist(Event $event) {
    if ($this->pixelBuilder->isEnabled() && $this->configFactory->get('add_to_wishlist_enabled')) {
      $product_variation = $event->getEntity();
      $quantity = $event->getQuantity();
      $this->addItem($product_variation, $quantity, 'AddToWishlist');
    }
  }

  /**
   * Adds AddToWishlist event. Using Flag module.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The add to wishlist event.
   */
  public function addToWishlistFlag(Event $event) {
    if ($this->pixelBuilder->isEnabled() && $this->configFactory->get('add_to_wishlist_flag_enabled')) {
      $enabled_flags = array_filter(array_values($this->configFactory->get('add_to_wishlist_flag_list')));

      if (in_array($event->getFlagging()->getFlagId(), $enabled_flags)) {
        $entity = $event->getFlagging()->getFlaggable();

        if ($entity instanceof \Drupal\commerce_product\Entity\ProductInterface) {
          $this->addItem($entity->getDefaultVariation(), 1, 'AddToWishlist');
        }
      }
    }
  }

  /**
   * Adds an event.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $product_variation
   *   The product variation.
   * @param float $quantity
   *   The quantity added.
   * @param string $event_name
   *   The Facebook Pixel event name.
   */
  protected function addItem($product_variation, $quantity, $event_name) {
    $contents[] = [
      'id' => $product_variation->getSku(),
      'quantity' => $quantity,
    ];

    $data = [
      'content_name' => $product_variation->getProduct()->getTitle(),
      'content_type' => 'product',
      'content_ids' => [$product_variation->getSku()],
      'value' => $product_variation->getPrice()->getNumber(),
      'currency' => $product_variation->getPrice()->getCurrencyCode(),
      'contents' => $contents,
    ];

    $this->pixelBuilder->addEvent($event_name, $data, TRUE);
  }

  /**
   * Adds Purchase event.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function purchaseEvent(WorkflowTransitionEvent $event) {
    if ($this->pixelBuilder->isEnabled() && $this->configFactory->get('purchase_enabled')) {
      $commerce_order = $event->getEntity();

      $skus = [];
      $contents = [];

      /** @var \Drupal\commerce_order\Entity\OrderItem $item */
      foreach ($commerce_order->getItems() as $item) {
        $skus[] = $item->getPurchasedEntity()->getSku();

        $contents[] = [
          'id' => $item->getPurchasedEntity()->getSku(),
          'quantity' => $item->getQuantity(),
        ];
      }

      $data = [
        'num_items' => count($commerce_order->getItems()),
        'value' => $commerce_order->getTotalPrice()->getNumber(),
        'currency' => $commerce_order->getTotalPrice()->getCurrencyCode(),
        'content_ids' => $skus,
        'contents' => $contents,
        'content_type' => 'product',
      ];

      $this->pixelBuilder->addEvent('Purchase', $data, TRUE);
    }
  }

}
