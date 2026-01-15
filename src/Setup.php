<?php

namespace MediaWiki\Extension\NSFileRepo;

class Setup {

	/**
	 * @return void
	 */
	public static function register() {
		if ( PHP_SAPI === 'cli' ) {
			// Avoid `--skip-config-validation` issues in context of `update.php`
			// Unfortunately there is no good way to know if in context of this particular maintenance script
			// Therefore we need to bail out in all CLI context.
			// HINT: In MW 1.43 `$wgIllegalFileChars` can still be altered, even though it is deprecated since 1.41
			// Future major releases of this extension need to solve this.
			return;
		}
		// Remove the default illegal char ':' - needed it to determine NS
		$GLOBALS['wgIllegalFileChars'] = str_replace( ":", "", $GLOBALS['wgIllegalFileChars'] );
	}
}
