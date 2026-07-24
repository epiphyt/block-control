<?php
/**
 * Base test case wiring Brain Monkey into PHPUnit.
 *
 * @package epiphyt\Block_Control
 */
declare( strict_types = 1 );

namespace epiphyt\Block_Control\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnit_Test_Case;

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
	}

	/**
	 * Tear down Brain Monkey after each test.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();

		parent::tearDown();
	}
}
