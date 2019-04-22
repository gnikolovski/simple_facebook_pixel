<?php

namespace Drupal\Tests\simple_facebook_pixel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Facebook Pixel snippet.
 *
 * @group simple_facebook_pixel
 */
class SnippetTest extends BrowserTestBase {

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

    $this->user = $this->drupalCreateUser([
      'administer simple facebook pixel',
    ]);
    $this->drupalLogin($this->user);

    $this->pixelBuilder = \Drupal::service('simple_facebook_pixel.pixel_builder');
  }

  /**
   * Tests snippet insertion when Facebook Pixel is missing.
   */
  public function testFacebookPixelIdMissing() {
    $this->drupalGet('');
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelScriptCode('789012'));
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelNoScriptCode('789012'));

    $this->drupalLogout();
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelScriptCode('789012'));
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelNoScriptCode('789012'));
  }

  /**
   * Tests snippet insertion when Facebook Pixel is disabled.
   */
  public function testFacebookPixelDisabled() {
    $edit['pixel_enabled'] = FALSE;
    $edit['pixel_id'] = '789012';
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('');
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelScriptCode('789012'));
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelNoScriptCode('789012'));

    $this->drupalLogout();
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelScriptCode('789012'));
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelNoScriptCode('789012'));
  }

  /**
   * Tests snippet insertion for all users.
   */
  public function testFacebookPixelEnabledForAllUsers() {
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelNoScriptCode('567123'));

    $this->drupalLogout();
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelNoScriptCode('567123'));
  }

  /**
   * Tests snippet exclusion for selected roles.
   */
  public function testFacebookPixelExclusionForRoles() {
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $edit['excluded_roles[anonymous]'] = TRUE;
    $edit['excluded_roles[authenticated]'] = FALSE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelNoScriptCode('567123'));

    $this->drupalLogout();
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelNoScriptCode('567123'));

    $this->drupalLogin($this->user);
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $edit['excluded_roles[anonymous]'] = FALSE;
    $edit['excluded_roles[authenticated]'] = TRUE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelNoScriptCode('567123'));

    $this->drupalLogout();
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelNoScriptCode('567123'));

    $this->drupalLogin($this->user);
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $edit['excluded_roles[anonymous]'] = TRUE;
    $edit['excluded_roles[authenticated]'] = TRUE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelNoScriptCode('567123'));

    $this->drupalLogout();
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseNotContains($this->pixelBuilder->getPixelNoScriptCode('567123'));

    $this->drupalLogin($this->user);
    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '567123';
    $edit['excluded_roles[anonymous]'] = FALSE;
    $edit['excluded_roles[authenticated]'] = FALSE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelNoScriptCode('567123'));

    $this->drupalLogout();
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelScriptCode('567123'));
    $this->assertSession()->responseContains($this->pixelBuilder->getPixelNoScriptCode('567123'));
  }

}
