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

    $this->pixelBuilder = \Drupal::service('simple_facebook_pixel.pixel_builder');
  }

  /**
   * Tests alter hooks.
   */
  public function testAlterHooks() {
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelNoScriptCode('567123'));

    $this->container->get('module_installer')->install(['simple_facebook_pixel_test_hooks']);
    // @todo Remove invalidation once https://www.drupal.org/project/drupal/issues/2783791 is fixed.
    Cache::invalidateTags(['rendered']);

    $altered_pixel_script_code = 'Altered script code';
    $altered_pixel_noscript_code = 'Altered noscript code';

    $this->drupalGet('<front>');
    $this->assertSession()->responseContains($altered_pixel_script_code);
    $this->assertSession()->responseContains($altered_pixel_noscript_code);

    $this->drupalLogout();
    $this->assertSession()->responseContains($altered_pixel_script_code);
    $this->assertSession()->responseContains($altered_pixel_noscript_code);
  }

}
