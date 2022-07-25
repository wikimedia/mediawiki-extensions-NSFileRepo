( function () {
	QUnit.module( 'ext.nsfilerepo.upload.paramsProcessor.test', QUnit.newMwEnvironment() );

	var inputWithoutOption = [
		[
			{
				prefix: 'Special:',
				filename: 'Test.txt',
				format: 'txt',
				ignorewarnings: true,
				comment: 'TestComment'
			},
			{
				name: 'Test.txt'
			},
			'<div><div>Test</div></div>'
		],
		[
			{
				prefix: 'Special:',
				filename: 'Test.txt',
				format: 'txt',
				ignorewarnings: true,
				comment: 'TestComment'
			},
			{
				name: 'Test.txt'
			},
			'<div><div>Test</div></div>'
		],
		[
			{
				prefix: 'Special:DEF',
				filename: 'Test.txt',
				format: 'txt',
				ignorewarnings: true,
				comment: 'TestComment'
			},
			{
				name: 'Test.txt'
			},
			'<div><div>Test</div></div>'
		],
		[
			{
				prefix: 'Special:DEF:',
				filename: 'Test.txt',
				format: 'txt',
				ignorewarnings: true,
				comment: 'TestComment'
			},
			{
				name: 'Test.txt'
			},
			'<div><div>Test</div></div>'
		]
	];
	var inputWithOption = [
		[
			{
				prefix: 'Special:',
				filename: 'Test.txt',
				format: 'txt',
				ignorewarnings: true,
				comment: 'TestComment'
			},
			{
				name: 'Test.txt'
			},
			'<div><div>Test</div></div>'
		],
		[
			{
				prefix: 'Special:',
				filename: 'Test.txt',
				format: 'txt',
				ignorewarnings: true,
				comment: 'TestComment'
			},
			{
				name: 'Test.txt'
			},
			'<div><div>Test</div></div>'
		],
		[
			{
				prefix: 'Special:DEF',
				filename: 'Test.txt',
				format: 'txt',
				ignorewarnings: true,
				comment: 'TestComment'
			},
			{
				name: 'Test.txt'
			},
			'<div><div>Test</div></div>'
		],
		[
			{
				prefix: 'Special:DEF:',
				filename: 'Test.txt',
				format: 'txt',
				ignorewarnings: true,
				comment: 'TestComment'
			},
			{
				name: 'Test.txt'
			},
			'<div><div>Test</div></div>'
		]
	];
	var expectedParamsWithoutOption = [
		{
			filename: 'Special:Test.txt',
			format: 'txt',
			ignorewarnings: true,
			comment: 'TestComment',
			prefix: 'Special:'
		},
		{
			filename: 'Special:Test.txt',
			format: 'txt',
			ignorewarnings: true,
			comment: 'TestComment',
			prefix: 'Special:'
		},
		{
			filename: 'Special:DEFTest.txt',
			format: 'txt',
			ignorewarnings: true,
			comment: 'TestComment',
			prefix: 'Special:DEF'
		},
		{
			filename: 'Special:DEF_Test.txt',
			format: 'txt',
			ignorewarnings: true,
			comment: 'TestComment',
			prefix: 'Special:DEF:'
		}
	];
	var expectedParamsWithOption = [
		{
			filename: 'Special:Test.txt',
			format: 'txt',
			ignorewarnings: true,
			comment: 'TestComment',
			namespace: '0',
			prefix: 'Special:'
		},
		{
			filename: 'Special:Test.txt',
			format: 'txt',
			ignorewarnings: true,
			comment: 'TestComment',
			namespace: '0',
			prefix: 'Special:'
		},
		{
			filename: 'Special:DEFTest.txt',
			format: 'txt',
			ignorewarnings: true,
			comment: 'TestComment',
			namespace: '0',
			prefix: 'Special:DEF'
		},
		{
			filename: 'Special:DEF_Test.txt',
			format: 'txt',
			ignorewarnings: true,
			comment: 'TestComment',
			namespace: '0',
			prefix: 'Special:DEF:'
		}
	];

	QUnit.test( 'ext.nsfilerepo.upload.paramsProcessor.test', function ( assert ) {
		for ( var i = 0; i < 4; i++ ) {
			var processor = new nsfr.EnhancedUploadParamsProcessor();
			var params = inputWithoutOption[ i ][ 0 ];
			var retrievedParams = processor.getParams( params, inputWithoutOption[ i ][ 1 ], true );
			assert.deepEqual( retrievedParams, expectedParamsWithoutOption[ i ], 'params' );
		}
	} );

	QUnit.test( 'ext.nsfilerepo.upload.paramsProcessor.test-options', function ( assert ) {
		for ( var i = 0; i < 4; i++ ) {
			var params = inputWithOption[ i ][ 0 ];
			var processor = new nsfr.EnhancedUploadParamsProcessor();
			processor.init();
			var retrievedParams = processor.getParams( params, inputWithOption[ i ][ 1 ], false );
			assert.deepEqual( retrievedParams, expectedParamsWithOption[ i ], 'params' );
		}
	} );

}( jQuery ) );
