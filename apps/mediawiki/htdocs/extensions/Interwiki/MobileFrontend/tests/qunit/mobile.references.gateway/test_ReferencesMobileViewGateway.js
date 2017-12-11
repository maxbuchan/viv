( function ( $, M ) {

	var ReferencesMobileViewGateway = M.require(
			'mobile.references.gateway/ReferencesMobileViewGateway'
		),
		Page = M.require( 'mobile.startup/Page' ),
		cache = M.require( 'mobile.startup/cache' ),
		MemoryCache = cache.MemoryCache;

	QUnit.module( 'MobileFrontend: mobileView references gateway', {
		setup: function () {
			this.$container = mw.template.get( 'tests.mobilefrontend', 'pageWithStrippedRefs.html' )
				.render().appendTo( '#qunit-fixture' );

			this.page = new Page( {
				el: this.$container,
				title: 'Reftest'
			} );

			this.api = new mw.Api();
			this.sandbox.stub( this.api, 'get' ).returns(
				$.Deferred().resolve( {
					mobileview: {
						sections: []
					}
				} )
			);

			this.referencesGateway = new ReferencesMobileViewGateway( new mw.Api() );
			// We use Page object which calls getUrl which uses config variables.
			this.sandbox.stub( mw.util, 'getUrl' ).returns( '/wiki/Reftest' );
			this.sandbox.stub( this.referencesGateway, 'getReferencesLists' ).returns(
				$.Deferred().resolve( {
					1: '<ol class="references"><li id="cite_note-1">real lazy</li>' +
						'<li id="cite_note-2">real lazy 2</li></ol>'
				} ).promise()
			);
			this.referencesGatewayEmpty = new ReferencesMobileViewGateway( new mw.Api() );
			this.sandbox.stub( this.referencesGatewayEmpty, 'getReferencesLists' ).returns(
				$.Deferred().resolve( {} ).promise()
			);
			this.referencesGatewayRejector = new ReferencesMobileViewGateway( new mw.Api() );
			this.sandbox.stub( this.referencesGatewayRejector, 'getReferencesLists' ).returns(
				$.Deferred().reject().promise()
			);
		}
	} );

	QUnit.test( 'Gateway only hits api once despite multiple calls', 1, function ( assert ) {
		var gatewayHitsApi = new ReferencesMobileViewGateway( this.api, new MemoryCache() );
		return gatewayHitsApi.getReferencesLists( this.page ).then( function () {
			gatewayHitsApi.getReferencesLists( this.page );
			gatewayHitsApi.getReferencesLists( this.page );
			assert.strictEqual( this.api.get.calledOnce, true, 'The API should only ever be hit once.' );
		}.bind( this ) );
	} );

	QUnit.test( 'checking good reference', 1, function ( assert ) {
		var done = assert.async( 1 );
		this.referencesGateway.getReference( '#cite_note-1', this.page ).done( function ( ref ) {
			assert.strictEqual( ref.text, 'real lazy' );
			done();
		} );
	} );

	QUnit.test( 'checking good reference (subsequent calls)', 1, function ( assert ) {
		var done = assert.async( 1 );
		this.referencesGateway.getReference( '#cite_note-1', this.page );
		this.referencesGateway.getReference( '#cite_note-2', this.page ).done( function ( ref ) {
			assert.strictEqual( ref.text, 'real lazy 2' );
			done();
		} );
	} );

	QUnit.test( 'checking bad reference', 1, function ( assert ) {
		var done = assert.async( 1 );
		this.referencesGateway.getReference( '#cite_note-bad', this.page ).done( function ( ref ) {
			assert.ok( ref === false, 'When bad id given false returned.' );
			done();
		} );
	} );

	QUnit.test( 'checking reference on non-existent page', 1, function ( assert ) {
		var done = assert.async( 1 );
		this.referencesGatewayEmpty.getReference( '#cite_note-bad', this.page ).done( function ( ref ) {
			assert.ok( ref === false,
				'When getReferencesElement returns empty list of elements reference is false.' );
			done();
		} );
	} );

	QUnit.test( 'checking reference when gateway rejects', 1, function ( assert ) {
		var done = assert.async( 1 );
		this.referencesGatewayRejector.getReference( '#cite_note-bad-2', this.page ).fail( function () {
			assert.ok( true, 'getReference is rejected if API query fails' );
			done();
		} );
	} );
}( jQuery, mw.mobileFrontend ) );
