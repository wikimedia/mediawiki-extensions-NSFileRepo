<?php

namespace MediaWiki\Extension\NSFileRepo\Integration\PDFCreator\Processor;

use MediaWiki\Config\Config;
use MediaWiki\Extension\NSFileRepo\Integration\PDFCreator\Utility\AttachmentFinder;
use MediaWiki\Extension\PDFCreator\IProcessor;
use MediaWiki\Extension\PDFCreator\Utility\AttachmentUrlUpdater;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Title\TitleFactory;
use RepoGroup;

class AttachmentProcessor implements IProcessor {

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
		TitleFactory $titleFactory, Config $config, RepoGroup $repoGroup ) {
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
		$attachmentFinder = new AttachmentFinder(
			$this->titleFactory, $this->config, $this->repoGroup
		);
		$results = $attachmentFinder->execute( $pages, $attachments );

		$ImageUrlUpdater = new AttachmentUrlUpdater( $this->titleFactory );
		$ImageUrlUpdater->execute( $pages, $attachments );

		/** @var WikiFileResource */
		foreach ( $results as $result ) {
			$filename = $result->getFilename();
			$attachments[$filename] = $result->getAbsolutePath();
		}
	}

	/**
	 * @return int
	 */
	public function getPosition(): int {
		return 80;
	}
}
