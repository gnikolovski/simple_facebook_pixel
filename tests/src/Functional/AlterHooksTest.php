<?php

namespace Drupal\Tests\simple_facebook_pixel\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the alter hooks.
 *
 * @group simple_facebook_pixel
 */
class AlterHooksTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'taxonomy',
    'simple_facebook_pixel',
  ];

  /**
   * The pixel builder service.
   *
   * @var \Drupal\simple_facebook_pixel\PixelBuilderServiceInterface
   */
  protected $pixelBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $account = $this->drupalCreateUser([
      'administer simple facebook pixel',
    ]);
    $this->drupalLogin($account);

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    $this->createNode(['title' => 'Test page #1', 'type' => 'page']);
  }

  /**
   * Tests alter hooks.
   */
  public function testAlterHooks() {
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $edit['view_content_entities[node:page]'] = TRUE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->pixelBuilder = \Drupal::service('simple_facebook_pixel.pixel_builder');
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelScriptCode());
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelNoScriptCode());

    $this->container->get('module_installer')->install(['simple_facebook_pixel_test_hooks']);
    // @todo Remove invalidation once https://www.drupal.org/project/drupal/issues/2783791 is fixed.
    Cache::invalidateTags(['rendered']);

    $altered_events_code = 'Altered title';
    $altered_pixel_script_code = 'Appended script code text';
    $altered_pixel_noscript_code = 'Appended noscript code text';

    $this->drupalGet('/node/1');
    $this->assertSession()->responseContains($altered_events_code);
    $this->assertSession()->responseContains($altered_pixel_script_code);
    $this->assertSession()->responseContains($altered_pixel_noscript_code);

    $this->drupalLogout();
    $this->assertSession()->responseContains($altered_events_code);
    $this->assertSession()->responseContains($altered_pixel_script_code);
    $this->assertSession()->responseContains($altered_pixel_noscript_code);
  }

}
