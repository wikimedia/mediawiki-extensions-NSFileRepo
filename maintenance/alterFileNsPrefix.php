<?php

require_once dirname( __DIR__, 3 ) . '/maintenance/Maintenance.php';

use MediaWiki\Maintenance\Maintenance;
use MediaWiki\Title\Title;

/**
 * This script can find files named with a old namespace prefix
 * and can alter the database to use a new namespace prefix instead.
 */
class AlterFileNsPrefix extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'NSFileRepo' );

		$GLOBALS['wgIllegalFileChars'] = str_replace( ":", "", $GLOBALS['wgIllegalFileChars'] );
		$this->addOption( 'old-ns', 'Old namespace prefix to find in file names.', true, true );
		$this->addOption( 'new-ns', 'New namespace prefix to replace.'
			. 'If not provided, the script will merely list file names.', false, true );
		$this->addOption( 'quick', 'Skip count down before replacement happens.' );
	}

	public function execute() {
		$dbr = $this->getDB( DB_REPLICA );
		$dbw = $this->getDB( DB_PRIMARY );
		$oldNs = $this->getOption( 'old-ns' );
		$quotedOldNs = $dbr->addQuotes( $oldNs . ':%' );

		$res = $dbr->selectRowCount( 'image', '*', [ 'img_name LIKE ' . $quotedOldNs ] );
		$this->output( "Found {$res} files with prefix '{$oldNs}:'\n" );
		if ( $res === 0 ) {
			$this->output( "No files found. Exiting.\n" );
			return;
		}
		$res = $dbr->select( 'image', [ 'img_name' ], [ 'img_name LIKE ' . $quotedOldNs ] );
		foreach ( $res as $row ) {
			$this->output( $row->img_name . "\n" );
		}

		if ( !$this->hasOption( 'new-ns' ) ) {
			$this->output( "No new namespace prefix provided. Exiting.\n" );
			return;
		}
		if ( !$this->hasOption( 'quick' ) ) {
			$this->output( "Dangerous: Database tables will be irreversibly altered. Backup first!\n" );
			$this->output( "Abort with ctrl + c, or the operation starts in seconds...\n" );
			$this->countDown( 9 );
		}
		$newNs = $this->getOption( 'new-ns' );
		$newNs .= ( $newNs == '' ) ? '' : ':';

		$imageCount = 0;
		$res = $dbr->select( 'image', [ 'img_name' ], [ 'img_name LIKE ' . $quotedOldNs ] );
		foreach ( $res as $row ) {
			$oldName = $row->img_name;
			$newName = preg_replace( '/^' . preg_quote( $oldNs ) . ':/', $newNs, $oldName );
			$title = Title::makeTitleSafe( NS_FILE, $newName );
			if ( $title === null ) {
				$this->error( "Could not create a valid title for: {$newName}, skipping.\n" );
				continue;
			}
			$newName = $title->getDBkey();
			$this->output( "image name: {$oldName} -> {$newName}\n" );
			$dbw->update( 'image', [ 'img_name' => $newName ], [ 'img_name' => $oldName ] );
			$imageCount++;
		}
		$pageCount = 0;
		$res = $dbr->select( 'page', [ 'page_title' ], [
			'page_namespace' => NS_FILE,
			'page_title LIKE ' . $quotedOldNs
		] );
		foreach ( $res as $row ) {
			$oldTitle = $row->page_title;
			$newTitle = preg_replace( '/^' . preg_quote( $oldNs ) . ':/', $newNs, $oldTitle );
			$title = Title::makeTitleSafe( NS_FILE, $newTitle );
			if ( $title === null ) {
				$this->error( "Could not create a valid title for: {$newTitle}, skipping.\n" );
				continue;
			}
			$newTitle = $title->getDBkey();
			$this->output( "page title: {$oldTitle} -> {$newTitle}\n" );
			$dbw->update( 'page', [ 'page_title' => $newTitle ], [ 'page_title' => $oldTitle ] );
			$pageCount++;
		}
		$oldimageCount = 0;
		$res = $dbr->select( 'oldimage', [ 'oi_name' ], [ 'oi_name LIKE ' . $quotedOldNs ] );
		foreach ( $res as $row ) {
			$oldName = $row->oi_name;
			$newName = preg_replace( '/^' . preg_quote( $oldNs ) . ':/', $newNs, $oldName );
			$title = Title::makeTitleSafe( NS_FILE, $newName );
			if ( $title === null ) {
				$this->error( "Could not create a valid title for: {$newName}, skipping.\n" );
				continue;
			}
			$newName = $title->getDBkey();
			$this->output( "oldimage name: {$oldName} -> {$newName}\n" );
			$dbw->update( 'oldimage', [ 'oi_name' => $newName ], [ 'oi_name' => $oldName ] );
			$oldimageCount++;
		}

		$this->output(
			"\n{$imageCount} images, {$pageCount} page titles and {$oldimageCount} old images are altered.\n"
		);
		$this->output( "Reset/restart your cache services to avoid title loading problems.\n" );
		$this->output( "For example, if memcached is used, run: echo 'flush_all' | nc localhost 11211\n" );
		$this->output( "Consider running pruneUnusedLinkTargetRows and refreshLinks as aftercare.\n" );
	}
}

$maintClass = AlterFileNsPrefix::class;
require_once RUN_MAINTENANCE_IF_MAIN;
