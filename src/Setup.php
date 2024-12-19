<?php

namespace MediaWiki\Extension\NSFileRepo;

class Setup {

	/**
	 * @return void
	 */
	public static function register() {
		$GLOBALS['wgLocalFileRepo']['class'] = NamespaceLocalRepo::class;

		if ( PHP_SAPI !== 'cli' ) {
			// Remove the default illegal char ':' - needed it to determine NS
			$GLOBALS['wgIllegalFileChars'] = str_replace( ":", "", $GLOBALS['wgIllegalFileChars'] );
		}
	}
}
