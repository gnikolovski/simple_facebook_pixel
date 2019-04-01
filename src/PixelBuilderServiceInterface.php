<?php

namespace Drupal\simple_facebook_pixel;

/**
 * Interface PixelBuilderServiceInterface.
 */
interface PixelBuilderServiceInterface {

  /**
   * Gets pixel script code.
   *
   * @param string $pixel_id
   *   The Facebook Pixel ID.
   *
   * @return string
   *   The Facebook Pixel script code.
   */
  public function getPixelScriptCode($pixel_id);

  /**
   * Gets pixel no-script code.
   *
   * @param string $pixel_id
   *   The Facebook Pixel ID.
   *
   * @return string
   *   The Facebook Pixel noscript code.
   */
  public function getPixelNoScriptCode($pixel_id);

}
