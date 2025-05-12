<?php

namespace MediaWiki\Extension\NSFileRepo\HookHandler;

use MediaWiki\Extension\NSFileRepo\Config;
use MediaWiki\Hook\UploadForm_BeforeProcessingHook;
use MediaWiki\Hook\UploadVerifyFileHook;
use MediaWiki\Title\TitleFactory;

class HandleUpload implements UploadForm_BeforeProcessingHook, UploadVerifyFileHook {

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var Config
	 */
	private $nsfrConfig;

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
		$this->nsfrConfig = new Config();
	}

	/**
	 * @inheritDoc
	 */
	public function onUploadForm_BeforeProcessing( $upload ) {
		$title = $this->titleFactory->newFromText( $upload->mDesiredDestName );
		if ( $title === null ) {
			return true;
		}
		if ( $title->getNamespace() < $this->nsfrConfig->get( 'NamespaceThreshold' ) ) {
			$upload->mDesiredDestName = preg_replace( "/:/", '-', $upload->mDesiredDestName );
		} else {
			$bits = explode( ':', $upload->mDesiredDestName );
			$ns = array_shift( $bits );
			$upload->mDesiredDestName = $ns . ":" . implode( "-", $bits );
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onUploadVerifyFile( $upload, $mime, &$error ) {
		$destName = $upload->getDesiredDestName();
		if ( !$destName ) {
			return;
		}
		$title = $this->titleFactory->newFromText( $destName );
		// There is a colon in the name, but it was not a valid namespace prefix!
		if ( !$title || str_contains( $title->getText(), ':' ) ) {
			$error = 'illegal-filename';
			return false;
		}
	}
}
