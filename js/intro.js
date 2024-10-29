$ = jQuery;



jQuery( document ).ready(
	function($) {

		$( '.afsa_create_account' ).click(
			create_account
		);

		$( '.afsa_warpto' ).click(
			function() {
				var d = $( this ).data();

				if (d && typeof d.to !== 'undefined') {
					typeof d.target === 'undefined' ?
						window.location = d.to :
						window.open( d.to, d.target );
				}
			}
		);

		$( '#input_self_visits_hidden' ).click(
			function() {
				var enabled = $( '#input_self_visits_hidden:checked' ).val() || 0;

				var
					back_url = window.location.href.replace( /\&afsa_result_ip_removal=[0-9]/, '' ),
					url      = 'https://www.afsanalytics.com/removeme.php?id=' + '000000002' +
					'&back=' + encodeURIComponent( back_url ) +
					'&state=' + enabled;

				// console.log('XXXX', enabled, url);

				window.location = url;

			}
		)

	}
);



function create_account() {
	var
		u = 'https://dev.afsanalytics.com/wordpress/signup',
		s = AFSA_site_infos || null;

	if ( ! s) {
		return;
	}

	u += '?sitename=' + encodeURIComponent( s.name ) +
		'&siteurl=' + encodeURIComponent( s.url ) +
		'&siteemail=' + encodeURIComponent( s.email ) +
		'&sitelang=' + encodeURIComponent( s.lng );

	if (s.currency || null) {
		u += '&currency=' + encodeURIComponent( s.currency );
	}

	if (s.tz || null) {
		u += '&tz=' + encodeURIComponent( s.tz );
	}

	if (s.desc || null) {
		u += '&sitedes=' + encodeURIComponent( s.desc );
	}

	u += '&cms=' + encodeURIComponent( s.cms ) +
		'&afsa_return_url=' + encodeURIComponent( s.return_url ) +
		'&afsa_state=' + encodeURIComponent( s.state ) +
		'&wp_version=' + encodeURIComponent( s.wp_version );

	if (s.woo_version || null) {
		u += '&woo_version=' + encodeURIComponent( s.woo_version );
	}

	if (s.plugin_version || null) {
		u += '&plugin_version=' + encodeURIComponent( s.plugin_version );
	}

	if (s.paa_rc || null) {
		u += '&paa_rc=' + encodeURIComponent( s.paa_rc );
	}

	window.location = u;
}
