<?php

namespace Drupal\Tests\simple_facebook_pixel\Unit;

use Drupal\simple_facebook_pixel\PixelBuilderService;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\simple_facebook_pixel\PixelBuilderService
 * @group simple_facebook_pixel
 */
class PixelBuilderServiceTest extends UnitTestCase {

  /**
   * The Pixel Builder service.
   *
   * @var \Drupal\simple_facebook_pixel\PixelBuilderService
   */
  protected $pixelBuilderService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->pixelBuilderService = new PixelBuilderService();
  }

  /**
   * Tests script code.
   */
  public function testScriptCode() {
    $this->assertContains('123789', $this->pixelBuilderService->getPixelScriptCode(123789));
  }

  /**
   * Tests noscript code.
   */
  public function testNoScriptCode() {
    $this->assertContains('890123', $this->pixelBuilderService->getPixelNoScriptCode(890123));
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    unset($this->pixelBuilderService);
  }

}
