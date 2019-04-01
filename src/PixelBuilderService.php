<?php

namespace Drupal\simple_facebook_pixel;

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
  const FACEBOOK_PIXEL_CODE_NOSCRIPT = '<img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{pixel_id}}&ev=PageView&noscript=1"/>';

  /**
   * {@inheritdoc}
   */
  public function getPixelScriptCode($pixel_id) {
    return str_replace('{{pixel_id}}', $pixel_id, self::FACEBOOK_PIXEL_CODE_SCRIPT);
  }

  /**
   * {@inheritdoc}
   */
  public function getPixelNoScriptCode($pixel_id) {
    return str_replace('{{pixel_id}}', $pixel_id, self::FACEBOOK_PIXEL_CODE_NOSCRIPT);
  }

}
