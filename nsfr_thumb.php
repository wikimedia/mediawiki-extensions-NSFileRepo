<?php
/**
 * THIS SCRIPT IS MODELLED AFTER MEDIAWIKI CORE thumb.php ENTRY POINT
 * altered to enforce namespace-based access control for namespace-prefixed files,
 * even on otherwise publicly readable wikis.
 *
 * @see MediaWiki\Extension\NSFileRepo\NamespaceAwareThumbnailEntryPoint The implementation.
 */

use MediaWiki\Context\RequestContext;
use MediaWiki\EntryPointEnvironment;
use MediaWiki\Extension\NSFileRepo\NamespaceAwareThumbnailEntryPoint;
use MediaWiki\MediaWikiServices;

define( 'MW_NO_OUTPUT_COMPRESSION', 1 );
define( 'MW_ENTRY_POINT', 'thumb' );
require __DIR__ . '/includes/WebStart.php';

( new NamespaceAwareThumbnailEntryPoint(
	RequestContext::getMain(),
	new EntryPointEnvironment(),
	MediaWikiServices::getInstance()
) )->run();
