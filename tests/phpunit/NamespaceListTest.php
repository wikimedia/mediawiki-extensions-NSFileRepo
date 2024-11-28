<?php

namespace MediaWiki\Extension\NSFileRepo\Tests;

use MediaWiki\Extension\NSFileRepo\Config;
use MediaWiki\Extension\NSFileRepo\NamespaceList;

/**
 * @covers \MediaWiki\Extension\NSFileRepo\NamespaceList
 */
class NamespaceListTest extends \MediaWikiLangTestCase {

	public const DUMMY_NS_A_ID = 12412;
	public const DUMMY_NS_B_ID = 12512;
	public const DUMMY_NS_C_ID = 12612;

	protected function setUp(): void {
		global $wgExtraNamespaces, $wgNamespaceContentModels, $wgContentHandlers;

		parent::setUp();

		$this->setMwGlobals( [
			'wgExtraNamespaces' => $wgExtraNamespaces,
			'wgNamespaceContentModels' => $wgNamespaceContentModels,
			'wgContentHandlers' => $wgContentHandlers,
		] );

		$wgExtraNamespaces[self::DUMMY_NS_A_ID] = 'NSFRDummyA';
		$wgExtraNamespaces[self::DUMMY_NS_A_ID + 1] = 'NSFRDummyA_talk';

		$wgExtraNamespaces[self::DUMMY_NS_B_ID] = 'NSFRDummyB';
		$wgExtraNamespaces[self::DUMMY_NS_B_ID + 1] = 'NSFRDummyB_talk';

		$wgExtraNamespaces[self::DUMMY_NS_C_ID] = 'NSFRDummyC';
		$wgExtraNamespaces[self::DUMMY_NS_C_ID + 1] = 'NSFRDummyC_talk';

		$this->getServiceContainer()
			->getNamespaceInfo()
			# reset namespace cache
			->getCanonicalNamespaces( true );

		/**
		 * Test hook handler that mimics Extension:Lockdown and revokes read
		 * permissions on 'NSFRDummyA' and edit permission on 'NSFRDummyB'
		 */
		$this->getServiceContainer()
			->getHookContainer()
			->register( 'getUserPermissionsErrors', static function ( &$title, &$user, $action, &$result ) {
				if ( $action === 'read'
					&& $title instanceof \Title
					&& $title->getNamespace() === self::DUMMY_NS_A_ID ) {
					$result = false;
					return false;
				}

				if ( $action === 'edit'
					&& $title instanceof \Title
					&& $title->getNamespace() === self::DUMMY_NS_B_ID ) {
					$result = false;
					return false;
				}

				return true;
			} );
		$this->resetServices();
	}

	public function testInstance() {
		$namespacelist = $this->makeInstance();
		$this->assertInstanceOf( NamespaceList::class, $namespacelist );
	}

	public function testGetReadableNoTalks() {
		$instance = $this->makeInstance( new \HashConfig( [
			Config::CONFIG_SKIP_TALK => true
		] ) );

		$readables = $instance->getReadable();
		$hasTalk = false;
		$namespaceInfo = $this->getServiceContainer()->getNamespaceInfo();
		foreach ( $readables as $namsepace ) {
			if ( $namespaceInfo->isTalk( $namsepace->getId() ) ) {
				$hasTalk = true;
				break;
			}
		}

		$this->assertFalse( $hasTalk, 'List should not contain any Talk namespaces' );
	}

	public function testGetReadableNoUnreadables() {
		$instance = $this->makeInstance( new \HashConfig( [
			Config::CONFIG_BLACKLIST => [ self::DUMMY_NS_A_ID ]
		] ) );

		$readables = $instance->getReadable();
		$hasUnreadables = false;
		foreach ( $readables as $namsepace ) {
			if ( $namsepace->getId() === self::DUMMY_NS_A_ID ) {
				$hasUnreadables = true;
				break;
			}
		}

		$this->assertFalse( $hasUnreadables, 'List should not contain an unreadable namespace' );
	}

	protected function makeInstance( $config = null ) {
		if ( $config === null ) {
			$config = new \HashConfig( [] );
		}

		$user = \RequestContext::getMain()->getUser();
		$lang = \RequestContext::getMain()->getLanguage();

		return new NamespaceList( $user, $config, $lang );
	}

}
