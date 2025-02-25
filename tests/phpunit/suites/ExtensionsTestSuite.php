<?php

use MediaWiki\HookContainer\HookRunner;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestSuite;
use SebastianBergmann\FileIterator\Facade;

/**
 * This test suite runs unit tests registered by extensions.
 * See https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList for details of
 * how to register your tests.
 */

class ExtensionsTestSuite extends TestSuite {
	public function __construct() {
		parent::__construct();

		$paths = [];
		// Autodiscover extension unit tests
		$registry = ExtensionRegistry::getInstance();
		foreach ( $registry->getAllThings() as $info ) {
			$paths[] = dirname( $info['path'] ) . '/tests/phpunit';
		}
		// Extensions can return a list of files or directories
		( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) )->onUnitTestsList( $paths );
		foreach ( array_unique( $paths ) as $path ) {
			if ( is_dir( $path ) ) {
				// If the path is a directory, search for test cases.
				// @since 1.24
				$suffixes = [ 'Test.php' ];
				$fileIterator = new Facade();
				$matchingFiles = $fileIterator->getFilesAsArray( $path, $suffixes );
				$this->addTestFiles( $matchingFiles );
			} elseif ( is_file( $path ) ) {
				// Add a single test case or suite class
				$this->addTestFile( $path );
			}
		}
		if ( !$paths ) {
			$this->addTest( new DummyExtensionsTest( 'testNothing' ) );
		}
	}

	public static function suite() {
		return new self;
	}
}

/**
 * Needed to avoid warnings like 'No tests found in class "ExtensionsTestSuite".'
 * when no extensions with tests are used.
 */
class DummyExtensionsTest extends MediaWikiIntegrationTestCase {
	/**
	 * @coversNothing
	 */
	public function testNothing() {
		$this->assertTrue( true );
	}
}
