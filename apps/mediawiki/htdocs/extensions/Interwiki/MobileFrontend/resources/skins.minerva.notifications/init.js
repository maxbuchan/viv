/*
 * This code loads the necessary modules for the notifications overlay, not to be confused
 * with the Toast notifications defined by common/toast.js.
 */
( function ( M, $, mw ) {
	var mainMenu = M.require( 'skins.minerva.scripts/skin' ).getMainMenu(),
		$btn = $( '#secondary-button.user-button' ).parent(),
		router = require( 'mediawiki.router' ),
		overlayManager = M.require( 'skins.minerva.scripts/overlayManager' ),
		icons = M.require( 'mobile.startup/icons' );

	/**
	 * Loads a ResourceLoader module script. Shows ajax loader whilst loading.
	 * @method
	 * @ignore
	 * FIXME: Upstream to mw.mobileFrontend and reuse elsewhere
	 * @param {string} moduleName Name of a module to fetch
	 * @return {jQuery.Deferred}
	 */
	function loadModuleScript( moduleName ) {
		var d = $.Deferred(),
			$spinner = $( icons.spinner().toHtmlString() );

		$btn.hide().after( $spinner );
		mw.loader.using( moduleName, function () {
			// FIXME: Some code uses the loading class while other code uses the
			// spinner class. Make all code consistent so it's easier to change.
			$spinner.remove();
			$btn.show();
			d.resolve();
		} );
		return d;
	}

	// Once the DOM is loaded hijack the notifications button to display an overlay rather
	// than linking to Special:Notifications.
	$( function () {
		$btn.on( 'click', function () {
			router.navigate( '#/notifications' );
			// Important that we also prevent propagation to avoid interference with events that may be
			// binded on #mw-mf-page-center that close overlay
			return false;
		} );

		/**
		 * Load the notification overlay.
		 * @method
		 * @ignore
		 * @private
		 * @uses NotificationsOverlay
		 * @return {jQuery.Deferred} with an instance of NotificationsOverlay
		 */
		function loadNotificationOverlay() {
			var result = $.Deferred();
			loadModuleScript( 'mobile.notifications.overlay' ).done( function () {
				var NotificationsOverlay = M.require( 'mobile.notifications.overlay/NotificationsOverlay' );
				result.resolve(
					new NotificationsOverlay( {
						$badge: $btn,
						count: parseInt( $btn.find( 'span' ).text(), 10 )
					} )
				);
			} );

			return result;
		}

		overlayManager.add( /^\/notifications$/, function () {
			return loadNotificationOverlay().done( function ( overlay ) {
				mainMenu.openNavigationDrawer( 'secondary' );
				overlay.on( 'hide', function () {
					mainMenu.closeNavigationDrawers();
					$( '#mw-mf-page-center' ).off( '.secondary' );
				} );

				$( '#mw-mf-page-center' ).one( 'click.secondary', function () {
					router.back();
				} );
			} );
		} );
	} );
}( mw.mobileFrontend, jQuery, mw ) );
