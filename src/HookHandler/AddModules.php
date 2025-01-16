<?php
namespace MediaWiki\Extension\NSFileRepo\HookHandler;

use MediaWiki\Hook\BeforePageDisplayHook;
use Wikimedia\Rdbms\ILoadBalancer;

class AddModules implements BeforePageDisplayHook {
	/** @var ILoadBalancer */
	private $lb;

	/**
	 * @param ILoadBalancer $lb
	 */
	public function __construct( ILoadBalancer $lb ) {
		$this->lb = $lb;
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( $out->getTitle() && $out->getTitle()->isSpecial( 'Upload' ) ) {
			$out->addModules( 'ext.nsfilerepo.special.upload' );
		}

		if ( $out->getTitle() && $out->getTitle()->isSpecial( 'EnhancedFilelist' ) ) {
			$db = $this->lb->getConnection( DB_REPLICA );
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
			$namespaces = array_map( static function ( $namespace ) {
				return [
					'data' => $namespace,
					'label' => $namespace
				];
			}, $namespaces );
			$out->addJsConfigVars( 'bsgNSFileRepoAvailableNamespaces', $namespaces );
		}
	}
}
