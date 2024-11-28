<?php

namespace MediaWiki\Extension\NSFileRepo;

class Setup {

	/**
	 * @return void
	 */
	public static function register() {
		$GLOBALS['wgLocalFileRepo']['class'] = NamespaceLocalRepo::class;

		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			// Remove the default illegal char ':' - needed it to determine NS
			$GLOBALS['wgIllegalFileChars'] = str_replace( ":", "", $GLOBALS['wgIllegalFileChars'] );
		}
	}
}
