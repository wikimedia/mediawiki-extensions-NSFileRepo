<?php

namespace MediaWiki\Extension\NSFileRepo\Tests\HookHandler;

use HashConfig;
use MediaWiki\Extension\NSFileRepo\HookHandler\PermissionChecker;
use MediaWiki\Permissions\PermissionManager;
use PHPUnit\Framework\TestCase;
use Title;
use User;

/**
 * @covers \MediaWiki\Extension\NSFileRepo\HookHandler\PermissionChecker
 */
class PermissionCheckerTest extends TestCase {

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
		$permissionManager->method( 'getPermissionErrors' )->willReturn( [
			'I-am-always-failing-if-not-bailed-out-before'
		] );

		$handler = new PermissionChecker( $mainConfig, $permissionManager );

		$user = $this->createMock( User::class );
		$action = 'read';
		$result = true;
		$handler->onGetUserPermissionsErrors( $title, $user, $action, $result );

		$this->assertEquals( $expectedResult, $result );
	}

	public function provideTestOnGetUserPermissionsErrorsData() {
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
