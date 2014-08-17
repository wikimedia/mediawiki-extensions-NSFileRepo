<?php

/**
 * Class definitions for NSFileRepo
 */

class NSLocalRepo extends LocalRepo {
	public $fileFactory = array( 'NSLocalFile', 'newFromTitle' );
	public $oldFileFactory = array( 'NSOldLocalFile', 'newFromTitle' );
	public $fileFromRowFactory = array( 'NSLocalFile', 'newFromRow' );
	public $oldFileFromRowFactory = array( 'NSOldLocalFile', 'newFromRow' );

	static function getHashPathForLevel( $name, $levels ) {
		global $wgContLang;
		$bits = explode( ':',$name );
		$filename = $bits[ count( $bits ) - 1 ];
		$path = parent::getHashPathForLevel( $filename, $levels );
		return count( $bits ) > 1 ?
			$wgContLang->getNsIndex( $bits[0] ) .'/'. $path : $path;
	}

	/**
	 * Get a relative path including trailing slash, e.g. f/fa/
	 * If the repo is not hashed, returns an empty string
	 * This is needed because self:: will call parent if not included - exact same as in FSRepo
	 */
	function getHashPath( $name ) {
		return self::getHashPathForLevel( $name, $this->hashLevels );
	}

	/**
	 * Pick a random name in the temp zone and store a file to it.
	 * @param string $originalName The base name of the file as specified
	 *     by the user. The file extension will be maintained.
	 * @param string $srcPath The current location of the file.
	 * @return FileRepoStatus object with the URL in the value.
	 */
	function storeTemp( $originalName, $srcPath ) {
		$date = gmdate( "YmdHis" );
		$hashPath = $this->getHashPath( $originalName );
		$filename = $this->getFileNameStripped( $originalName );
		$dstRel = "$hashPath$date!$filename";
		$dstUrlRel = $hashPath . $date . '!' . rawurlencode( $filename );
		$result = $this->store( $srcPath, 'temp', $dstRel );
		$result->value = $this->getVirtualUrl( 'temp' ) . '/' . $dstUrlRel;
		return $result;
	}

	function getFileNameStripped($suffix) {
		return(NSLocalFile::getFileNameStripped($suffix));
	}
}

class NSLocalFile extends LocalFile {
	/**
	 * Get the path of the file relative to the public zone root
	 */
	function getRel() {
		return $this->getHashPath() . $this->getFileNameStripped( $this->getName() );
	}
	/**
	 * Get the path, relative to the thumbnail zone root, of the
	 * thumbnail directory or a particular file if $suffix is specified
	 *
	 * @param $suffix bool|string if not false, the name of a thumbnail file
	 *
	 * @return string
	 */
	function getThumbRel( $suffix = false ) {
		$path = $this->getRel();
		if ( $suffix !== false ) {
/* This is the part that changed from LocalFile */
			$path .= '/' . $this->getFileNameStripped( $suffix );
/* End of changes */
		}
		return $path;
	}

	/**
	 * Get the path of an archived file relative to the public zone root
	 *
	 * @param $suffix bool|string if not false, the name of an archived thumbnail file
	 *
	 * @return string
	 */
	function getArchiveRel( $suffix = false ) {
		$path = 'archive/' . $this->getHashPath();
		if ( $suffix === false ) {
			$path = substr( $path, 0, -1 );
		} else {
/* This is the part that changed from LocalFile */
			$path .= '/' . $this->getFileNameStripped( $suffix );
/* End of changes */
		}
		return $path;
	}



	/**
	 * Get urlencoded relative path of the file
	 */
	function getUrlRel() {
		return $this->getHashPath() . 
			rawurlencode( $this->getFileNameStripped( $this->getName() ) );
	}

	/**
	 * Get the URL of the thumbnail directory, or a particular file if $suffix is specified
	 *
	 * @param $suffix bool|string if not false, the name of a thumbnail file
	 *
	 * @return string path
	 */
	function getThumbUrl( $suffix = false ) {
		$path = $this->repo->getZoneUrl('thumb') . '/' . $this->getUrlRel();
		if ( $suffix !== false ) {
			$path .= '/' . rawurlencode( $this->getFileNameStripped( $suffix ) );
		}
		return $path;
	}


	public function thumbName( $params, $flags = 0 ) {
		$name = ( $this->repo && !( $flags & self::THUMB_FULL_NAME ) )
/* This is the part that changed from LocalFile */
			? $this->repo->nameForThumb( $this->getFileNameStripped( $this->getName() ) )
			: $this->getFileNameStripped( $this->getName() );
/* End of changes */
		return $this->generateThumbName( $name, $params );
	}

	/**
	 * Generate a thumbnail file name from a name and specified parameters
	 *
	 * @param string $name
	 * @param array $params Parameters which will be passed to MediaHandler::makeParamString
	 *
	 * @return string
	 */
	public function generateThumbName( $name, $params ) {
		if ( !$this->getHandler() ) {
			return null;
		}
		$extension = $this->getExtension();
		list( $thumbExt, $thumbMime ) = $this->handler->getThumbType(
			$extension, $this->getMimeType(), $params );
/* This is the part that changed from LocalFile */
		$thumbName = $this->handler->makeParamString( $params ) . '-' . 
			$this->getFileNameStripped( $this->getName() );
/* End of changes */
		if ( $thumbExt != $extension ) {
			$thumbName .= ".$thumbExt";
		}
/* And also need to retain namespace changed from LocalFile */
		$bits = explode( ':',$this->getName() );
		if ( count($bits) > 1 ) $thumbName = $bits[0] . ":" . $thumbName;
/* End of changes */
		return $thumbName;
	}

	/**
	 * Get the URL of the archive directory, or a particular file if $suffix is specified
	 *
	 * @param $suffix bool|string if not false, the name of an archived file
	 *
	 * @return string
	 */
	function getArchiveUrl( $suffix = false ) {
		$this->assertRepoDefined();
		$ext = $this->getExtension();
		$path = $this->repo->getZoneUrl( 'public', $ext ) . '/archive/' . $this->getHashPath();
		if ( $suffix === false ) {
			$path = substr( $path, 0, -1 );
		} else {
/* This is the part that changed from LocalFile */
			$path .= rawurlencode( $this->getFileNameStripped( $suffix ) );
/* End of changes */
		}
		return $path;
	}

	/**
	 * Get the public zone virtual URL for an archived version source file
	 *
	 * @param $suffix bool|string if not false, the name of a thumbnail file
	 *
	 * @return string
	 */
	function getArchiveVirtualUrl( $suffix = false ) {
		$this->assertRepoDefined();
		$path = $this->repo->getVirtualUrl() . '/public/archive/' . $this->getHashPath();
		if ( $suffix === false ) {
			$path = substr( $path, 0, -1 );
		} else {
/* This is the part that changed from LocalFile */
			$path .= rawurlencode( $this->getFileNameStripped( $suffix ) );
/* End of changes */
		}
		return $path;
	}

	/**
	 * Get the virtual URL for a thumbnail file or directory
	 *
	 * @param $suffix bool|string if not false, the name of a thumbnail file
	 *
	 * @return string
	 */
	function getThumbVirtualUrl( $suffix = false ) {
		$this->assertRepoDefined();
		$path = $this->repo->getVirtualUrl() . '/thumb/' . $this->getUrlRel();
		if ( $suffix !== false ) {
			$path .= '/' . rawurlencode( $suffix );
/* This is the part that changed from LocalFile */
			$path .= '/' . rawurlencode( $this->getFileNameStripped( $suffix ) );
/* End of changes */
		}
		return $path;
	}

	/**
	 * Strip namespace (if any) from file name
	 *
	 * @param $suffix the name of a thumbnail file
	 *
	 * @return string
	 */
	function getFileNameStripped($suffix) {
		$bits = explode( ':', $suffix );
		return $bits[ count( $bits ) -1 ];
	}
	
	/**
	 * Move or copy a file to a specified location. Returns a FileRepoStatus
	 * object with the archive name in the "value" member on success.
	 *
	 * The archive name should be passed through to recordUpload for database
	 * registration.
	 *
	 * @param $srcPath String: local filesystem path to the source image
	 * @param $dstRel String: target relative path
	 * @param $flags Integer: a bitwise combination of:
	 *     File::DELETE_SOURCE	Delete the source file, i.e. move rather than copy
	 * @param $options Array Optional additional parameters
	 * @return FileRepoStatus object. On success, the value member contains the
	 *     archive name, or an empty string if it was a new file.
	 */
	function publishTo( $srcPath, $dstRel, $flags = 0, array $options = array() ) {
		if ( $this->getRepo()->getReadOnlyReason() !== false ) {
			return $this->readOnlyFatalStatus();
		}

		$this->lock(); // begin

/* This is the part that changed from LocalFile */
		$archiveName = wfTimestamp( TS_MW ) . '!'. $this->getFileNameStripped( $this->getName() );
/* End of changes */
		$archiveRel = 'archive/' . $this->getHashPath() . $archiveName;
		$flags = $flags & File::DELETE_SOURCE ? LocalRepo::DELETE_SOURCE : 0;
		$status = $this->repo->publish( $srcPath, $dstRel, $archiveRel, $flags, $options );

		if ( $status->value == 'new' ) {
			$status->value = '';
		} else {
			$status->value = $archiveName;
		}

		$this->unlock(); // done

		return $status;
	}

	/**
	 * Move file to the new title
	 *
	 * Move current, old version and all thumbnails
	 * to the new filename. Old file is deleted.
	 *
	 * Cache purging is done; checks for validity
	 * and logging are caller's responsibility
	 *
	 * @param $target Title New file name
	 * @return FileRepoStatus object.
	 */
	function move( $target ) {
		if ( $this->getRepo()->getReadOnlyReason() !== false ) {
			return $this->readOnlyFatalStatus();
		}

		wfDebugLog( 'imagemove', "Got request to move {$this->name} to " . $target->getText() );
/* This is the part that changed from LocalFile */
		$batch = new NSLocalFileMoveBatch( $this, $target );
/* End of changes */

		$this->lock(); // begin
		$batch->addCurrent();
		$archiveNames = $batch->addOlds();
		$status = $batch->execute();
		$this->unlock(); // done

		wfDebugLog( 'imagemove', "Finished moving {$this->name}" );

		$this->purgeEverything();
		foreach ( $archiveNames as $archiveName ) {
			$this->purgeOldThumbnails( $archiveName );
		}
		if ( $status->isOK() ) {
			// Now switch the object
			$this->title = $target;
			// Force regeneration of the name and hashpath
			unset( $this->name );
			unset( $this->hashPath );
			// Purge the new image
			$this->purgeEverything();
		}

		return $status;
	}


	/** Instantiating this class using "self"
	 * If you're reading this, you're problably wondering why on earth are the following static functions, which are copied
	 * verbatim from the original extended class "LocalFIle" included here?
	 * The answer is that "self", will instantiate the class the code is physically in, not the class extended from it.
	 * Without the inclusion of these methods in "NSLocalFile, "self" would instantiate a "LocalFile" class, not the
	 * "NSLocalFile" class we want it to.  Since there are only two methods within the "LocalFile" class that use "self",
	 * I just copied that code into the new "NSLocalFile" extended class, and the copied code will instantiate the "NSLocalFIle"
	 * class instead of the "LocalFile" class (at least in PHP 5.2.4)
	 */

	/**
	 * Create a NSLocalFile from a title
	 * Do not call this except from inside a repo class.
	 *
	 * Note: $unused param is only here to avoid an E_STRICT
	 */
	static function newFromTitle( $title, $repo, $unused = null ) {
		return new self( $title, $repo );
	}
	/**
	 * Create a NSLocalFile from a title
	 * Do not call this except from inside a repo class.
	 */

	static function newFromRow( $row, $repo ) {
		$title = Title::makeTitle( NS_FILE, $row->img_name );
		$file = new self( $title, $repo );
		$file->loadFromRow( $row );
		return $file;
	}
}

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
}

/**
 * Helper class for file movement
 * @ingroup FileAbstraction
 */
class NSLocalFileMoveBatch extends LocalFileMoveBatch {
	/**
	 * @param File $file
	 * @param Title $target
	 */
	function __construct( File $file, Title $target ) {
		$this->file = $file;
		$this->target = $target;
		$this->oldHash = $this->file->repo->getHashPath( $this->file->getName() );
		$this->newHash = $this->file->repo->getHashPath( $this->target->getDBkey() );
		$this->oldName = $this->file->getName();
		$this->newName = $this->file->repo->getNameFromTitle( $this->target );
		$this->oldRel = $this->oldHash . $this->file->getFileNameStripped( $this->oldName );
		$this->newRel = $this->newHash . $this->file->getFileNameStripped( $this->newName );
		$this->db = $file->getRepo()->getMasterDb();
	}
}
