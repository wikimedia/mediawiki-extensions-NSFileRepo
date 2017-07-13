<?php

namespace NSFileRepo\Hooks;

class UploadFormInitDescriptor {

	/**
	 *
	 * @var \Config
	 */
	protected $config = null;

	/**
	 *
	 * @var \IContextSource
	 */
	protected $context = null;
	
	/**
	 *
	 * @var array
	 */
	protected $descriptor = [];

	/**
	 *
	 * @param \IContextSource $context
	 * @param \Config $config
	 * @param array $descriptor
	 */
	public function __construct( \IContextSource $context, \Config $config, &$descriptor ) {
		$this->context = $context;
		$this->config = $config;
		$this->descriptor =& $descriptor;
	}

	/**
	 * Add fields to Special:Upload
	 * @param array $descriptor
	 * @return boolean
	 */
	public static function handle( &$descriptor ) {
		$instance = new self(
			\RequestContext::getMain(),
			new \NSFileRepo\Config(),
			$descriptor
		);

		return $instance->process();
	}

	public function process() {
		$this->setDefaultNamespace();
		$this->setNamespaceSelectOptions();
		$this->setFieldDefinitions();
		$this->modifyDescriptor();

		return true;
	}

	protected $selectedNamespace = '';

	protected function setDefaultNamespace() {
		$this->selectedNamespace = '';
		//"wpDestFile" is set on query string. e.g after click on redlink or on re-upload
		if( !empty( $this->descriptor['DestFile']['default'] ) ) {
			$target = \Title::newFromText( $this->descriptor['DestFile']['default'] );
			$this->descriptor['DestFile']['default'] = $target->getText();
			$this->selectedNamespace = str_replace(  ' ', '_', $target->getNsText() );
		}
	}

	protected $namespaceSelectOptions = [];

	protected function setNamespaceSelectOptions() {
		$namespaceList = new \NSFileRepo\NamespaceList(
			$this->context->getUser(),
			$this->config,
			$this->context->getLanguage()
		);

		foreach( $namespaceList->getEditable() as $nsId => $namespace ) {
			$this->namespaceSelectOptions[$namespace->getDisplayName()]
				= $namespace->getCanonicalName();
		}
	}

	protected $fieldDef = [];

	protected function setFieldDefinitions() {
		$this->fieldDef = [
			'NSFR_Namespace' => [
				'label'    => wfMessage('namespace')->plain(),
				'section'  => 'description',
				'class'    => 'HTMLSelectField',
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
	}

	protected function modifyDescriptor() {
		$pos = array_search( 'UploadDescription', array_keys( $this->descriptor ) );

		$this->descriptor =
			array_slice( $this->descriptor, 0, $pos, true ) +
			$this->fieldDef +
			array_slice( $this->descriptor, $pos, count( $this->descriptor ) - 1, true ) ;
	}
}