<?php

namespace Drupal\simple_facebook_pixel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Class PixelBuilderService.
 *
 * @package Drupal\simple_facebook_pixel
 */
class PixelBuilderService implements PixelBuilderServiceInterface {

  /**
   * Script base code.
   */
  const FACEBOOK_PIXEL_CODE_SCRIPT = "!function(f,b,e,v,n,t,s) {if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)}; if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0'; n.queue=[];t=b.createElement(e);t.async=!0; t.src=v;s=b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t,s)}(window, document,'script', 'https://connect.facebook.net/en_US/fbevents.js'); fbq('init', '{{pixel_id}}'); fbq('track', 'PageView');";

  /**
   * Noscript base code.
   */
  const FACEBOOK_PIXEL_CODE_NOSCRIPT = '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{pixel_id}}&ev=PageView&noscript=1"/></noscript>';

  /**
   * The Pixel ID.
   *
   * @var string
   */
  protected $pixelId;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The private temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStore;

  /**
   * An array of events.
   *
   * @var array
   */
  protected static $events = [];

  /**
   * PixelBuilderService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $private_temp_store
   *   The private temp store.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, PrivateTempStoreFactory $private_temp_store) {
    $this->pixelId = $config_factory->get('simple_facebook_pixel.settings')->get('pixel_id');
    $this->moduleHandler = $module_handler;
    $this->privateTempStore = $private_temp_store->get('simple_facebook_pixel');
  }

  /**
   * Adds an event.
   *
   * @param string $event
   *   The event name.
   * @param string|array $data
   *   The event data.
   * @param bool $use_store
   *   If set to true, an event will be stored in Private Temp Store.
   */
  public function addEvent($event, $data, $use_store = FALSE) {
    $event_data = [
      'event' => $event,
      'data' => $data,
    ];

    if ($use_store) {
      $this->privateTempStore->set('events', [$event_data]);
    }
    else {
      self::$events[] = $event_data;
    }
  }

  /**
   * Gets all events.
   *
   * @return array
   *   An array of events.
   */
  public function getEvents() {
    $static_events = self::$events;
    $stored_events = $this->getStoredEvents();
    $events = array_merge($static_events, $stored_events);

    return array_unique($events, SORT_REGULAR);
  }

  /**
   * Gets events stored in Private Temp Store.
   */
  protected function getStoredEvents() {
    $events = $this->privateTempStore->get('events');

    try {
      $this->privateTempStore->delete('events');
    }
    catch (\Exception $ex) {
    }

    return $events ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPixelScriptCode() {
    $pixel_script_code = str_replace('{{pixel_id}}', $this->pixelId, self::FACEBOOK_PIXEL_CODE_SCRIPT);

    $events = $this->getEvents();
    // Allow other modules to alter the events array.
    $this->moduleHandler->alter('simple_facebook_pixel_events', $events);

    foreach ($events as $event) {
      $pixel_script_code .= 'fbq("track", "' . $event['event'] . '", ' . json_encode($event['data']) . ');';
    }

    // Allow other modules to alter the script code.
    $this->moduleHandler->alter('simple_facebook_pixel_script_code', $pixel_script_code);

    return $pixel_script_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getPixelNoScriptCode() {
    $no_script_code = str_replace('{{pixel_id}}', $this->pixelId, self::FACEBOOK_PIXEL_CODE_NOSCRIPT);
    // Allow other modules to alter the noscript code.
    $this->moduleHandler->alter('simple_facebook_pixel_noscript_code', $no_script_code);

    return $no_script_code;
  }

}
