<?php

namespace MediaWiki\Extension\NSFileRepo\Integration\PDFCreator\PreProcessor;

use MediaWiki\Config\Config;
use MediaWiki\Extension\NSFileRepo\Integration\PDFCreator\Utility\ImageFinder;
use MediaWiki\Extension\PDFCreator\IPreProcessor;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Extension\PDFCreator\Utility\ImageUrlUpdater;
use MediaWiki\Extension\PDFCreator\Utility\ImageWidthUpdater;
use MediaWiki\Title\TitleFactory;
use RepoGroup;

class ImageProcessor implements IPreProcessor {

	/** @var TitleFactory */
	private $titleFactory;

	/** @var Config */
	private $config;

	/** @var RepoGroup */
	private $repoGroup;

	/**
	 * @param TitleFactory $titleFactory
	 * @param Config $config
	 * @param RepoGroup $repoGroup
	 */
	public function __construct(
		TitleFactory $titleFactory, Config $config, RepoGroup $repoGroup
	) {
		$this->titleFactory = $titleFactory;
		$this->config = $config;
		$this->repoGroup = $repoGroup;
	}

	/**
	 * @param ExportPage[] &$pages
	 * @param array &$images
	 * @param array &$attachments
	 * @param ExportContext|null $context
	 * @param string $module
	 * @param array $params
	 * @return void
	 */
	public function execute(
		array &$pages, array &$images, array &$attachments,
		?ExportContext $context = null, string $module = '', $params = []
	): void {
		$imageFinder = new ImageFinder(
			$this->titleFactory, $this->config, $this->repoGroup
		);
		$results = $imageFinder->execute( $pages, $images );

		$AttachmentUrlUpdater = new ImageUrlUpdater( $this->titleFactory );
		$AttachmentUrlUpdater->execute( $pages, $results );

		$imageWidthUpdater = new ImageWidthUpdater();
		$imageWidthUpdater->execute( $pages );

		/** @var WikiFileResource */
		foreach ( $results as $result ) {
			$filename = $result->getFilename();
			$images[$filename] = $result->getAbsolutePath();
		}
	}

	/**
	 * @return int
	 */
	public function getPosition(): int {
		// This processor must run after PDFCreator\PreProcessor\ObjectProcessor.
		return 50;
	}
}
