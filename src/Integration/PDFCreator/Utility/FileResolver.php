<?php

namespace MediaWiki\Extension\NSFileRepo\Integration\PDFCreator\Utility;

use DOMElement;
use File;
use MediaWiki\Extension\PDFCreator\Utility\FileResolver as PDFCreatorFileResolver;
use MediaWiki\Extension\PDFCreator\Utility\ThumbFilenameExtractor;

class FileResolver extends PDFCreatorFileResolver {

	/**
	 * @inheritDoc
	 */
	public function execute( DOMElement $element, string $attrSrc = 'src' ): ?File {
		$src = $element->getAttribute( $attrSrc );

		// When wgThumbnailScriptPath is set, thumbnail src attributes are query-based:
		// e.g. /w/nsfr_thumb.php?f=Project%3AImage.png&width=120
		// The path-stripping logic below strips the query string early, making filename
		// extraction impossible. Handle these URLs explicitly before that happens.
		$file = $this->resolveFromThumbScript( $src );
		if ( $file !== null ) {
			return $file;
		}

		$pathsForRegex = [
			$this->config->get( 'Server' ),
			$this->config->get( 'UploadPath' ),
			$this->config->get( 'ScriptPath' )
		];

		if ( strpos( $src, '?' ) ) {
			$src = substr( $src, 0, strpos( $src, '?' ) );
		}

		// Extracting the filename
		foreach ( $pathsForRegex as $path ) {
			$src = preg_replace( "#" . preg_quote( $path, "#" ) . "#", '', $src );
			$src = preg_replace( '/(&.*)/', '', $src );
		}

		$srcUrl = urldecode( $src );
		$srcFilename = wfBaseName( $srcUrl );

		$thumbFilenameExtractor = new ThumbFilenameExtractor();
		$isThumb = $thumbFilenameExtractor->isThumb( $srcUrl );
		if ( $isThumb ) {
			// HINT: Thumbname-to-filename-conversion taken from includes/Upload/UploadBase.php
			// Check for filenames like 50px- or 180px-, these are mostly thumbnails
			$srcFilename = $thumbFilenameExtractor->extractFilename( $srcUrl );
		}

		/**
		 * Check url for
		 * - thumb
		 * - custom namespace
		 * - archived file
		 */
		$matches = [];
		$file = null;
		preg_match( '#(\/thumb)?\/(\d{4})\/[a-z0-9]{1}\/[a-z0-9]{2}\/(.*)#', $srcUrl, $matches );
		if ( !empty( $matches ) ) {
			$namespace = $matches[2];
			$dummyTitle = $this->titleFactory->newFromText( 'Dummy', $namespace );
			$fileTitle = $this->titleFactory->newFromText( 'File:' . $dummyTitle->getNsText() . ':' . $srcFilename );
			$file = $this->repoGroup->findFile( $fileTitle );
		} else {
			preg_match( '#\/([a-z0-9])\/([a-z0-9]{2})\/(.*)#', $srcUrl, $matches );
			if ( !empty( $matches ) ) {
				$fileTitle = $this->titleFactory->newFromText( $srcFilename, NS_FILE );
				$file = $this->repoGroup->findFile( $fileTitle );
			}
		}

		if ( !$file ) {
			$file = $this->findArchivedFile( $srcFilename );
		}

		return $file ?: null;
	}

	/**
	 * Attempt to resolve a file from a ThumbnailScriptPath-style URL
	 * (e.g. /w/nsfr_thumb.php?f=Project%3AImage.png&width=120).
	 *
	 * When $wgThumbnailScriptPath is configured, all thumbnail src attributes
	 * are query-based rather than path-based, so the path-stripping logic in
	 * execute() cannot extract a filename from them. This method handles that
	 * case by reading the 'f' query parameter directly.
	 *
	 * @param string $src Raw value of the src/href attribute
	 * @return File|null
	 */
	protected function resolveFromThumbScript( string $src ): ?File {
		$thumbScriptPath = $this->config->get( 'ThumbnailScriptPath' );
		if ( !$thumbScriptPath || strpos( $src, '?' ) === false ) {
			return null;
		}

		// Compare only the path component so absolute URLs (with server prefix) also match.
		$srcPath = parse_url( $src, PHP_URL_PATH ) ?? '';
		$scriptPath = parse_url( $thumbScriptPath, PHP_URL_PATH ) ?? $thumbScriptPath;
		if ( $srcPath !== $scriptPath ) {
			return null;
		}

		$query = parse_url( $src, PHP_URL_QUERY ) ?? '';
		parse_str( $query, $params );
		$fileName = $params['f'] ?? '';
		if ( $fileName === '' ) {
			return null;
		}

		// Archived files have the format <timestamp>!<name> and need a different lookup.
		if ( !empty( $params['archived'] ) ) {
			return $this->findArchivedFile( $fileName );
		}

		// Build a proper NS_FILE title for the filename.
		// Prefixing with 'File:' ensures the result is always in NS_FILE even for
		// namespace-prefixed filenames, because MediaWiki treats the text after the
		// first recognised namespace prefix as the DB key:
		//   'File:Project:Image.png' → NS_FILE, DB key 'Project:Image.png'  (namespace-prefixed)
		//   'File:Image.png'         → NS_FILE, DB key 'Image.png'           (plain)
		// Using newFromText( $fileName, NS_FILE ) would be wrong for 'Project:Image.png'
		// because 'Project:' would be resolved as a namespace, yielding NS_PROJECT.
		$fileTitle = $this->titleFactory->newFromText( 'File:' . $fileName );
		if ( $fileTitle === null ) {
			return null;
		}

		return $this->repoGroup->findFile( $fileTitle ) ?: null;
	}
}
