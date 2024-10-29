<?php


class AFSA_Renderer {

	public static function hook_footer_line() {
		/*
		  add_filter('admin_footer_text', function() {
		  print '';
		  }, 11);
		 */
		add_filter(
			'update_footer',
			function() {
					print 'AFS Analytics WordPress plugin ' . AFSA_MODULE_VERSION;
			},
			11
		);
	}


}
