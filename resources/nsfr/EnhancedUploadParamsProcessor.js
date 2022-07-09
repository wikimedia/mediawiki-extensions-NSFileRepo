window.nsfr = window.nsfr || {};

nsfr.EnhancedUploadParamsProcessor = function () {
	this.config = require( './config.json' );
};

OO.initClass( nsfr.EnhancedUploadParamsProcessor );

nsfr.EnhancedUploadParamsProcessor.prototype.init = function () {
	var excludeNS = this._getInvalidNamespaces();
	this.targetNamespaceSelector = new mw.widgets.NamespaceInputWidget( {
		exclude: excludeNS,
		dropdown: {
			$overlay: true
		}
	} );

	var namespaceInputField = new OO.ui.FieldLayout( this.targetNamespaceSelector, {
		label: mw.message( 'nsfilerepo-upload-file-namespace-namespaceselector-label' ).plain(),
		align: 'left'
	} );

	return namespaceInputField;
};

nsfr.EnhancedUploadParamsProcessor.prototype.setNamespaceValue = function ( prefix ) {
	var namespace = prefix.split( ':' );
	var namespaces = mw.config.get( 'wgFormattedNamespaces' );
	for ( var namespaceId in namespaces ) {
		if ( namespaces[ namespaceId ] === namespace[ 0 ] ) {
			this.targetNamespaceSelector.setValue( namespaceId );
			break;
		}
	}
};

nsfr.EnhancedUploadParamsProcessor.prototype.getParams = function ( params, item, skipOption ) {
	var skipNamespace = skipOption || false;
	if ( !skipNamespace ) {
		params.namespace = this.targetNamespaceSelector.getValue();
	}
	params.filename = this._makeUploadFilenameFromParams( params );
	return params;
};

nsfr.EnhancedUploadParamsProcessor.prototype._makeUploadFilenameFromParams = function ( params ) {
	var prefix = params.prefix || '';
	var filename = params.filename || '';
	var namespace = params.namespace || false;
	var namespaces = mw.config.get( 'wgFormattedNamespaces' );

	var prefixNamespace = '';
	if ( params.namespace ) {
		prefixNamespace = namespaces[ namespace ];
	}
	var prefixParts = prefix.split( ':' );
	if ( prefixParts.length > 1 ) {
		prefixNamespace = prefixParts[ 0 ];
		prefixParts.splice( 0, 1 );
	}

	var prefixStub = prefixParts.join( ':' );
	prefixStub = prefixStub.replace( ':', '_' );

	var validPrefixNamespace = false;
	for ( var index in namespaces ) {
		if ( namespaces[ index ] === prefixNamespace ) {
			namespace = index;
			validPrefixNamespace = true;
			break;
		}
	}

	if ( validPrefixNamespace === false ) {
		prefixStub = prefixNamespace + '_' + prefixStub;
		prefixNamespace = '';
	}

	if ( namespace ) {
		if ( ( namespace !== 0 ) && ( namespaces[ namespace ] !== 'undefined' ) ) {
			prefixNamespace = namespaces[ namespace ];
		}
	}

	if ( prefixNamespace !== '' ) {
		prefixNamespace += ':';
	}

	// Avoid double prefixing
	if ( prefixStub === filename.substr( 0, prefixStub.length ) ) {
		prefixStub = '';
	}

	prefix = prefixNamespace + prefixStub;

	return prefix + filename;
};

nsfr.EnhancedUploadParamsProcessor.prototype._getInvalidNamespaces = function () {
	var excludeNS = [];
	var namespaces = mw.config.get( 'wgNamespaceIds' );
	var namespacesThreshold = this.config.egNSFileRepoNamespaceThreshold;
	var namespacesBlacklist = this.config.egNSFileRepoNamespaceBlacklist;
	var skiptalk = this.config.egNSFileRepoSkipTalk;

	for ( var namespace in namespaces ) {
		var nsId = namespaces[ namespace ];
		if ( nsId < namespacesThreshold && nsId !== 0 ) {
			excludeNS.push( nsId );
			continue;
		}
		if ( namespacesBlacklist.includes( nsId ) ) {
			excludeNS.push( nsId );
			continue;
		}
		if ( skiptalk && nsId % 2 !== 0 ) {
			excludeNS.push( nsId );
		}
	}

	return excludeNS;
};
