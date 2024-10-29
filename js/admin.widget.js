/* global AFSA, s_nonce  */
$ = jQuery;

if (typeof jQuery !== 'undefined') {
	jQuery(
		function () {

				var
						started = false,
						widget  = $( '#afsa_dashboard_widget' );

				AFSA.log = console.log;

				AFSA.log( 'AFSA Admin widget - AFSA DBoard ' + AFSA.version().board );
				AFSA.version();

				declareCustomHooks();

				AFSA.config().set(
					{
						dashboard: {
							parent: '#afsa_container',
							do_not_parse: 0,
							forced_theme: 'main',
							container: {
								template: 'ecom'
							}
						},
						ecom: {
							currency: 'EUR'
						},
						ajax: {
							data_context_enabled: 1,
							client: 'AFSA:wordpress'
						}

						}
				).dump();

			if (is_visible()) {
				run();
			}

				$( '#afsa_dashboard_widget' ).click(
					function () {
							var visible = is_visible();
						if (visible && ! started) {
							run();
						} else {
							visible ? AFSA.Dashboard().resume() : AFSA.Dashboard().pause();
						}
					}
				);

			function is_visible() {
				return ! widget.hasClass( 'closed' );
			}

			function run( ) {
				started = true;

				window.setTimeout(
					function () {

							AFSA.Dashboard()
									.init(
										{
											id: 'maindb',
											calendar: 1,
											widgets: [

												]
											}
									)
									.run();
					},
					1
				);

			}

			function declareCustomHooks() {

				AFSA.hook = AFSA.hook || {};

				AFSA.hook.prepareAjaxData = function (data) {
					data.action = 'afsa_stats_server';
					if (typeof s_nonce !== 'undefined') {
						data._ajax_nonce = s_nonce;
					}

				};

			}

		}()
	);
}
