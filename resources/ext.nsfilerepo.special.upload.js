( function () {
	const namespaces = mw.config.get( 'nsfilerepoNamespaces' );

	if ( !window.mw || !mw.loader ) {
		return;
	}

	mw.loader.using( 'mediawiki.Upload.Dialog' ).then( waitForDialog );

	function waitForDialog() {
		if ( mw.Upload && mw.Upload.Dialog ) {
			patchDialog();
			return;
		}
		setTimeout( waitForDialog, 50 );
	}

	function patchDialog() {
		const DialogProto = mw.Upload.Dialog.prototype;
		const DialogStatic = mw.Upload.Dialog.static;

		if ( DialogProto.nsfrPatched ) {
			return;
		}
		DialogProto.nsfrPatched = true;

		// Match VisualEditor media dialog behavior: render the info-step back
		if ( Array.isArray( DialogStatic.actions ) ) {
			DialogStatic.actions = DialogStatic.actions.map( ( action ) => {
				if ( action.action === 'cancelupload' ) {
					return Object.assign( {}, action, {
						flags: [ 'safe', 'back' ]
					} );
				}
				return action;
			} );
		}

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

		const namespaceOptions = Object.entries( namespaces ).map( ( [ label, value ] ) => ( {
			data: value,
			label: label
		} ) );

		const namespaceSelector = new OO.ui.DropdownInputWidget( {
			options: namespaceOptions
		} );
		const namespaceSelectorLayout = new OO.ui.FieldLayout( namespaceSelector, {
			label: mw.message( 'namespace' ).text(),
			align: 'top'
		} );
		namespaceSelectorLayout.$element.addClass( 'nsfr-namespace-selector' );

		const $fieldsetGroup = $fieldset.find( '.oo-ui-fieldsetLayout-group' ).first();
		if ( $fieldsetGroup.length ) {
			// Keep the Details legend/title first, then insert namespace as first
			// field inside the fieldset content.
			$fieldsetGroup.prepend( namespaceSelectorLayout.$element );
		} else {
			$fieldset.append( namespaceSelectorLayout.$element );
		}

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
			const nsPrefix = namespaceSelector.getValue();
			const current = $filenameInput.val() || '';
			const baseName = stripNamespace( current );

			if ( nsPrefix ) {
				$filenameInput.val( nsPrefix + ':' + baseName );
			} else {
				$filenameInput.val( baseName );
			}
		}

		// Event: namespace selection change → rewrite filename
		namespaceSelector.on( 'change', updateFilename );

		// Event: typing in filename → preserve namespace prefix
		$filenameInput.on( 'input', () => {
			setTimeout( updateFilename, 0 );
		} );

		updateFilename();
	}
}() );
