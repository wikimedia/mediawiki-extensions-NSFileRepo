<?php

class NSOldLocalFile extends OldLocalFile {

	function getRel() {
		return 'archive/' . $this->getHashPath() .
			$this->getFileNameStripped( $this->getArchiveName() );
	}
	function getUrlRel() {
		return 'archive/' . $this->getHashPath() .
			urlencode( $this->getFileNameStripped( $this->getArchiveName() ) );
	}
	function publish( $srcPath, $flags = 0, array $options = array() ) {
		return NSLocalFile::publish( $srcPath, $flags, $options );
	}
	function getThumbUrl( $suffix = false ) {
		return NSLocalFile::getThumbUrl( $suffix );
	}
	function thumbName( $params, $flags = 0 ) {
		return NSLocalFile::thumbName( $params, $flags );
	}
	function getThumbPath( $suffix = false ) {
		return NSLocalFile::getThumbPath( $suffix );
	}
	function getArchiveRel( $suffix = false ) {
		return NSLocalFile::getArchiveRel( $suffix );
	}
	function getArchiveUrl( $suffix = false ) {
		return NSLocalFile::getArchiveUrl( $suffix );
	}
	function getArchiveVirtualUrl( $suffix = false ) {
		return NSLocalFile::getArchiveVirtualUrl( $suffix );
	}
	function getThumbVirtualUrl( $suffix = false ) {
		return NSLocalFile::getArchiveVirtualUrl( $suffix );
	}
	function getVirtualUrl( $suffix = false ) {
		return NSLocalFile::getVirtualUrl( $suffix );
	}
	function getFileNameStripped($suffix) {
		return NSLocalFile::getFileNameStripped( $suffix );
	}
	function addOlds() {
		return NSLocalFile::addOlds();
	}
	function purgeThumbnails($options = array() ) {
		return NSLocalFile::purgeThumbnails( $options );
	}
	/**
	 * Replaces hard coded OldLocalFile::newFromRow to use $this->repo->oldFileFromRowFactory configuration
	 * This may not be necessary in the future if LocalFile is patched to allow configuration
	*/
	function getHistory( $limit = null, $start = null, $end = null, $inc = true ) {
		return NSLocalFile::getHistory( $limit, $start , $end, $inc );
	}

	/** See comment above about Instantiating this class using "self" */

	static function newFromTitle( $title, $repo, $time = null ) {
		# The null default value is only here to avoid an E_STRICT
		if( $time === null )
			throw new MWException( __METHOD__.' got null for $time parameter' );
		return new self( $title, $repo, $time, null );
	}

	static function newFromArchiveName( $title, $repo, $archiveName ) {
		return new self( $title, $repo, null, $archiveName );
	}

	static function newFromRow( $row, $repo ) {
		$title = Title::makeTitle( NS_FILE, $row->oi_name );
		$file = new self( $title, $repo, null, $row->oi_archive_name );
		$file->loadFromRow( $row, 'oi_' );
		return $file;
	}

	/**
	 * Create a OldLocalFile from a SHA-1 key
	 * Do not call this except from inside a repo class.
	 *
	 * Copy & paste from OldLocalFile to fix "late-static-binding" issue
	 *
	 * @param string $sha1 Base-36 SHA-1
	 * @param LocalRepo $repo
	 * @param string|bool $timestamp MW_timestamp (optional)
	 *
	 * @return bool|OldLocalFile
	 */
	static function newFromKey( $sha1, $repo, $timestamp = false ) {
		$dbr = $repo->getSlaveDB();

		$conds = [ 'oi_sha1' => $sha1 ];
		if ( $timestamp ) {
			$conds['oi_timestamp'] = $dbr->timestamp( $timestamp );
		}

		$row = $dbr->selectRow( 'oldimage', self::selectFields(), $conds, __METHOD__ );
		if ( $row ) {
			return self::newFromRow( $row, $repo );
		} else {
			return false;
		}
	}
}
