<?php

namespace Drupal\Tests\simple_facebook_pixel\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the View Content event.
 *
 * @group simple_facebook_pixel
 */
class ViewContentEventTest extends BrowserTestBase {

  use NodeCreationTrait;

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

    $this->user = $this->drupalCreateUser([
      'administer simple facebook pixel',
    ]);
    $this->drupalLogin($this->user);

    $this->pixelBuilder = \Drupal::service('simple_facebook_pixel.pixel_builder');
    $this->configFactory = \Drupal::configFactory();
  }

  /**
   * Tests the case when ViewContent is not enabled.
   */
  public function testNotEnabled() {
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('pixel_id', '1234567890')
      ->save();

    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains('ViewContent');
  }

  /**
   * Tests the case when ViewContent is enabled for nodes.
   */
  public function testEnabledForNodes() {
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('pixel_id', '1234567890')
      ->set('view_content_entities', ['node:article' => 'node:article'])
      ->set('view_content_entities', ['node:page' => 'node:page'])
      ->save();

    // Create Article content type and three test node.
    $this->createContentType(['type' => 'article']);
    $this->createNode(['title' => 'Test article #1', 'type' => 'article']);
    $this->createNode(['title' => 'Test article #2', 'type' => 'article']);
    $this->createNode(['title' => 'Test article #3', 'type' => 'article']);

    $this->drupalGet('node/1');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test article #1","content_type":"article","content_ids":["1"]});');

    $this->drupalGet('node/2');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test article #2","content_type":"article","content_ids":["2"]});');

    $this->drupalGet('node/3');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test article #3","content_type":"article","content_ids":["3"]});');

    // Create Page content type and three test node.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Test page #1', 'type' => 'page']);
    $this->createNode(['title' => 'Test page #2', 'type' => 'page']);
    $this->createNode(['title' => 'Test page #3', 'type' => 'page']);

    $this->drupalGet('node/4');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test page #1","content_type":"page","content_ids":["4"]});');

    $this->drupalGet('node/5');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test page #2","content_type":"page","content_ids":["5"]});');

    $this->drupalGet('node/6');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test page #3","content_type":"page","content_ids":["6"]});');

    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('pixel_id', '1234567890')
      ->set('view_content_entities', [])
      ->save();

    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test page #1","content_type":"page","content_ids":["1"]});');

    $this->drupalGet('node/2');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test page #2","content_type":"page","content_ids":["2"]});');

    $this->drupalGet('node/3');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test page #3","content_type":"page","content_ids":["3"]});');
  }

}
