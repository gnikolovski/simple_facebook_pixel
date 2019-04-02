<?php

namespace Drupal\Tests\simple_facebook_pixel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form.
 *
 * @group simple_facebook_pixel
 */
class SimpleFacebookPixelSettingsFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'simple_facebook_pixel',
  ];

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $account = $this->drupalCreateUser([
      'administer modules',
      'administer simple facebook pixel',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests form structure.
   */
  public function testFormStructure() {
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->titleEquals('Simple Facebook Pixel | Drupal');
    $this->assertSession()->elementExists('css', '#edit-pixel-enabled');
    $this->assertSession()->elementExists('css', '#edit-pixel-id');
    $this->assertSession()->elementExists('css', '#edit-excluded-roles');
    $this->assertSession()->buttonExists(t('Save configuration'));
  }

  /**
   * Tests if configuration is possible.
   */
  public function testGoogleAnalyticsConfiguration() {
    $this->drupalGet('admin/modules');
    $this->assertSession()->responseContains('admin/config/system/simple-facebook-pixel');

    $this->assertEquals(TRUE, $this->config('simple_facebook_pixel.settings')->get('pixel_enabled'));
    $this->assertEquals('', $this->config('simple_facebook_pixel.settings')->get('pixel_id'));
    $this->assertEquals([], $this->config('simple_facebook_pixel.settings')->get('excluded_roles'));

    $edit['pixel_enabled'] = FALSE;
    $edit['pixel_id'] = '456123';
    $edit['excluded_roles[anonymous]'] = TRUE;
    $edit['excluded_roles[authenticated]'] = FALSE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->assertEquals(FALSE, $this->config('simple_facebook_pixel.settings')->get('pixel_enabled'));
    $this->assertEquals('456123', $this->config('simple_facebook_pixel.settings')->get('pixel_id'));
    $roles = [
      'anonymous' => 'anonymous',
      'authenticated' => '0',
    ];
    $this->assertArraySubset($roles, $this->config('simple_facebook_pixel.settings')->get('excluded_roles'));

    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '876321';
    $edit['excluded_roles[anonymous]'] = FALSE;
    $edit['excluded_roles[authenticated]'] = TRUE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->assertEquals(TRUE, $this->config('simple_facebook_pixel.settings')->get('pixel_enabled'));
    $this->assertEquals('876321', $this->config('simple_facebook_pixel.settings')->get('pixel_id'));
    $roles = [
      'anonymous' => '0',
      'authenticated' => 'authenticated',
    ];
    $this->assertArraySubset($roles, $this->config('simple_facebook_pixel.settings')->get('excluded_roles'));
  }

  /**
   * Tests form access for anonymous users.
   */
  public function testFormAccess() {
    $this->drupalLogout();
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->assertSession()->statusCodeEquals(403);
  }

}
