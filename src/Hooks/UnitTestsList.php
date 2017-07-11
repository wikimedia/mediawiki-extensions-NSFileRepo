<?php

namespace NSFileRepo\Hooks;

class UnitTestsList {

	/**
	 * Register PHP Unit Tests with MediaWiki framework
	 * @param array $paths
	 * @return boolean
	 */
	public static function handle( &$paths ) {
		$paths[] =  dirname( dirname( __DIR__ ) ) . '/tests/phpunit/';
		return true;
	}
}