<?php
/**
 * Base test case wiring Brain Monkey into PHPUnit.
 *
 * @package epiphyt\Block_Control
 */
declare( strict_types = 1 );

namespace epiphyt\Block_Control\Tests;

use Brain\Monkey;
use epiphyt\Block_Control\Viewport;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnit_Test_Case;
use ReflectionClass;

/**
 * Shared base class that boots and tears down Brain Monkey around every test.
 */
abstract class Test_Case extends PHPUnit_Test_Case {
	use MockeryPHPUnitIntegration;

	/**
	 * Set up Brain Monkey before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		Monkey\setUp();

		// the presets are cached for the whole request, which would leak the
		// stubbed settings of one test into the next one
		( new ReflectionClass( Viewport::class ) )->setStaticPropertyValue( 'presets', null );
	}

	/**
	 * Tear down Brain Monkey after each test.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();

		parent::tearDown();
	}
}
