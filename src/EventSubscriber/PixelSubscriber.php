<?php

namespace Drupal\simple_facebook_pixel\EventSubscriber;

use Drupal\Core\Cache\Cache;
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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

  /**
   * Invalidates page cache tags if needed.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    if (strpos($response->getContent(), 'CompleteRegistration') !== FALSE) {
      Cache::invalidateTags(['simple_facebook_pixel:complete_registration']);
    }
  }

}
