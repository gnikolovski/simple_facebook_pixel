<?php

namespace Drupal\Tests\simple_facebook_pixel\Functional;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form.
 *
 * @group simple_facebook_pixel
 */
class SettingsFormTest extends BrowserTestBase {

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
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    Vocabulary::create([
      'vid' => 'tags',
    ])->save();

    $this->user = $account = $this->drupalCreateUser([
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
    $this->assertSession()->elementExists('css', '#edit-exclude-admin-pages');
    $this->assertSession()->elementExists('css', '#edit-excluded-roles');
    $this->assertSession()->pageTextContains('PageView event is by default enabled on all pages. Other events can be enabled/disabled bellow.');
    $this->assertSession()->elementExists('css', '#edit-view-content-entities');
    $this->assertSession()->elementExists('css', '#edit-view-content-entities-nodearticle');
    $this->assertSession()->elementExists('css', '#edit-view-content-entities-nodepage');
    $this->assertSession()->elementExists('css', '#edit-view-content-entities-taxonomy-termtags');
    $this->assertSession()->elementExists('css', '#edit-complete-registration-enabled');
    $this->assertSession()->buttonExists('Save configuration');
  }

  /**
   * Tests if configuration is possible.
   */
  public function testConfiguration() {
    $this->drupalGet('admin/modules');
    $this->assertSession()->responseContains('admin/config/system/simple-facebook-pixel');

    $this->assertEquals(FALSE, $this->config('simple_facebook_pixel.settings')->get('pixel_enabled'));
    $this->assertEquals('', $this->config('simple_facebook_pixel.settings')->get('pixel_id'));
    $this->assertEquals([], $this->config('simple_facebook_pixel.settings')->get('excluded_roles'));

    $edit['pixel_enabled'] = FALSE;
    $edit['pixel_id'] = '456123';
    $edit['exclude_admin_pages'] = FALSE;
    $edit['excluded_roles[anonymous]'] = TRUE;
    $edit['excluded_roles[authenticated]'] = FALSE;
    $edit['complete_registration_enabled'] = FALSE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->assertEquals(FALSE, $this->config('simple_facebook_pixel.settings')->get('pixel_enabled'));
    $this->assertEquals('456123', $this->config('simple_facebook_pixel.settings')->get('pixel_id'));
    $this->assertEquals(FALSE, $this->config('simple_facebook_pixel.settings')->get('exclude_admin_pages'));
    $this->assertEquals(FALSE, $this->config('simple_facebook_pixel.settings')->get('complete_registration_enabled'));
    $user_roles = $this->user->getRoles(TRUE);
    $user_role = reset($user_roles);
    $roles = [
      'anonymous' => 'anonymous',
      'authenticated' => '0',
      $user_role => '0',
    ];
    $this->assertSame($roles, $this->config('simple_facebook_pixel.settings')->get('excluded_roles'));

    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '876321';
    $edit['exclude_admin_pages'] = TRUE;
    $edit['excluded_roles[anonymous]'] = FALSE;
    $edit['excluded_roles[authenticated]'] = TRUE;
    $edit['complete_registration_enabled'] = TRUE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->assertEquals(TRUE, $this->config('simple_facebook_pixel.settings')->get('pixel_enabled'));
    $this->assertEquals('876321', $this->config('simple_facebook_pixel.settings')->get('pixel_id'));
    $this->assertEquals(TRUE, $this->config('simple_facebook_pixel.settings')->get('exclude_admin_pages'));
    $this->assertEquals(TRUE, $this->config('simple_facebook_pixel.settings')->get('complete_registration_enabled'));
    $roles = [
      'authenticated' => 'authenticated',
      'anonymous' => '0',
      $user_role => '0',
    ];
    $this->assertSame($roles, $this->config('simple_facebook_pixel.settings')->get('excluded_roles'));

    $edit['pixel_enabled'] = TRUE;
    $edit['pixel_id'] = '876321';
    $edit['view_content_entities[node:article]'] = TRUE;
    $edit['view_content_entities[node:page]'] = FALSE;
    $edit['view_content_entities[taxonomy_term:tags]'] = TRUE;
    $this->drupalPostForm('admin/config/system/simple-facebook-pixel', $edit, 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $this->assertEquals(TRUE, $this->config('simple_facebook_pixel.settings')->get('pixel_enabled'));
    $this->assertEquals('876321', $this->config('simple_facebook_pixel.settings')->get('pixel_id'));
    $view_content_entities = [
      'node:article' => 'node:article',
      'taxonomy_term:tags' => 'taxonomy_term:tags',
      'node:page' => '0',
    ];
    $this->assertSame($view_content_entities, $this->config('simple_facebook_pixel.settings')->get('view_content_entities'));
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
