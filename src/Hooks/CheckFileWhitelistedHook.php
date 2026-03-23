<?php

namespace MediaWiki\Extension\NSFileRepo\Hooks;

use MediaWiki\Title\Title;

interface CheckFileWhitelistedHook {

	/**
	 * @param Title $title
	 * @param string $path
	 * @param string $name
	 * @param bool &$whitelisted
	 * @return bool
	 */
	public function onCheckFileWhitelisted( Title $title, string $path, string $name, bool &$whitelisted ): bool;

}
