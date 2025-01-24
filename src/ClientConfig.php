<?php

namespace MediaWiki\Extension\NSFileRepo;

use MediaWiki\MediaWikiServices;

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

	/**
	 * @return array
	 */
	public static function makeNamespaceBuckets() {
		$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$field = '';
		if ( $db->getType() === 'mysql' ) {
			$field = 'DISTINCT SUBSTRING_INDEX(img_name, ":", 1) as namespace';
		}
		if ( $db->getType() === 'sqlite' ) {
			$field = 'DISTINCT SUBSTR(img_name, 1, INSTR(img_name, ":") - 1) as namespace';
		}
		if ( $db->getType() === 'postgres' ) {
			$field = 'DISTINCT SUBSTRING(img_name FROM 1 FOR POSITION(":" IN img_name) - 1) as namespace';
		}
		$res = $db->select(
			'image',
			[ $field ],
			[ "img_name LIKE '%:%'" ],
			__METHOD__
		);
		$namespaces = [];
		foreach ( $res as $row ) {
			$namespaces[] = $row->namespace;
		}
		sort( $namespaces );
		return array_map( static function ( $namespace ) {
			return [
				'data' => $namespace,
				'label' => $namespace
			];
		}, $namespaces );
	}
}
