( function ( mw ) {

	mw.hook( 'enhancedUpload.makeParamProcessor' ).add( ( paramsProcessor ) => {
		paramsProcessor.processor = new nsfr.EnhancedUploadParamsProcessor();
	} );

}( mediaWiki ) );
