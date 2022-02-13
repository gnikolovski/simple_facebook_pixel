<?php

namespace Drupal\simple_facebook_pixel;

/**
 * Defines the Pixel Builder Service interface.
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

  /**
   * Checks if Facebook Pixel should be enabled.
   *
   * @return bool
   *   True if enabled, False otherwise.
   */
  public function isEnabled();

}
