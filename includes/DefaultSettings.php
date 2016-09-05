<?php

/**
 * Some default configuration that is needed for this extension
 */

// Remove the default illegal char ':' - needed it to determine NS
$GLOBALS['wgIllegalFileChars'] = str_replace( ":","",$GLOBALS['wgIllegalFileChars'] );

//Activate img_auth.php
$GLOBALS['wgUploadPath'] = $GLOBALS['wgScriptPath'] .'/img_auth.php';
$GLOBALS['wgImgAuthUrlPathMap']['/nsfilerepo/'] = 'mwstore://nsfilerepo-fs/namespace/';
$GLOBALS['wgFileBackends'][] = array(
	'name'        => 'nsfilerepo-fs',
	'class'       => 'NSFileRepoFSFileBackend',
	'lockManager' => 'fsLockManager',
	#'wikiId'       => wfWikiID()
);