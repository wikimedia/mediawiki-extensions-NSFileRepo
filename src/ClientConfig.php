<?php

namespace MediaWiki\Extension\NSFileRepo;

class ClientConfig {

	/**
	 *
	 * @return array
	 */
	public static function makeConfigJson() {
		$config = new Config();
		return [
			'egNSFileRepoSkipTalk' => $config->get( 'SkipTalk' ),
			'egNSFileRepoNamespaceBlacklist' => $config->get( 'NamespaceBlacklist' ),
			'egNSFileRepoNamespaceThreshold' => $config->get( 'NamespaceThreshold' )
		];
	}
}
