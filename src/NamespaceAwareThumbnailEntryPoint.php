<?php

namespace MediaWiki\Extension\NSFileRepo;

use MediaWiki\FileRepo\ThumbnailEntryPoint;
use MediaWiki\Title\Title;

/**
 * Extends MediaWiki's ThumbnailEntryPoint to enforce namespace-based access control
 * for namespace-prefixed files (e.g. "Project:Image.png") on publicly readable wikis.
 *
 * Core's ThumbnailEntryPoint::maybeDenyAccess() only checks permissions when the
 * anonymous group lacks the 'read' right (i.e. private wikis). On public wikis with
 * namespace-restricted files, it skips the check entirely — the same gap that motivated
 * nsfr_img_auth.php. This class closes that gap for thumbnail delivery via thumb.php.
 *
 * @see nsfr_thumb.php The web entry point.
 */
class NamespaceAwareThumbnailEntryPoint extends ThumbnailEntryPoint {

	/**
	 * @inheritDoc
	 */
	protected function handleRequest() {
		$params = $this->getRequest()->getQueryValuesOnly();

		// Temp files are not stored with namespace-based paths; no namespace check needed.
		if ( !empty( $params['temp'] ) ) {
			parent::handleRequest();
			return;
		}

		// On private wikis the anonymous group lacks 'read', so core's maybeDenyAccess()
		// already fires getUserPermissionsErrors — which includes NSFileRepo's Authenticate
		// hook. No additional check is needed in that case.
		$permissionLookup = $this->getServiceContainer()->getGroupPermissionsLookup();
		if ( !$permissionLookup->groupHasPermission( '*', 'read' ) ) {
			parent::handleRequest();
			return;
		}

		// Public wiki: enforce namespace-based access control ourselves, because core's
		// maybeDenyAccess() will skip all permission checks for publicly readable wikis.
		$fileName = $params['f'] ?? '';
		if ( $fileName === '' ) {
			parent::handleRequest();
			return;
		}

		// Archived files: format is <timestamp>!<name>. Mirror how streamThumb() parses it.
		if ( !empty( $params['archived'] ) ) {
			$bits = explode( '!', $fileName, 2 );
			if ( count( $bits ) !== 2 ) {
				// Malformed format — let core handle it (it will return 404).
				parent::handleRequest();
				return;
			}
			$fileName = $bits[1];
		}

		// Mirror core's normalisation step from streamThumb().
		$fileName = strtr( $fileName, '\\/', '__' );

		// Derive the namespace context for this file by parsing the filename as a plain title.
		// 'Project:Image.png' → Title in the Project namespace.
		// 'Image.png'         → Title in NS_MAIN.
		// NSFileRepo only restricts NS_MAIN and namespaces at or above the configured threshold.
		$nsTitle = Title::newFromText( $fileName );
		if ( !$nsTitle instanceof Title ) {
			// Unparseable filename — let core handle it (it will return 404).
			parent::handleRequest();
			return;
		}

		$nsConfig = new Config();
		$ns = $nsTitle->getNamespace();
		$needsCheck = ( $ns === NS_MAIN || $ns >= $nsConfig->get( Config::CONFIG_THRESHOLD ) );

		if ( !$needsCheck ) {
			// File lives in a built-in namespace that NSFileRepo does not restrict.
			parent::handleRequest();
			return;
		}

		// Perform the access check against the actual File-namespace title rather than the
		// derived namespace title. This ensures that all getUserPermissionsErrors hooks fire
		// correctly — including NSFileRepo's own Authenticate hook and any WhitelistRead
		// entries that reference the file by its prefixed File: title.
		$fileTitle = Title::makeTitleSafe( NS_FILE, $fileName );
		if ( !$fileTitle instanceof Title ) {
			parent::handleRequest();
			return;
		}

		$authority = $this->getContext()->getAuthority();
		if ( !$authority->authorizeRead( 'read', $fileTitle ) ) {
			$this->thumbErrorText(
				403,
				'Access denied. You do not have permission to access the source file.'
			);
			return;
		}

		// The response now depends on the caller's identity; prevent shared (CDN) caching.
		// We cannot call the private vary('Cookie') method inherited from ThumbnailEntryPoint,
		// so Cache-Control: private (which already prevents CDN caching) is used instead.
		$this->header( 'Cache-Control: private' );

		parent::handleRequest();
	}
}
