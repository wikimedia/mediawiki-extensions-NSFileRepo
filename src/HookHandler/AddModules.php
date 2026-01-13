<?php
namespace MediaWiki\Extension\NSFileRepo\HookHandler;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\NSFileRepo\Config;
use MediaWiki\Extension\NSFileRepo\NamespaceList;
use MediaWiki\SpecialPage\Hook\SpecialPageBeforeExecuteHook;
use MediaWiki\Specials\SpecialUpload;

class AddModules implements SpecialPageBeforeExecuteHook {

	/**
	 * @inheritDoc
	 */
	public function onSpecialPageBeforeExecute( $special, $subpage ) {
		$script = file_get_contents( __DIR__ . '/../../resources/ext.nsfilerepo.special.upload.js' );
		if ( $special && $special instanceof SpecialUpload ) {
			$special->getOutput()->addInlineScript( $script );
		}
		if ( $special && class_exists( '\PFFormEdit' ) && $special instanceof \PFFormEdit ) {
			// temporarily allow iframe in x-frame-options
			$GLOBALS['wgBreakFrames'] = false;

			$context = RequestContext::getMain();
			$namespaceList = new NamespaceList(
				$context->getUser(),
				new Config(),
				$context->getLanguage()
			);

			$namespaces = [];
			foreach ( $namespaceList->getEditable() as $nsId => $namespace ) {
				$displayName = $namespace->getDisplayName();
				$namespaces[$displayName] = $namespace->getCanonicalName();
				if ( $nsId === NS_MAIN ) {
					$namespaces[$displayName] = '';
				}
			}

			$special->getOutput()->addJsConfigVars(
				'nsfilerepoNamespaces',
				$namespaces
			);

			$special->getOutput()->addModules( [ 'ext.nsfilerepo.special.upload' ] );
		}
	}

}
