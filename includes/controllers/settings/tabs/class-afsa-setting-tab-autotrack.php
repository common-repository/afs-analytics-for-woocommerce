<?php

class AFSA_Setting_Tab_Autotrack extends AFSA_Setting_Tab {

	public function init() {

		$this->add_section(
			'afsa_autotrack_section',
			__( 'Monitored Events', 'afsanalytics' ),
			function() {
					// return optional description;
			}
		);

		$this->add_select_inputs(
			array(
				'autotrack_outbound' => __( 'Outbound clicks tracking', 'afsanalytics' ),
				'autotrack_inside'   => __( 'Inside clicks tracking', 'afsanalytics' ),
				'autotrack_download' => __( 'Download tracking', 'afsanalytics' ),
				'autotrack_video'    => __( 'Video tracking', 'afsanalytics' ),
				'autotrack_iframe'   => __( 'Iframe tracking', 'afsanalytics' ),
			)
		);
	}

}
