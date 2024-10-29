/* global AFSA, s_nonce  */

$ = jQuery;

jQuery( document ).ready(
	function () {

			AFSA.log = console.log;

			AFSA.log( 'AFSA DB Wordpress Container ' + AFSA.version().board );

			AFSA.version();

			declareCustomHooks();

			AFSA.config().set(
				{
					dashboard: {
						parent: '#afsa_container'
					},

					ajax: {
						data_context_enabled: 1,
						client: 'AFSA:wordpress'
					}

					}
			).dump();

			window.setTimeout(
				function () {
						AFSA.Dashboard().container().run();
				},
				1
			);

		function declareCustomHooks() {

			AFSA.hook = AFSA.hook || {};

			AFSA.hook.prepareAjaxData = function (data) {
				data.action = 'afsa_stats_server';
				if (typeof s_nonce !== 'undefined') {
					data._ajax_nonce = s_nonce;
				}
			};

		}

	}
);
