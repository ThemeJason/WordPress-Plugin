<?php

namespace ThemeJason\Classes\Admin;

class Admin {

	function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_theme_jason_import_styles', array( $this, 'import_styles' ) );
		add_action( 'wp_ajax_theme_jason_export_styles', array( $this, 'export_styles' ) );
	}

	/**
	 * Validates the user permissions and runs the AJAX 'theme_jason_import_styles' to import the Global Styles.
	 *
	 * Only users with 'edit_theme_options' permission can import Global Styles.
	 *
	 * @return void
	 */
	public function import_styles() {

		if ( empty( $_POST['content'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_theme_options' ) || empty( check_ajax_referer( 'theme_json_import_styles' ) ) ) {
			return;
		}

		$content = json_decode( wp_kses_post( wp_unslash( $_POST['content'] ) ), true );

		if ( ! empty( $content['config'] ) ) {
			$content = $content['config'];
		}

		if ( class_exists( '\WP_Theme_JSON_Gutenberg' ) ) {
			$schema = \WP_Theme_JSON_Gutenberg::LATEST_SCHEMA;
		} else {
			$schema = \WP_Theme_JSON::LATEST_SCHEMA;
		}

		// Gets the provided version or the latest schema if null.
		$version = empty( $content['version'] ) && is_numeric( $content['version'] ) ? intval( $content['version'] ) : $schema;

		$to_save = array(
			'isGlobalStylesUserThemeJSON' => true,
			'version'                     => $version,
		);

		$allowed_items = array( 'settings', 'styles' );

		foreach ( $allowed_items as $key => $value ) {
			if ( ! empty( $content[ $value ] ) ) {
				$to_save[ $value ] = $content[ $value ];
			}
		}

		$name = 'wp-global-styles-' . urlencode( wp_get_theme()->get_stylesheet() ); // Imports to the current theme.

		$saved_styles = get_posts(
			array(
				'numberposts' => 1,
				'post_type'   => 'wp_global_styles',
				'post_name'   => $name,
				'post_status' => 'publish',
			)
		);

		if ( ! empty( $saved_styles ) ) {
			$result = wp_update_post(
				array(
					'ID'           => $saved_styles[0]->ID,
					'post_content' => wp_json_encode( $to_save ),
					'post_author'  => get_current_user_id(),
					'post_type'    => 'wp_global_styles',
					'post_name'    => $name,

				)
			);
		} else {
			$result = wp_insert_post(
				array(
					'post_content' => wp_json_encode( $to_save ),
					'post_status'  => 'publish',
					'post_title'   => __( 'Custom Styles', 'default' ),
					'post_type'    => 'wp_global_styles',
					'post_name'    => $name,
					'tax_input'    => array(
						'wp_theme' => array( wp_get_theme()->get_stylesheet() ),
					),
				),
				true
			);
		}

		wp_cache_flush();

		wp_send_json( array( 'success' => ! empty( $result ) ), ! empty( $result ) ? 200 : 400 );

		wp_die();
	}

	/**
	 * Validates the user permissions and runs the AJAX 'theme_jason_export_styles' to export the Global Styles.
	 *
	 * Only users with 'edit_theme_options' permission can export Global Styles.
	 *
	 * @return void
	 */
	public function export_styles() {

		if ( ! current_user_can( 'edit_theme_options' ) || empty( check_ajax_referer( 'theme_json_export_styles' ) ) ) {
			return;
		}

		$name = 'wp-global-styles-' . urlencode( wp_get_theme()->get_stylesheet() );

		$styles = get_posts(
			array(
				'numberposts' => 1,
				'post_status' => 'publish',
				'post_type'   => 'wp_global_styles',
				'post_name'   => $name,
			)
		);

		if ( ! empty( $styles ) ) {
			$content = json_decode( $styles[0]->post_content );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$content = false;
			}
			wp_send_json(
				array(
					'content' => array(
						'name'   => sanitize_key( $styles[0]->post_name ),
						'config' => $content,
					),
				),
				200
			);
		}
		wp_die();
	}

	public function enqueue_scripts() {

		wp_enqueue_style( 'theme-jason-admin-css', THEME_JASON_DIRECTORY_URL . 'assets/admin/css/main.css', array(), THEME_JASON_PLUGIN_VERSION, 'all' );

		wp_enqueue_script(
			'theme-jason-admin-js',
			THEME_JASON_DIRECTORY_URL . 'assets/admin/js/main.js',
			array( 'wp-blocks', 'wp-element', 'wp-hooks', 'wp-components', 'wp-i18n', 'wp-edit-post', 'wp-compose' ),
			THEME_JASON_PLUGIN_VERSION,
			true
		);

		$script_params = array(
			'file_name'    => sprintf( '%s-%s-global-styles.json', current_time( 'Y-m-d-h-i-s' ), sanitize_key( get_bloginfo( 'name' ) ) ),
			'ajax'         => array(
				'url'          => admin_url( 'admin-ajax.php' ),
				'import_nonce' => wp_create_nonce( 'theme_json_import_styles' ),
				'export_nonce' => wp_create_nonce( 'theme_json_export_styles' ),
			),
			'localization' => array(
				'import_styles' => __( 'Import Styles', 'theme-jason' ),
				'export_styles' => __( 'Export Styles', 'theme-jason' ),
				'refresh'       => __( 'Refresh', 'theme-jason' ),
				'success'       => __( 'Success.', 'theme-jason' ),
				'error'         => __( 'An error occurred.', 'theme-jason' ),
			),
		);

		wp_localize_script( 'theme-jason-admin-js', 'scriptParams', $script_params );
	}

}
