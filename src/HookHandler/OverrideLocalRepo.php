<?php

namespace MediaWiki\Extension\NSFileRepo\HookHandler;

use MediaWiki\Extension\NSFileRepo\NamespaceLocalRepo;
use MediaWiki\Hook\MediaWikiServicesHook;

class OverrideLocalRepo implements MediaWikiServicesHook {

	/**
	 * @inheritDoc
	 */
	public function onMediaWikiServices( $services ) {
		$GLOBALS['wgLocalFileRepo']['class'] = NamespaceLocalRepo::class;
	}
}
