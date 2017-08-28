<?php

namespace NSFileRepo\Hooks;

class UserCan {

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
	 * @var \Title
	 */
	protected $title = null;

	/**
	 * @var \User
	 */
	protected $user = null;

	/**
	 * @var string
	 */
	protected $action = '';

	/**
	 * @var boolean
	 */
	protected $result = true;

	/**
	 * Check individual namespace protection using Extension:Lockdown
	 * @param Title $title
	 * @param user $user
	 * @param string $action
	 * @param mixed $result
	 * @return boolean
	 */
	public static function handle( &$title, &$user, $action, &$result ) {
		$instance = new self(
			\RequestContext::getMain(),
			new \MultiConfig( [
				new \NSFileRepo\Config(),
				\MediaWiki\MediaWikiServices::getInstance()->getMainConfig()
			] ),
			$title,
			$user,
			$action,
			$result
		);

		return $instance->process();
	}

	/**
	 * See static method "handle"
	 * @param \IContextSource $context
	 * @param \Config $config
	 * @param \Title $title
	 * @param \User $user
	 * @param string $action
	 * @param boolean $result
	 */
	public function __construct( \IContextSource $context, \Config $config, \Title &$title, \User &$user, $action, &$result ) {
		$this->context = $context;
		$this->config = $config;
		$this->title = $title;
		$this->user = $user;
		$this->action = $action;
		$this->result =& $result;
	}

	/**
	 *
	 * @return boolean
	 */
	public function process() {
		$whitelistRead = $this->config->get( 'WhitelistRead' );
		if ( $whitelistRead !== false && in_array( $this->title->getPrefixedText(), $whitelistRead ) ) {
			return true;
		}

		if( $this->title->getNamespace() !== NS_FILE ) {
			return true;
		}

		$ntitle = \Title::newFromText( $this->title->getDBkey() );
		$ret_val = true;

		//When image title cannot be created, due to upload errors,
		//$this->title->getDBKey() is empty, resulting in an invaid
		//title object in Title::newFromText
		if( !$ntitle instanceof \Title ) {
			return $ret_val;
		}

		//Additional check for NS_MAIN: If a user is not allowed to read NS_MAIN he should also be not allowed
		//to view files with no namespace-prefix as they are logically assigned to namespace NS_MAIN
		$titleIsNSMAIN =  $ntitle->getNamespace() === NS_MAIN;
		$titleNSaboveThreshold = $ntitle->getNamespace() > $this->config->get( 'NamespaceThreshold' );
		if( $titleIsNSMAIN || $titleNSaboveThreshold ) {
			$ret_val = lockdownUserPermissionsErrors( $ntitle, $this->user, $this->action, $this->result );
		}

		$this->result = null;
		return $ret_val;
	}
}
