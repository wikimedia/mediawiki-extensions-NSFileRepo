<?php
namespace MediaWiki\Extension\NSFileRepo\HookHandler;

use MediaWiki\Output\Hook\BeforePageDisplayHook;

class AddModules implements BeforePageDisplayHook {

	/**
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( $out->getTitle() && $out->getTitle()->isSpecial( 'Upload' ) ) {
			$out->addModules( 'ext.nsfilerepo.special.upload' );
		}
	}
}
