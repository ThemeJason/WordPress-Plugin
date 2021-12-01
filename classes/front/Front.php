<?php

namespace ThemeJason\Classes\Front;

class Front {

	function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		$data = \WP_Theme_JSON_Resolver_Gutenberg::get_merged_data();
		$data = $data->get_raw_data();
		if (
			! empty( $data['settings'] ) &&
			! empty( $data['settings']['typography'] ) &&
			! empty( $data['settings']['typography']['fontFamilies'] ) &&
			! empty( $data['settings']['typography']['fontFamilies']['user'] ) &&
			is_array( $data['settings']['typography']['fontFamilies']['user'] )
		) {
			$fonts = implode( '&', array_column( $data['settings']['typography']['fontFamilies']['user'], 'google' ) );

			// It's needed to use null as the version to prevent PHP from removing multiple the multiple font parameter.
			wp_enqueue_style( 'theme-jason-fonts', sprintf( 'https://fonts.googleapis.com/css?%s', esc_attr( $fonts ) ), false, null ); // phpcs:ignore WordPress.WP
		}

	}

}
