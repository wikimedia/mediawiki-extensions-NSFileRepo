( function () {
	QUnit.module( 'ext.nsfilerepo.upload.paramsProcessor.test', QUnit.newMwEnvironment() );

	const inputWithoutOption = [
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
	const inputWithOption = [
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
	const expectedParamsWithoutOption = [
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
	const expectedParamsWithOption = [
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

	QUnit.test( 'ext.nsfilerepo.upload.paramsProcessor.test', ( assert ) => {
		for ( let i = 0; i < 4; i++ ) {
			const processor = new nsfr.EnhancedUploadParamsProcessor();
			const params = inputWithoutOption[ i ][ 0 ];
			const retrievedParams = processor.getParams( params, inputWithoutOption[ i ][ 1 ], true );
			assert.deepEqual( retrievedParams, expectedParamsWithoutOption[ i ], 'params' );
		}
	} );

	QUnit.test( 'ext.nsfilerepo.upload.paramsProcessor.test-options', ( assert ) => {
		for ( let i = 0; i < 4; i++ ) {
			const params = inputWithOption[ i ][ 0 ];
			const processor = new nsfr.EnhancedUploadParamsProcessor();
			const retrievedParams = processor.getParams( params, inputWithOption[ i ][ 1 ], false );
			assert.deepEqual( retrievedParams, expectedParamsWithOption[ i ], 'params' );
		}
	} );

}( jQuery ) );
