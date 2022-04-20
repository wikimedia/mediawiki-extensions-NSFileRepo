( function( mw, $, d, undefined ){

	var targetNamespaceSelector;
	mw.hook( 'upload.init' ).add( function( $container ) {
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

	mw.hook( 'upload.getUploadParams' ).add( function( params ) {
		var namespaces = mw.config.get( 'wgFormattedNamespaces' );
		var selectedNamespace = targetNamespaceSelector.getValue();

		var prefix = '';
		if( selectedNamespace !== '0' ) { //NS_MAIN
			prefix = namespaces[selectedNamespace] + ':';
		}
		var name = params.filename;
		newName = prefix + name;
		params.filename = newName;
	} );

})( mediaWiki, jQuery, document );
