<?php

class AFSA_Post_Title_Field {

	public static function on_edit_form_after_title( $post ) {
		$id = $post->ID;
		if ( $id ) {

			$trackername = stripslashes( get_post_meta( $id, 'afstrackername', true ) );

			return '<div>'
					. '<b>' . __( 'AFS Analytics tracker name', 'afsanalytics' ) . ':&nbsp</b>'
					. '<input type="text" name="afstrackername" id="afstrackername" '
					. 'size=40 '
					. 'placeholder="' . __( 'tracker name (optional)', 'afsanalytics' ) . '" '
					. 'value="' . $trackername . '"/>'
					. '</div>';
		}
		return '';
	}

	public static function on_save_post( $post_id, $post ) {

		$id         = $post_id;
		$page_title = null;

		if ( $id ) {
			if ( isset( $_POST['afstrackername'] ) ) {
				$page_title = sanitize_text_field( $_POST['afstrackername'] );
			} elseif ( isset( $_POST['post_title'] ) ) {
				$page_title = sanitize_text_field( $_POST['post_title'] );
			}

			if ( empty( $page_title ) ) {
				$page_title = $post->post_title;
			}

			if ( empty( $page_title ) && ( is_front_page() || is_home() ) ) {
				$page_title = 'Home';
			}

			if ( ! empty( $page_title ) ) {
				if ( ! add_post_meta( $id, 'afstrackername', $page_title, true ) ) {
					update_post_meta( $id, 'afstrackername', esc_sql( $page_title ) );
				}
			}
		}
	}

}
