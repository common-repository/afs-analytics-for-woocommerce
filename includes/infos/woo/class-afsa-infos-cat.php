<?php

class AFSA_Category_Infos {

	public static function get_name_from_product( $product ) {
		$category_str = null;

		if ( $product->is_type( 'variation' ) ) {
			$id = $product->get_parent_id();
		} else {
			$id = $product->get_id();
		}

		$categories = get_the_terms( $id, 'product_cat' );
		if ( $categories ) {
			$names = array();
			foreach ( $categories as $category ) {
				$names[] = '/' . static::get_product_category_hierarchy( $category->term_id );
			}
			$category_str = implode( ',', $names );
		}

		return $category_str;
	}

	public static function get_product_category_hierarchy( $category_id ) {

		$full_path = get_term_parents_list(
			$category_id,
			'product_cat',
			array(
				'format'    => 'name',
				'separator' => '/',
				'link'      => false,
				'inclusive' => true,
			)
		);

		return is_string( $full_path ) ?
				mb_strtolower( $full_path ) :
				$full_path;
	}

}
