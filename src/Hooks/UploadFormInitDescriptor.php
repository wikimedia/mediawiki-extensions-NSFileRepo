<?php

namespace NSFileRepo\Hooks;

use MediaWiki\Config\Config as MediaWikiConfig;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\NSFileRepo\Config;
use MediaWiki\Extension\NSFileRepo\NamespaceList;
use MediaWiki\HTMLForm\Field\HTMLSelectField;

class UploadFormInitDescriptor {

	/**
	 *
	 * @var MediaWikiConfig
	 */
	protected $config = null;

	/**
	 *
	 * @var IContextSource
	 */
	protected $context = null;

	/**
	 *
	 * @var array
	 */
	protected $descriptor = [];

	/** @var array */
	protected $namespaceSelectOptions = [];

	/** @var string */
	protected $selectedNamespace = '';

	/** @var array */
	protected $fieldDef = [];

	/**
	 *
	 * @param IContextSource $context
	 * @param MediaWikiConfig $config
	 * @param array &$descriptor
	 */
	public function __construct( IContextSource $context, MediaWikiConfig $config, &$descriptor ) {
		$this->context = $context;
		$this->config = $config;
		$this->descriptor =& $descriptor;
	}

	/**
	 * Add fields to Special:Upload
	 * @param array &$descriptor
	 * @return bool
	 */
	public static function handle( &$descriptor ) {
		$instance = new self(
			RequestContext::getMain(),
			new Config(),
			$descriptor
		);

		return $instance->process();
	}

	/**
	 * @return true
	 */
	public function process() {
		$this->setDefaultNamespace();
		$this->setNamespaceSelectOptions();
		$this->setFieldDefinitions();
		$this->modifyDescriptor();

		return true;
	}

	/**
	 * @return void
	 */
	protected function setDefaultNamespace() {
		$this->selectedNamespace = '-';
		// "wpDestFile" is set on query string. e.g after click on redlink or on re-upload
		if ( !empty( $this->descriptor['DestFile']['default'] ) ) {
			$target = $this->descriptor['DestFile']['default'];
			$target = str_replace( '_', ' ', $target );
			$targetPieces = explode( ':', $target );

			$nsText = '';
			if ( count( $targetPieces ) > 1 ) {
				$nsText = str_replace( ' ', '_', $targetPieces[0] );
				$target = $targetPieces[1];
			}

			$this->descriptor['DestFile']['default'] = $target;
			$this->selectedNamespace = $nsText;
		}
	}

	/**
	 * @return void
	 */
	protected function setNamespaceSelectOptions() {
		$namespaceList = new NamespaceList(
			$this->context->getUser(),
			$this->config,
			$this->context->getLanguage()
		);

		foreach ( $namespaceList->getEditable() as $nsId => $namespace ) {
			$this->namespaceSelectOptions[$namespace->getDisplayName()]
				= $namespace->getCanonicalName();
			if ( $nsId === NS_MAIN ) {
				$this->namespaceSelectOptions[$namespace->getDisplayName()] = '-';
			}
		}
	}

	/**
	 * @return void
	 */
	protected function setFieldDefinitions() {
		$this->fieldDef = [
			'NSFR_Namespace' => [
				'label'    => wfMessage( 'namespace' )->plain(),
				'section'  => 'description',
				'class'    => HTMLSelectField::class,
				'options'  => $this->namespaceSelectOptions,
				'required' => true,
				'default' => $this->selectedNamespace
			],
			'NSFR_DestFile' => [
				'type' => 'text',
				'section' => 'description',
				'label-message' => 'nsfilerepo-upload-target',
				'size' => 60,
				'default' => '',
				'readonly' => true,
				'nodata' => false,
			],
		];

		// Prevent change of this fields value when it's a reupload
		if ( isset( $this->descriptor['ForReUpload'] ) ) {
			$this->fieldDef['NSFR_Namespace']['disabled'] = true;
			$this->fieldDef['NSFR_Namespace']['help-message']
				= 'nsfilerepo-reupload-namespaceselector-disabled-helptext';
		}
	}

	/**
	 * @return void
	 */
	protected function modifyDescriptor() {
		$pos = array_search( 'UploadDescription', array_keys( $this->descriptor ) );

		$this->descriptor =
			array_slice( $this->descriptor, 0, $pos, true ) +
			$this->fieldDef +
			array_slice( $this->descriptor, $pos, count( $this->descriptor ) - 1, true );
	}
}
