<?php

namespace MediaWiki\Extension\NSFileRepo\Tests\HookHandler;

use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\NSFileRepo\HookHandler\Authenticate;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\NSFileRepo\HookHandler\Authenticate
 */
class AuthenticateTest extends TestCase {

	/**
	 * @dataProvider provideTestOnGetUserPermissionsErrorsData
	 */
	public function testOnGetUserPermissionsErrors( Title $title, bool $expectedResult ) {
		$mainConfig = new HashConfig( [
			'WhitelistRead' => [ 'File:My whiteliste read file.pdf' ]
		] );
		$permissionManager = $this->createMock( PermissionManager::class );
		// We are mainly testing bail-out logic. So we assume, that every
		// non-bail-out test will lead to access prevention.
		$permissionManager->method( 'getPermissionStatus' )->willReturn(
			PermissionStatus::newFatal( 'I-am-always-failing-if-not-bailed-out-before' )
		);
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->method( 'newFromText' )->willReturnCallback( function ( $text ) {
				$titleMock = $this->createMock( Title::class );
				$titleMock->method( 'getNamespace' )
					->willReturn( strPos( $text, ':' ) === false ? NS_MAIN : NS_FILE );
				return $titleMock;
		} );

		$handler = new Authenticate( $permissionManager, $mainConfig, $titleFactoryMock );

		$user = $this->createMock( User::class );
		$action = 'read';
		$result = true;
		$handler->onGetUserPermissionsErrors( $title, $user, $action, $result );

		if ( $expectedResult === false ) {
			$this->assertIsArray( $result, "Expected an array of errors when permission check fails" );
		} else {
			$this->assertTrue( $result, "Expected true for permission check success" );
		}
	}

	public static function provideTestOnGetUserPermissionsErrorsData() {
		return [
			'should-bail-out-for-wgWhitelistRead' => [
				Title::newFromText( 'File:My_whiteliste_read_file.pdf' ),
				true
			],
			'should-bail-out-for-non-File-titles' => [
				Title::newFromText( 'Help:Some_help_pages' ),
				true
			],
			'should-bail-out-for-files-associated-with-non-custom-namespaces' => [
				Title::newFromText( 'File:Help:MyFile.pdf' ),
				true
			],
			'should-prevent-access-for-files-associated-with-main-namespace' => [
				Title::newFromText( 'File:MyFile.pdf' ),
				false
			]
		];
	}
}
