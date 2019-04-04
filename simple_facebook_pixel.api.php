<?php

/**
 * @file
 * Hooks specific to the Simple Facebook Pixel module.
 */

/**
 * Alter the script code for pages.
 *
 * @param string $script_code
 *   The script code.
 */
function hook_simple_facebook_pixel_script_code_alter(&$script_code) {
  $script_code = 'Altered script code';
}

/**
 * Alter the noscript code for pages.
 *
 * @param string $noscript_code
 *   The noscript code.
 */
function hook_simple_facebook_pixel_noscript_code_alter(&$noscript_code) {
  $noscript_code = 'Altered noscript code';
}
