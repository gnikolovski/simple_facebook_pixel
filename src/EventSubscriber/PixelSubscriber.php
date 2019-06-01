<?php

namespace Drupal\simple_facebook_pixel\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactory;
use Drupal\simple_facebook_pixel\PixelBuilderService;
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
   * Adds AddToWishlist event.
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

}
