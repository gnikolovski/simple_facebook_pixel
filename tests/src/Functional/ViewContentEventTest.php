<?php

namespace Drupal\Tests\simple_facebook_pixel\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Tests View Content event.
 *
 * @group simple_facebook_pixel
 */
class ViewContentEventTest extends BrowserTestBase {

  use TaxonomyTestTrait;

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
   * The user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

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
   * Tests the case when ViewContent is enabled/disabled for nodes.
   */
  public function testForNodes() {
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('pixel_enabled', TRUE)
      ->set('pixel_id', '1234567890')
      ->set('view_content_entities.node:article', 'node:article')
      ->set('view_content_entities.node:page', 'node:page')
      ->save();

    // Create Article content type and two test nodes.
    $this->createContentType(['type' => 'article']);
    $this->createNode(['title' => 'Test article #1', 'type' => 'article']);
    $this->createNode(['title' => 'Test article #2', 'type' => 'article']);

    // Make sure that View Content is tracked.
    $this->drupalGet('/node/1');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test article #1","content_type":"article","content_ids":["1"]});');

    $this->drupalGet('/node/2');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test article #2","content_type":"article","content_ids":["2"]});');

    // Create Page content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Test page #3', 'type' => 'page']);
    $this->createNode(['title' => 'Test page #4', 'type' => 'page']);

    // Make sure that View Content is tracked.
    $this->drupalGet('/node/3');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test page #3","content_type":"page","content_ids":["3"]});');

    $this->drupalGet('/node/4');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test page #4","content_type":"page","content_ids":["4"]});');

    // Disable View Content event.
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('view_content_entities', [])
      ->save();

    // Make sure that View Content is not tracked.
    $this->drupalGet('/node/1');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test article #1","content_type":"article","content_ids":["1"]});');

    $this->drupalGet('/node/2');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test article #2","content_type":"article","content_ids":["2"]});');

    $this->drupalGet('/node/3');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test page #3","content_type":"page","content_ids":["3"]});');

    $this->drupalGet('/node/4');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test page #4","content_type":"page","content_ids":["4"]});');

    // Enable View Content event, but just for Articles.
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('pixel_id', '1234567890')
      ->set('view_content_entities.node:article', 'node:article')
      ->save();

    // Make sure that View Content is tracked for Articles, but not for Pages.
    $this->drupalGet('/node/1');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test article #1","content_type":"article","content_ids":["1"]});');

    $this->drupalGet('/node/3');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test page #3","content_type":"page","content_ids":["3"]});');

    // Log out and test again.
    $this->drupalLogout();
    $this->drupalGet('/node/1');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test article #1","content_type":"article","content_ids":["1"]});');

    $this->drupalGet('/node/3');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test page #3","content_type":"page","content_ids":["3"]});');

    // Disable View Content event.
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('view_content_entities', [])
      ->save();

    // Make sure that View Content is not tracked.
    $this->drupalGet('/node/1');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test article #1","content_type":"article","content_ids":["1"]});');
  }

  /**
   * Tests the case when ViewContent is enabled/disabled for taxonomy terms.
   */
  public function testForTaxonomyTerms() {
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('pixel_enabled', TRUE)
      ->set('pixel_id', '1234567890')
      ->set('view_content_entities.taxonomy_term:tags', 'taxonomy_term:tags')
      ->set('view_content_entities.taxonomy_term:categories', 'taxonomy_term:categories')
      ->save();

    // Create Tags vocabulary and two test terms.
    $tags_vocabulary = $this->createVocabulary();
    $this->createTerm($tags_vocabulary, ['vid' => 'tags', 'name' => 'Test term #1']);
    $this->createTerm($tags_vocabulary, ['vid' => 'tags', 'name' => 'Test term #2']);

    // Make sure that View Content is tracked.
    $this->drupalGet('/taxonomy/term/1');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test term #1","content_type":"tags","content_ids":["1"]});');

    $this->drupalGet('/taxonomy/term/2');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test term #2","content_type":"tags","content_ids":["2"]});');

    // Create Categories vocabulary and two test terms.
    $categories_vocabulary = $this->createVocabulary();
    $this->createTerm($categories_vocabulary, ['vid' => 'categories', 'name' => 'Test term #3']);
    $this->createTerm($categories_vocabulary, ['vid' => 'categories', 'name' => 'Test term #4']);

    // Make sure that View Content is tracked.
    $this->drupalGet('/taxonomy/term/3');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test term #3","content_type":"categories","content_ids":["3"]});');

    $this->drupalGet('/taxonomy/term/4');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test term #4","content_type":"categories","content_ids":["4"]});');

    // Disable View Content event.
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('view_content_entities', [])
      ->save();

    // Make sure that View Content is not tracked.
    $this->drupalGet('/taxonomy/term/1');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test term #1","content_type":"tags","content_ids":["1"]});');

    $this->drupalGet('/taxonomy/term/2');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test term #2","content_type":"tags","content_ids":["2"]});');

    $this->drupalGet('/taxonomy/term/3');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test term #3","content_type":"categories","content_ids":["3"]});');

    $this->drupalGet('/taxonomy/term/4');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test term #4","content_type":"categories","content_ids":["4"]});');

    // Enable View Content event, but just for Tags.
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('pixel_id', '1234567890')
      ->set('view_content_entities.taxonomy_term:tags', 'taxonomy_term:tags')
      ->save();

    // Make sure that View Content is tracked for Tags, but not for Categories.
    $this->drupalGet('/taxonomy/term/1');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test term #1","content_type":"tags","content_ids":["1"]});');

    $this->drupalGet('/taxonomy/term/3');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test term #3","content_type":"categories","content_ids":["3"]});');

    // Log out and test again.
    $this->drupalLogout();
    $this->drupalGet('/taxonomy/term/1');
    $this->assertSession()->responseContains('fbq("track", "ViewContent", {"content_name":"Test term #1","content_type":"tags","content_ids":["1"]});');

    $this->drupalGet('/taxonomy/term/3');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test term #3","content_type":"categories","content_ids":["3"]});');

    // Disable View Content event.
    $this->configFactory->getEditable('simple_facebook_pixel.settings')
      ->set('view_content_entities', [])
      ->save();

    // Make sure that View Content is not tracked.
    $this->drupalGet('/taxonomy/term/1');
    $this->assertSession()->responseNotContains('fbq("track", "ViewContent", {"content_name":"Test term #1","content_type":"tags","content_ids":["1"]});');
  }

}
