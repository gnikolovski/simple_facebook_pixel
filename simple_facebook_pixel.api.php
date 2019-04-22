<?php

/**
 * @file
 * Hooks specific to the Simple Facebook Pixel module.
 */

/**
 * Alter the event array.
 *
 * @param array $events
 *   The events array.
 */
function hook_simple_facebook_pixel_events_alter(&$events) {
  if (isset($events[0]['data']['content_name'])) {
    $events[0]['data']['content_name'] = 'Altered title';
  }
}

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
