<?php

namespace Drupal\simple_facebook_pixel;

/**
 * Interface PixelBuilderServiceInterface.
 */
interface PixelBuilderServiceInterface {

  /**
   * Gets pixel script code.
   *
   * @return string
   *   The Facebook Pixel script code.
   */
  public function getPixelScriptCode();

  /**
   * Gets pixel no-script code.
   *
   * @return string
   *   The Facebook Pixel noscript code.
   */
  public function getPixelNoScriptCode();

}
