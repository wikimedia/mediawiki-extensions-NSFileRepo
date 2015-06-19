<?php
/**
 * The purpose of this extension is to provide NameSpace-based features to uploaded files in the local file repositories (FileRepo)
 * The optimal solution would be a clean extension that is easily maintainable as the trunk of MW moves foward.
 *
 * @file
 * @author Jack D. Pond <jack.pond@psitex.com>
 * @ingroup Extensions
 * @copyright  2009-2012 Jack D. pond
 * @url http://www.mediawiki.org/wiki/Manual:Extension:NSFileRepo
 * @licence GNU General Public Licence 2.0 or later
 *
 * Version 1.7.0 - Some refactoring, img_auth enabled by default
 *
 * Version 1.6.0 - Migrated to JSON i18n files
 *
 * Version 1.5 - (bug 45364)Fixed Moving/Rename, synched for Repo Upgrades
 *
 * Version 1.4 - Bug 37652 Several thumbnail fixes and updates for FileRepo enhancements
 *
 * Version 1.3 - Allows namespace protected files to be whitelisted
 *
 * Version 1.2 - Fixes reupload error and adds lockdown security to archives, deleted, thumbs
 *
 * This extension extends and is dependent on extension Lockdown - see http://www.mediawiki.org/wiki/Extension:Lockdown
 * It must be included(required) after Lockdown!  Also, $wgHashedUploadDirectory must be true and cannot be changed once repository has files in it
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}
if ( !function_exists('lockdownUserPermissionsErrors') ) {
	die('You MUST load Extension Lockdown before NSFileRepo (http://www.mediawiki.org/wiki/Extension:Lockdown).');
}

$wgExtensionCredits['media'][] = array(
	'path' => __FILE__,
	'name' => 'NSFileRepo',
	'author' => 'Jack D. Pond',
	'version' => '1.7.0',
	'url' => 'https://www.mediawiki.org/wiki/Extension:NSFileRepo',
	'descriptionmsg' => 'nsfilerepo-desc'
);

# Internationalisation file
$wgMessagesDirs['NSFileRepo'] = __DIR__ . '/i18n/nsfilerepo';
$wgExtensionMessagesFiles['NSFileRepo'] =  __DIR__ . '/NSFileRepo.i18n.php';
$wgMessagesDirs['img_auth'] = __DIR__ . '/i18n/imgauth';
$wgExtensionMessagesFiles['img_auth'] =  __DIR__ . '/img_auth.i18n.php';

/**
 * Classes
 */
$wgAutoloadClasses['NSFileRepoHooks'] = __DIR__ . '/NSFileRepo.hooks.php';
$wgAutoloadClasses['NSLocalRepo'] = __DIR__ . '/includes/filerepo/NSLocalRepo.php';
$wgAutoloadClasses['NSLocalFile'] = __DIR__ . '/includes/filerepo/file/NSLocalFile.php';
$wgAutoloadClasses['NSLocalFileMoveBatch'] = __DIR__ . '/includes/filerepo/file/NSLocalFile.php';
$wgAutoloadClasses['NSOldLocalFile'] = __DIR__ . '/includes/filerepo/file/NSOldLocalFile.php';

$wgExtensionFunctions[] = 'NSFileRepoHooks::setup';

/**
 * Set up hooks for NSFileRepo
 */
$wgHooks['UploadForm:BeforeProcessing'][] =  'NSFileRepoHooks::onUploadFormBeforeProcessing';
// Note, this must be AFTER lockdown has been included - thus assuming that the
// user has access to files in general + files at this particular namespace.
$wgHooks['userCan'][] = 'NSFileRepoHooks::onUserCan';
$wgHooks['ImgAuthBeforeStream'][] = 'NSFileRepoHooks::onImgAuthBeforeStream';

/**
 * Some default configuration that is needed for this extension
 */
$wgImgAuthPublicTest = false; // Must be set to false if you want to use more restrictive than general ['*']['read']
$wgIllegalFileChars = isset( $wgIllegalFileChars ) ? $wgIllegalFileChars : ""; // For MW Versions <1.16
$wgIllegalFileChars = str_replace(":","",$wgIllegalFileChars); // Remove the default illegal char ':' - need it to determine NS
$wgUploadPath = "$wgScriptPath/img_auth.php";