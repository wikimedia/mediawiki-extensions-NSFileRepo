<?php

class NSFileRepoFSFileBackend extends FSFileBackend {

	protected $zoneSuffixes = [
		'public', 'thumb', 'transcoded', 'deleted', 'archive', 'temp'
	];

	/**
	 * This is a pretty bad workaround for "img_auth.php" and
	 * "FileRepo/FileBackend" not being able to do proper permission checks in
	 * REL1_27 of MediaWiki
	 * For details see: https://phabricator.wikimedia.org/T140334
	 * @param array $params
	 * @return boolean
	 */
	protected function doGetFileStat( array $params ) {
		$unprefixedPath = $this->stripStorageBasePath( $params['src'] );
		//e.g. $unprefixedPath = "5000/b/be/Some_image.png"
		//  or $unprefixedPath = "thumb/5000/b/be/Some_image.png/300px-Some_image.png"
		//  or $unprefixedPath = "deleted/5000/b/be/Some_image.png"
		//  or $unprefixedPath = "archive/5000/b/be/Some_image.png"
		//  or $unprefixedPath = "temp/5000/b/be/Some_image.png"
		//  or $unprefixedPath = "4/03/Some_image_that_is_not_in_a_namespace.png"

		$parts = explode( '/', $unprefixedPath );
		if( count($parts) < 4 ) {
			return parent::doGetFileStat($params);
		}

		if( in_array( $parts[0], $this->zoneSuffixes ) ) {
			array_shift( $parts );
		}

		$namespaceId = intval( array_shift( $parts ) ); // = 5000
		$fileName = array_pop( $parts ); // = "Some_image.png" or "300px-Some_image.png"
		if( UploadBase::isThumbName( $fileName ) ) {
			//HINT: Thumbname-to-filename-conversion taken from includes/Upload/UploadBase.php
			//Check for filenames like 50px- or 180px-, these are mostly thumbnails
			$fileName = substr( $fileName , strpos( $fileName , '-' ) +1 );
		}
		$title = Title::makeTitle( (int)$namespaceId, $fileName );
		if( $title instanceof Title && !$title->userCan( 'read' ) ) {
			return false;
		}

		//Maybe the single file is not protectey by Extension:Lockdown, but
		//also by some other mechanism
		$actualFileTitle = Title::makeTitle( NS_FILE, $title->getPrefixedText() );
		if( $actualFileTitle instanceof Title && !$actualFileTitle->userCan( 'read' ) ) {
			return false;
		}

		return parent::doGetFileStat( $params );
	}

	protected function resolveToFSPath( $storagePath ) {
		global $wgUploadDirectory;
		$unprefixedPath = $this->stripStorageBasePath( $storagePath );
		return "$wgUploadDirectory/$unprefixedPath";
	}

	/**
	 * @see FSFileBackend::getFeatures()
	 * @return int
	 */
	public function getFeatures() {
		return FileBackend::ATTR_UNICODE_PATHS;
	}

	protected function stripStorageBasePath( $storagePath ) {
		global $wgImgAuthUrlPathMap;
		/**
		 * TODO: Improve this!
		 * A proper FileBackendConfiguration should be used!
		 */
		foreach( $this->zoneSuffixes as $zone ) {
			$parts = explode( "$zone/nsfilerepo/", $storagePath, 2 );
			if( count( $parts ) === 2 ) {
				return $zone.'/'.$parts[1];
			}
		}

		return preg_replace( "#^{$wgImgAuthUrlPathMap['/nsfilerepo/']}#", '', $storagePath );
	}
}