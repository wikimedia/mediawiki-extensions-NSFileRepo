<?php

namespace MediaWiki\Extension\NSFileRepo\Integration\PDFCreator\Utility;

use DOMElement;
use File;
use MediaWiki\Config\Config;
use MediaWiki\Extension\PDFCreator\Utility\ThumbFilenameExtractor;
use MediaWiki\Title\TitleFactory;
use RepoGroup;

class FileResolver {

	/** @var Config */
	private $config;

	/** @var RepoGroup */
	private $repoGroup;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param Config $config
	 * @param RepoGroup $repoGroup
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		Config $config, RepoGroup $repoGroup, TitleFactory $titleFactory
	) {
		$this->config = $config;
		$this->repoGroup = $repoGroup;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param DOMElement $element
	 * @param string $attrSrc
	 * @return File|null
	 */
	public function execute( DOMElement $element, string $attrSrc = 'src' ): ?File {
		$pathsForRegex = [
			$this->config->get( 'Server' ),
			$this->config->get( 'ThumbnailScriptPath' ) . "?f=",
			$this->config->get( 'UploadPath' ),
			$this->config->get( 'ScriptPath' )
		];

		$src = $element->getAttribute( $attrSrc );
		if ( strpos( $src, '?' ) ) {
			$src = substr( $src, 0, strpos( $src, '?' ) );
		}
		$srcUrl = urldecode( $src );

		// Extracting the filename
		foreach ( $pathsForRegex as $path ) {
			$srcUrl = preg_replace( "#" . preg_quote( $path, "#" ) . "#", '', $srcUrl );
			$srcUrl = preg_replace( '/(&.*)/', '', $srcUrl );
		}

		$srcFilename = wfBaseName( $srcUrl );

		$thumbFilenameExtractor = new ThumbFilenameExtractor();
		$isThumb = $thumbFilenameExtractor->isThumb( $srcUrl );
		if ( $isThumb ) {
			// HINT: Thumbname-to-filename-conversion taken from includes/Upload/UploadBase.php
			// Check for filenames like 50px- or 180px-, these are mostly thumbnails
			$srcFilename = $thumbFilenameExtractor->extractFilename( $srcUrl );
		}

		$matches = [];
		preg_match( '#(\/thumb)?\/(\d{4})\/[a-z0-9]{1}\/[a-z0-9]{2}\/(.*)#', $srcUrl, $matches );
		if ( !empty( $matches ) ) {
			$namespace = $matches[2];
			$dummyTitle = $this->titleFactory->newFromText( 'Dummy', $namespace );
			$srcFilename = $dummyTitle->getNsText() . ':' . $srcFilename;
			$fileTitle = $this->titleFactory->newFromText( 'File:' . $srcFilename );
			$file = $this->repoGroup->findFile( $fileTitle );
		} else {
			preg_match( '#\/([a-z0-9])\/([a-z0-9]{2})\/(.*)#', $srcUrl, $matches );
			if ( !empty( $matches ) ) {
				$fileTitle = $this->titleFactory->newFromText( $srcFilename, NS_FILE );
				$file = $this->repoGroup->findFile( $fileTitle );
			}
		}

		return $file ?: null;
	}
}
