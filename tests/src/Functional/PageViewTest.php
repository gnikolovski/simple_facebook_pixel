<?php

namespace Drupal\Tests\simple_facebook_pixel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests PageView event.
 *
 * @group simple_facebook_pixel
 */
class PageViewTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'simple_facebook_pixel',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'administer simple facebook pixel',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests snippet insertion when Facebook Pixel is missing.
   */
  public function testFacebookPixelIdMissing() {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);

    /** @var \Drupal\simple_facebook_pixel\PixelBuilderServiceInterface $pixel_builder */
    $pixel_builder = \Drupal::service('simple_facebook_pixel.pixel_builder');

    $this->assertSession()->responseNotContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseNotContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogout();
    $this->assertSession()->responseNotContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseNotContains($pixel_builder->getPixelNoScriptCode());
  }

  /**
   * Tests snippet insertion when Facebook Pixel is disabled.
   */
  public function testFacebookPixelDisabled() {
    $edit['pixel_enabled'] = FALSE;
    $edit['pixel_id'] = '789012';
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    /** @var \Drupal\simple_facebook_pixel\PixelBuilderServiceInterface $pixel_builder */
    $pixel_builder = \Drupal::service('simple_facebook_pixel.pixel_builder');

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseNotContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogout();
    $this->assertSession()->responseNotContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseNotContains($pixel_builder->getPixelNoScriptCode());
  }

  /**
   * Tests snippet insertion when Facebook Pixel is disabled for admin routes.
   */
  public function testDisablingForAdminRoutes() {
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '789012';
    $edit['exclude_admin_pages'] = FALSE;
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    /** @var \Drupal\simple_facebook_pixel\PixelBuilderServiceInterface $pixel_builder */
    $pixel_builder = \Drupal::service('simple_facebook_pixel.pixel_builder');
    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());

    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '789012';
    $edit['exclude_admin_pages'] = TRUE;
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->assertSession()->responseNotContains($pixel_builder->getPixelScriptCode());
  }

  /**
   * Tests snippet insertion for all users.
   */
  public function testFacebookPixelEnabledForAllUsers() {
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    /** @var \Drupal\simple_facebook_pixel\PixelBuilderServiceInterface $pixel_builder */
    $pixel_builder = \Drupal::service('simple_facebook_pixel.pixel_builder');

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogout();
    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseContains($pixel_builder->getPixelNoScriptCode());
  }

  /**
   * Tests adding mutliple pixels.
   */
  public function testMultiFacebookPixelsEnabledForAllUsers() {
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123, 985473';
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    /** @var \Drupal\simple_facebook_pixel\PixelBuilderServiceInterface $pixel_builder */
    $pixel_builder = \Drupal::service('simple_facebook_pixel.pixel_builder');

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseContains("fbq('init', '567123') fbq('init', '985473')");
    $this->assertSession()->responseContains($pixel_builder->getPixelNoScriptCode());
    $this->assertSession()->responseContains('https://www.facebook.com/tr?id=567123&ev=PageView&noscript=1');
    $this->assertSession()->responseContains('https://www.facebook.com/tr?id=985473&ev=PageView&noscript=1');

    $this->drupalLogout();
    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseContains($pixel_builder->getPixelNoScriptCode());
  }

  /**
   * Tests snippet exclusion for selected roles.
   */
  public function testFacebookPixelExclusionForRoles() {
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $edit['excluded_roles[anonymous]'] = TRUE;
    $edit['excluded_roles[authenticated]'] = FALSE;
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    /** @var \Drupal\simple_facebook_pixel\PixelBuilderServiceInterface $pixel_builder */
    $pixel_builder = \Drupal::service('simple_facebook_pixel.pixel_builder');

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogout();
    $this->assertSession()->responseNotContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseNotContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogin($this->user);
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $edit['excluded_roles[anonymous]'] = FALSE;
    $edit['excluded_roles[authenticated]'] = TRUE;
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseNotContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogout();
    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogin($this->user);
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $edit['excluded_roles[anonymous]'] = TRUE;
    $edit['excluded_roles[authenticated]'] = TRUE;
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseNotContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogout();
    $this->assertSession()->responseNotContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseNotContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogin($this->user);
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $edit['excluded_roles[anonymous]'] = FALSE;
    $edit['excluded_roles[authenticated]'] = FALSE;
    $this->drupalGet('admin/config/system/simple-facebook-pixel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseContains($pixel_builder->getPixelNoScriptCode());

    $this->drupalLogout();
    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseContains($pixel_builder->getPixelNoScriptCode());
  }

}
