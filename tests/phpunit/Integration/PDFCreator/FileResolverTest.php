<?php

namespace MediaWiki\Extension\NSFileRepo\Tests\Integratinon\PDFCreator;

use DOMElement;
use File;
use MediaWiki\Config\Config;
use MediaWiki\Extension\NSFileRepo\Integration\PDFCreator\Utility\FileResolver;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use PHPUnit\Framework\TestCase;
use RepoGroup;

class FileResolverTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\NSFileRepo\Integration\PDFCreator\Utility\FileResolver::execute
	 * @dataProvider provideFileSrcData
	 *
	 * @param string $srcUrl
	 * @param string|null $expectedFilename
	 * @param int|null $expectedNamespace
	 * @param bool $shouldFindFile
	 * @return void
	 */
	public function testExecuteWithVariousSources(
		string $srcUrl,
		?string $expectedFilename,
		?int $expectedNamespace,
		bool $shouldFindFile
	): void {
		// Setup mocks
		$config = $this->createMock( Config::class );
		$config->method( 'get' )->willReturnCallback( static function ( $key ) {
			$configMap = [
				'Server' => 'https://example.com',
				'ThumbnailScriptPath' => '/thumb_handler.php',
				'UploadPath' => '/images',
				'ScriptPath' => '/w'
			];
			return $configMap[$key] ?? '';
		} );

		$repoGroup = $this->createMock( RepoGroup::class );
		$titleFactory = $this->createMock( TitleFactory::class );

		// Setup title factory to return mock titles
		$titleFactory->method( 'newFromText' )->willReturnCallback(
			function ( $text, $namespace = null ) use ( $expectedNamespace ) {
				$title = $this->createMock( Title::class );
				$title->method( 'getNsText' )->willReturn( $expectedNamespace !== null
					? 'NS' . $expectedNamespace
					: ''
				);
				return $title;
			}
		);

		// Setup repo group to return file or null
		if ( $shouldFindFile ) {
			$file = $this->createMock( File::class );
			$file->method( 'getName' )->willReturn( $expectedFilename );

			// For archived files, setup findFile to return null and mock the local repo
			if ( strpos( $expectedFilename, '!' ) !== false ) {
				$repoGroup->method( 'findFile' )->willReturn( null );
				$localRepo = $this->createMock( \LocalRepo::class );
				$localRepo->method( 'newFromArchiveName' )->willReturn( $file );
				$repoGroup->method( 'getLocalRepo' )->willReturn( $localRepo );
			} else {
				$repoGroup->method( 'findFile' )->willReturn( $file );
			}
		} else {
			$repoGroup->method( 'findFile' )->willReturn( null );
		}

		$fileResolver = new FileResolver( $config, $repoGroup, $titleFactory );

		$mockElement = $this->createMock( DOMElement::class );
		$mockElement->method( 'getAttribute' )->willReturn( $srcUrl );

		$result = $fileResolver->execute( $mockElement );

		if ( $shouldFindFile ) {
			$this->assertNotNull( $result, "Expected to find a file for URL: $srcUrl" );
			$this->assertInstanceOf( File::class, $result );
		} else {
			$this->assertNull( $result, "Expected no file for URL: $srcUrl" );
		}
	}

	/**
	 * Data provider for testExecuteWithVariousSources
	 *
	 * @return array[]
	 */
	public function provideFileSrcData(): array {
		return [
			'simple file with namespace path' => [
				'srcUrl' => 'https://example.com/images/2023/a/bc/Example.png',
				'expectedFilename' => 'Example.png',
				'expectedNamespace' => 2023,
				'shouldFindFile' => true
			],
			'thumbnail with namespace path' => [
				'srcUrl' => 'https://example.com/images/thumb/2023/a/bc/Example.png/200px-Example.png',
				'expectedFilename' => 'Example.png',
				'expectedNamespace' => 2023,
				'shouldFindFile' => true
			],
			'file without namespace (standard MediaWiki hash path)' => [
				'srcUrl' => 'https://example.com/images/a/bc/Example.png',
				'expectedFilename' => 'Example.png',
				'expectedNamespace' => null,
				'shouldFindFile' => true
			],
			'thumbnail without namespace' => [
				'srcUrl' => 'https://example.com/images/thumb/a/bc/Example.png/150px-Example.png',
				'expectedFilename' => 'Example.png',
				'expectedNamespace' => null,
				'shouldFindFile' => true
			],
			'file with query parameters' => [
				'srcUrl' => 'https://example.com/images/2023/a/bc/Example.png?version=123',
				'expectedFilename' => 'Example.png',
				'expectedNamespace' => 2023,
				'shouldFindFile' => true
			],
			'url encoded filename' => [
				'srcUrl' => 'https://example.com/images/2023/a/bc/Example%20File.png',
				'expectedFilename' => 'Example File.png',
				'expectedNamespace' => 2023,
				'shouldFindFile' => true
			],
			'relative path with namespace' => [
				'srcUrl' => '/images/2023/a/bc/Document.pdf',
				'expectedFilename' => 'Document.pdf',
				'expectedNamespace' => 2023,
				'shouldFindFile' => true
			],
			'relative path without namespace' => [
				'srcUrl' => '/images/f/ab/Image.jpg',
				'expectedFilename' => 'Image.jpg',
				'expectedNamespace' => null,
				'shouldFindFile' => true
			],
			'file not found' => [
				'srcUrl' => 'https://example.com/images/2023/x/yz/NonExistent.png',
				'expectedFilename' => null,
				'expectedNamespace' => 2023,
				'shouldFindFile' => false
			],
			'thumbnail with numeric hash' => [
				'srcUrl' => 'https://example.com/images/thumb/2024/0/1a/File.png/100px-File.png',
				'expectedFilename' => 'File.png',
				'expectedNamespace' => 2024,
				'shouldFindFile' => true
			],
			'archived file with namespace' => [
				'srcUrl' => 'https://example.com/images/archive/3002/e/ef/20251121111456%21A.pdf',
				'expectedFilename' => '20251121111456!A.pdf',
				'expectedNamespace' => 3002,
				'shouldFindFile' => true
			],
			'archived file with namespace (unencoded)' => [
				'srcUrl' => 'https://example.com/images/archive/3002/e/ef/20251121111456!A.pdf',
				'expectedFilename' => '20251121111456!A.pdf',
				'expectedNamespace' => 3002,
				'shouldFindFile' => true
			],
			'archived file without namespace' => [
				'srcUrl' => 'https://example.com/images/archive/a/bc/20201215093045!Document.pdf',
				'expectedFilename' => '20201215093045!Document.pdf',
				'expectedNamespace' => null,
				'shouldFindFile' => true
			],
			'archived image with namespace' => [
				'srcUrl' => 'https://example.com/images/archive/2023/f/ab/20230101120000!Image.jpg',
				'expectedFilename' => '20230101120000!Image.jpg',
				'expectedNamespace' => 2023,
				'shouldFindFile' => true
			],
			'archived file with complex filename' => [
				'srcUrl' => 'https://example.com/images/archive/3002/a/bc/20251121111456%21My_Document%20File.pdf',
				'expectedFilename' => '20251121111456!My_Document File.pdf',
				'expectedNamespace' => 3002,
				'shouldFindFile' => true
			],
			'relative path archived file with namespace' => [
				'srcUrl' => '/images/archive/3002/e/ef/20251121111456!A.pdf',
				'expectedFilename' => '20251121111456!A.pdf',
				'expectedNamespace' => 3002,
				'shouldFindFile' => true
			]
		];
	}

	/**
	 * @covers \MediaWiki\Extension\NSFileRepo\Integration\PDFCreator\Utility\FileResolver::execute
	 *
	 * @return void
	 */
	public function testExecuteReturnsNullForInvalidPath(): void {
		$config = $this->createMock( Config::class );
		$config->method( 'get' )->willReturn( '' );

		$repoGroup = $this->createMock( RepoGroup::class );
		$repoGroup->method( 'findFile' )->willReturn( null );

		$titleFactory = $this->createMock( TitleFactory::class );

		$fileResolver = new FileResolver( $config, $repoGroup, $titleFactory );

		$mockElement = $this->createMock( DOMElement::class );
		$mockElement->method( 'getAttribute' )->willReturn( 'invalid-path' );

		$result = $fileResolver->execute( $mockElement );

		$this->assertNull( $result );
	}
}
