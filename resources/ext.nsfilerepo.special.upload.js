( function () {
	const namespaces = mw.config.get( 'nsfilerepoNamespaces' );

	if ( !window.mw || !mw.loader ) {
		return;
	}

	mw.loader.using( 'mediawiki.special.upload' ).then( waitForDialog );

	function waitForDialog() {
		if ( mw.Upload && mw.Upload.Dialog ) {
			patchDialog();
			return;
		}
		setTimeout( waitForDialog, 50 );
	}

	function patchDialog() {
		const DialogProto = mw.Upload.Dialog.prototype;

		if ( DialogProto.nsfrPatched ) {
			return;
		}
		DialogProto.nsfrPatched = true;

		const originalOnSet = DialogProto.onUploadBookletSet;

		DialogProto.onUploadBookletSet = function ( page ) {
			originalOnSet.call( this, page );

			if ( page && page.getName && page.getName() === 'info' ) {
				injectNamespaceSelector( page );
			}
		};
	}

	function injectNamespaceSelector( page ) {
		if ( page.nsfrInjected ) {
			return;
		}
		page.nsfrInjected = true;

		const $fieldset = page.$element.find( '.oo-ui-fieldsetLayout' ).first();
		if ( !$fieldset.length ) {
			return;
		}

		const $nsDropdown = $( '<select>' ).css( { width: '100%' } );
		for ( const [ label, value ] of Object.entries( namespaces ) ) {
			$nsDropdown.append(
				$( '<option>' ).attr( 'value', value ).text( label )
			);
		}

		const $wrapper = $( '<div>' )
			.addClass( 'nsfr-namespace-selector' )
			.css( { marginBottom: '1em' } )
			.append(
				$( '<label>' ).text( mw.message( 'namespace' ).text() ),
				$nsDropdown
			);

		$fieldset.prepend( $wrapper );

		const $filenameInput = page.$element
			.find( '.oo-ui-textInputWidget input' )
			.first();

		if ( !$filenameInput.length ) {
			return;
		}

		function stripNamespace( name ) {
			return name.replace( /^[^:]+:/, '' );
		}

		// Update filename to include selected namespace
		function updateFilename() {
			const nsPrefix = $nsDropdown.val();
			const current = $filenameInput.val() || '';
			const baseName = stripNamespace( current );

			if ( nsPrefix ) {
				$filenameInput.val( nsPrefix + ':' + baseName );
			} else {
				$filenameInput.val( baseName );
			}
		}

		// Event: namespace selection change → rewrite filename
		$nsDropdown.on( 'change', updateFilename );

		// Event: typing in filename → preserve namespace prefix
		$filenameInput.on( 'input', () => {
			setTimeout( updateFilename, 0 );
		} );

		updateFilename();
	}
}() );
