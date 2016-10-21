<?php

class NSFileRepoHooks {

	/**
	* Initial setup
	 * @global array $wgLocalFileRepo
	 */
	public static function setup() {
		if ( !function_exists('lockdownUserPermissionsErrors') ) {
			die('You MUST load extension Lockdown before NSFileRepo (http://www.mediawiki.org/wiki/Extension:Lockdown).');
		}

		$GLOBALS['wgLocalFileRepo']['class'] = "NSLocalRepo";
		$GLOBALS['wgLocalFileRepo']['backend'] = "nsfilerepo-fs";
		$GLOBALS['wgLocalFileRepo']['url'] = $GLOBALS['wgScriptPath'] .'/img_auth.php';

		RepoGroup::destroySingleton();
	}

	public static function register() {
		require_once( __DIR__.'/DefaultSettings.php' );
	}

	/**
	 * Add JavaScript
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return boolean true
	 */
	public static function onBeforePageDisplay( &$out, &$skin ) {
		if( $out->getTitle()->isSpecial( 'Upload' ) ) {
			$out->addModules( 'ext.nsfilerepo.special.upload' );
		}

		return true;
	}

	/**
	 * Check for Namespace in Title line
	 * @param UploadForm $uploadForm
	 * @return boolean
	 */
	public static function onUploadFormBeforeProcessing( &$uploadForm ) {
		$title = Title::newFromText($uploadForm->mDesiredDestName);
		if( $title === null ) {
			return true;
		}
		if ( $title->getNamespace() < 100 ) {
			$uploadForm->mDesiredDestName = preg_replace( "/:/", '-', $uploadForm->mDesiredDestName );
		} else {
			$bits = explode( ':', $uploadForm->mDesiredDestName );
			$ns = array_shift( $bits );
			$uploadForm->mDesiredDestName = $ns.":" . implode( "-", $bits );
		}
		return true;
	}

	/**
	 * If Extension:Lockdown has been activated (recommend), check individual namespace protection
	 * @global array $wgWhitelistRead
	 * @param Title $title
	 * @param user $user
	 * @param string $action
	 * @param mixed $result
	 * @return boolean
	 */
	public static function onUserCan( &$title, &$user, $action, &$result) {
		global $wgWhitelistRead;
		if ( $wgWhitelistRead !== false && in_array( $title->getPrefixedText(), $wgWhitelistRead ) ) {
			return true;
		} elseif( function_exists( 'lockdownUserPermissionsErrors' ) ) {
			if( $title->getNamespace() == NS_FILE ) {
				$ntitle = Title::newFromText( $title->mDbkeyform );
				$ret_val = ( $ntitle->getNamespace() < 100 ) ?
						true : lockdownUserPermissionsErrors( $ntitle, $user, $action, $result );
				$result = null;
				return $ret_val;
			}
		}
		return true;
	}

	/**
	 * Add fields to Special:Upload
	 * @param array $descriptor
	 * @return boolean
	 */
	public static function onUploadFormInitDescriptor( &$descriptor ) {
		$sSelectedNamespace = '';
		//wpDestFile is set on query string. e.g after click on redlink or on re-upload
		if( !empty( $descriptor['DestFile']['default'] ) ) {
			$oTarget = Title::newFromText( $descriptor['DestFile']['default'] );
			$descriptor['DestFile']['default'] = $oTarget->getText();
			$sSelectedNamespace = $oTarget->getNsText();
		}

		$aNamespaces = self::getPossibleNamespaces();
		$aOptions = array(
			wfMessage('nsfilerepo-nsmain')->plain() => ''
		);
		foreach($aNamespaces as $iNsId => $sNsText ) {
			if( $iNsId === NS_MAIN ) {
				continue;
			}
			$aOptions[$sNsText] = $sNsText;
		}

		$aFieldDef = array(
			'NSFR_Namespace' => array (
				'label'    => wfMessage('namespace')->plain(),
				'section'  => 'description',
				'class'    => 'HTMLSelectField',
				'options'  => $aOptions,
				'required' => true,
				'default' => str_replace( ' ', '_', $sSelectedNamespace )
			),
			'NSFR_DestFile' =>
				array (
					'type' => 'text',
					'section' => 'description',
					'label-message' => 'nsfilerepo-upload-target',
					'size' => 60,
					'default' => '',
					'readonly' => true,
					'nodata' => false,
			),
		);

		$sPostion = array_search(
			'UploadDescription',
			array_keys( $descriptor )
		);

		$descriptor =
			array_slice($descriptor, 0, $sPostion, true) +
			$aFieldDef +
			array_slice($descriptor, $sPostion, count($descriptor) - 1, true) ;

		return true;
	}


	/**
	 * Returns an Array of Namespaces, that can be used for NSFileRepo
	 * @param Boolean $filterByPermissions
	 * @param User $user
	 * @return Array (NsIdx => NsLocalizedName)
	 */
	protected static function getPossibleNamespaces( $filterByPermissions = true, $user = null ) {
		$availableNamespaces = RequestContext::getMain()
			->getLanguage()
			->getNamespaces();

		foreach( $availableNamespaces as $nsIdx => $nsText ) {
			if( $nsIdx % 2 !== 0 || $nsIdx < 100 ) {
				unset( $availableNamespaces[$nsIdx] );
			}
		}

		if( !$filterByPermissions ) {
			return $availableNamespaces;
		}

		return static::filterNamespacesByPermission(
			$availableNamespaces,
			'read',
			$user
		);
	}

	/**
	 * Filter array of namespaces based on the user's permission
	 * @param array $namespaces
	 * @param string $permission
	 * @param User $user
	 * @return array
	 */
	protected static function filterNamespacesByPermission( $namespaces, $permission = 'read', $user = null ) {
		$aReturn = array();
		foreach($namespaces as $iNsId => $sNsText ) {
			$oTitle = Title::makeTitle( $iNsId, 'X' );
			if( $oTitle->userCan( $permission, $user ) === false ) {
				continue;
			}
			$aReturn[$iNsId] = $sNsText;
		}
		return $aReturn;
	}
}