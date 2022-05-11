( function ( mw ) {

	var targetNamespaceSelector;
	mw.hook( 'upload.init' ).add( function ( $container, value ) {
		value = value || '';
		targetNamespaceSelector = new mw.widgets.NamespaceInputWidget( {
			exclude: '',
			dropdown: {
				$overlay: true
			}
		} );

		var namespaceInputField = new OO.ui.FieldLayout( targetNamespaceSelector, {
			label: mw.message( 'nsfilerepo-upload-file-namespace-namespaceselector-label' ).plain(),
			align: 'left'
		} );

		$container.$element.prepend( namespaceInputField.$element );
	} );

	mw.hook( 'upload.getUploadParams' ).add( function ( params, defaultPrefix ) {
		var namespaces = mw.config.get( 'wgFormattedNamespaces' );
		var selectedNamespace = targetNamespaceSelector.getValue();
		defaultPrefix = defaultPrefix || '';
		if ( defaultPrefix.length > 0 ) {
			defaultPrefix = defaultPrefix.split( ':' );
			if ( defaultPrefix.length > 1 ) {
				defaultPrefix = defaultPrefix[ 1 ] + '.';
			} else {
				defaultPrefix = defaultPrefix[ 0 ] + '.';
			}
		}

		var prefix = '';
		if ( selectedNamespace !== '0' ) { // NS_MAIN
			prefix = namespaces[ selectedNamespace ] + ':';
		}
		var name = params.filename;
		var newName = prefix + defaultPrefix + name;
		params.filename = newName;
	} );

	mw.hook( 'upload.setNamespaceValue' ).add( function ( prefix ) {
		var namespace = prefix.split( ':' );
		var namespaces = mw.config.get( 'wgFormattedNamespaces' );
		for ( var namespaceId in namespaces ) {
			if ( namespaces[ namespaceId ] === namespace[ 0 ] ) {
				targetNamespaceSelector.setValue( namespaceId );
			}
		}
	} );

	mw.hook( 'upload.getPrefixParams' ).add( function ( params, defaultPrefix ) {
		var namespaces = mw.config.get( 'wgFormattedNamespaces' );
		var namespace = '';
		defaultPrefix = defaultPrefix || '';
		if ( defaultPrefix.length > 0 ) {
			defaultPrefix = defaultPrefix.split( ':' );

			namespace = defaultPrefix[ 0 ];
			if ( defaultPrefix.length > 1 ) {
				defaultPrefix = defaultPrefix[ 1 ] + '.';
			}
		}

		prefix = '';
		namespaceValue = false;
		for ( var namespaceId in namespaces ) {
			if ( namespaces[ namespaceId ] === namespace ) {
				if ( namespaceId !== '0' ) {
					prefix = namespaces[ namespaceId ] + ':';
				}
				namespaceValue = true;
			}
		}
		var name = params.filename;
		var newName = '';
		if ( namespaceValue ) {
			newName = prefix + defaultPrefix + name;
			params.filename = newName;
		} else {
			if ( defaultPrefix.length > 1 ) {
				newName = namespace + ':' + defaultPrefix + name;
			} else {
				newName = defaultPrefix + '.' + name;
			}
			params.filename = newName;
		}
	} );

}( mediaWiki ) );
