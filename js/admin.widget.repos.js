if (typeof jQuery !== 'undefined') {
	jQuery(
		function () {

			try {
				var widget = $( '#afsa_dashboard_widget' );
				widget.insertBefore(
					widget.parent().children( '.postbox' ).get( 0 )
				);

			} catch (e) {

			}

		}
	);
}
