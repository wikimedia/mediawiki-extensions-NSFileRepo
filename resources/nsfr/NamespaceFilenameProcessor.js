window.nsfr = window.nsfr || {};

nsfr.NamespaceFilenameProcessor = function () {};

/**
 * @inheritDoc
 */
nsfr.NamespaceFilenameProcessor.prototype.initializeFilename = function () {
	let filename = mw.config.get( 'wgPageName' ) + '-' + ( Math.floor( Math.random() * 100000000 ) + 1 );
	// filename must only contain alphanumeric characters, colons (for namespaces),
	// dashes and underscores
	filename = this.sanitizeFilename( filename );

	return filename;
};

/**
 * @inheritDoc
 */
nsfr.NamespaceFilenameProcessor.prototype.validateFilename = function ( filename ) {
	if ( !filename || !filename.match( /^[\w,-.:\s]+$/ ) ) {
		return false;
	}

	const colonCount = ( filename.match( /:/g ) || [] ).length;
	if ( colonCount > 1 ) {
		return false;
	}

	const prefix = ( colonCount === 1 ) ?
		filename.split( ':' )[ 0 ] :
		null;

	if ( prefix && !this.isRegisteredNamespace( prefix ) ) {
		return false;
	}

	return true;
};

nsfr.NamespaceFilenameProcessor.prototype.isRegisteredNamespace = function ( prefix ) {
	if ( !prefix ) {
		return false;
	}

	const namespaces = mw.config.get( 'wgFormattedNamespaces' );
	for ( const nsId in namespaces ) {
		if ( namespaces[ nsId ] === prefix ) {
			return true;
		}
	}

	return false;
};

/**
 * @inheritDoc
 */
nsfr.NamespaceFilenameProcessor.prototype.sanitizeFilename = function ( filename ) {
	// filename must only contain alphanumeric characters, colons (for namespaces),
	// dashes and underscores
	filename = filename.replace( /[^a-zA-Z0-9_:-]/g, '_' );

	return filename;
};
