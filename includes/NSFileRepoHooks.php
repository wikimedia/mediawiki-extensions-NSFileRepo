<?php

use MediaWiki\MediaWikiServices;

class NSFileRepoHooks {
	public static function register() {
		require_once( __DIR__.'/DefaultSettings.php' );
		$GLOBALS['wgLocalFileRepo']['class'] = "NSLocalRepo";
	}

	/**
	 * Add JavaScript
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return boolean true
	 */
	public static function onBeforePageDisplay( &$out, &$skin ) {
		if ( $out->getTitle() && $out->getTitle()->isSpecial( 'Upload' ) ) {
			$out->addModules( 'ext.nsfilerepo.special.upload' );
		}

		return true;
	}

	/**
	 * @param $path
	 * @param $name
	 * @param $filename
	 * @return bool
	 */
	public static function onImgAuthBeforeCheckFileExists( &$path, &$name, &$filename ) {
		$nsfrhelper = new NSFileRepoHelper();
		$title = $nsfrhelper->getTitleFromPath( $path );
		if( $title instanceof Title && $title->getNamespace() !== NS_MAIN ) {
			//Not using "$title->getPrefixedDBKey()" because "$wgCapitalLinkOverrides[NS_FILE]" may be "false"
			$name = $title->getNsText() . ':' . $name;
		}

		return true;
	}

	/**
	 * Checks if the destination file name contains a valid namespace prefix
	 * @param string $destName
	 * @param string $tempPath
	 * @param string $error
	 * @return bool
	 */
	public static function onUploadVerification( $destName, $tempPath, &$error ) {
		$title = Title::newFromText( $destName );
		if( strpos( $title->getText(), ':' ) !== false ) { //There is a colon in the name but it was not a valid namespace prefix!
			$error = 'illegal-filename';
			return false;
		}
		return true;
	}
}
