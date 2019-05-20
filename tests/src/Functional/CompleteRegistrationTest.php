<?php

namespace Drupal\Tests\simple_facebook_pixel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests CompleteRegistration event.
 *
 * @group simple_facebook_pixel
 */
class CompleteRegistrationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'simple_facebook_pixel',
  ];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configFactory = \Drupal::configFactory();
  }

  /**
   * Tests CompleteRegistration when enabled.
   */
  public function testUserCreationWhenEnabled() {
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('pixel_enabled', TRUE)
      ->set('pixel_id', '1234567890')
      ->set('complete_registration_enabled', TRUE)
      ->save();

    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your email address.'));

    /** @var \Drupal\simple_facebook_pixel\PixelBuilderServiceInterface $pixel_builder */
    $pixel_builder = \Drupal::service('simple_facebook_pixel.pixel_builder');

    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseContains('CompleteRegistration');

    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains('CompleteRegistration');
  }

  /**
   * Tests CompleteRegistration when disabled.
   */
  public function testUserCreationWhenDisabled() {
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('pixel_enabled', TRUE)
      ->set('pixel_id', '1234567890')
      ->set('complete_registration_enabled', FALSE)
      ->save();

    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your email address.'));

    /** @var \Drupal\simple_facebook_pixel\PixelBuilderServiceInterface $pixel_builder */
    $pixel_builder = \Drupal::service('simple_facebook_pixel.pixel_builder');

    $this->assertSession()->responseContains($pixel_builder->getPixelScriptCode());
    $this->assertSession()->responseNotContains('CompleteRegistration');
  }

}
