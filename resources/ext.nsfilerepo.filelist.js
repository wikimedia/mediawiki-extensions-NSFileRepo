mw.hook( 'enhanced.filelist.gridconfig' ).add( function ( columnCfg ) {
	// eslint-disable-next-line camelcase
	columnCfg.namespace_text = {
		headerText: mw.message( 'nsfilerepo-enhanced-filelist-grid-namespace-title' ).text(),
		type: 'text',
		sortable: true,
		filter: { type: 'string' },
		hidden: !mw.user.options.get( 'filelist-show-namespace_text' )
	};
} );
