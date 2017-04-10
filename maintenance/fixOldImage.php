<?php

require_once( dirname(dirname(dirname(dirname(__DIR__)))) . '/maintenance/Maintenance.php' );

class FixOldImage extends Maintenance {
	protected $mDBName;

	function __construct() {
		parent::__construct();
		$this->addOption( 'db', 'DB to run script on', true, true );
	}

	function execute() {
		$this->mDBName = $this->getOption( 'db' );

		$dbw = wfGetDB( DB_MASTER );
		$dbw->selectDB( $this->mDBName );
		print( "USING DB: " . $dbw->getDBName() . "\n" );
		$images = $dbw->select( 'oldimage',
			array( 'oi_name', 'oi_archive_name' ),
			array(),
			__METHOD__
		);

		$count = 0;
		$log = '';
		foreach( $images as $image ) {
			$nameBits = explode( ':', $image->oi_name );
			$nameNS = '';
			$nameName = $image->oi_name;
			if( count( $nameBits ) == 2 ) {
				$nameNS = $nameBits[0];
				$nameName = $nameBits[1];
 			}

			$archiveName = explode( '!', $image->oi_archive_name )[1];
			$archiveBits = explode( ':', $archiveName );
			$archiveNS = '';
			if( count( $archiveBits ) == 2 ) {
				$archiveNS = $archiveBits[0];
				$archiveName = $archiveBits[1];
			}

			if( $nameNS === $archiveNS && $nameName == $archiveName ) {
				continue;
			}

			if( $nameNS != $archiveNS && $nameName != $archiveName ) {
				continue;
			}
			$title = Title::newFromText( $image->oi_name, NS_FILE );
			$repo = RepoGroup::singleton()->getRepo( 'local' );
			$file = OldLocalFile::newFromArchiveName( $title, $repo, $image->oi_archive_name );
			if( !$file->exists() ) {
				continue;
			}

			print( "NAME: ns-" . $nameNS . ' name-' . $nameName . ' ARCHIVE: ns-' . $archiveNS . ' name-' . $archiveName . "\n" );
			$log .= $nameNS . ";" . $nameName . ";" . $archiveNS . ";" . $archiveName . "\n";
			$dbw->update(
				'oldimage',
				array(
					'oi_archive_name = ' . $dbw->strreplace( 'oi_archive_name',
						$dbw->addQuotes( $archiveName ), $dbw->addQuotes( $image->oi_name ) )
				),
				array( 'oi_name' => $image->oi_name ),
				__METHOD__
			);

			$count++;
		}
		#file_put_contents( '/tmp/fixOldImagesLog.log', $log );
		print( "Total " . $count . " images altered\n" );
	}
}

$maintClass = 'FixOldImage';
require_once( RUN_MAINTENANCE_IF_MAIN );
