<?php

class NSFileRepoHooks {

	/**
	* Initial setup, add .i18n. messages from $IP/extensions/DiscussionThreading/DiscussionThreading.i18n.php
	 * @global array $wgLocalFileRepo
	 */
	public static function setup() {
		global $wgLocalFileRepo;
		$wgLocalFileRepo['class'] = "NSLocalRepo";
		RepoGroup::destroySingleton();
	}

	/**
	 * Check for Namespace in Title Line
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
	 *
	 * @global Language $wgContLang
	 * @param Title $title
	 * @param string $path
	 * @param string $name
	 * @param mixed $result
	 * @return boolean
	 */
	public static function onImgAuthBeforeStream( &$title, &$path, &$name, &$result ) {
	global $wgContLang;

	# See if stored in a NS path
	$subdirs = explode('/',$path);
	$x = (!is_numeric($subdirs[1]) && ($subdirs[1] == "archive" || $subdirs[1] == "deleted" || $subdirs[1] == "thumb")) ? 2 : 1;
	$x = ($x == 2 && $subdirs[1] == "thumb" && $subdirs[2] == "archive") ? 3 : $x;
	if ( strlen( $subdirs[$x] ) >= 3 && is_numeric( $subdirs[$x] )
		&& $subdirs[$x] >= 100 )
	{
		$title = Title::makeTitleSafe( NS_FILE, $wgContLang->getNsText( $subdirs[$x] ) . ":" . $name );
		if( !$title instanceof Title ) {
			$result = array( 'img-auth-accessdenied', 'img-auth-badtitle', $name );
			return false;
		}
	}
	return true;
}
}