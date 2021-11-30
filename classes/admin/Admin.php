<?php

namespace ThemeJason\Classes\Admin;

class Admin {

	function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_theme_jason_import_styles', array( $this, 'import_styles' ) );
		add_action( 'wp_ajax_theme_jason_export_styles', array( $this, 'export_styles' ) );
	}

	public function import_styles() {

		if ( empty( $_POST['content'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_theme_options' ) || empty( check_ajax_referer( 'theme_json_import_styles' ) ) ) {
			return;
		}

		$content = json_decode( wp_kses_post( wp_unslash( $_POST['content'] ) ), true );

		if ( empty( $content['styles'] ) || empty( $content['name'] ) ) {
			return;
		}

		if ( ! is_array( $content['styles'] ) || empty( $content['styles']['version'] ) || empty( $content['styles']['isGlobalStylesUserThemeJSON'] ) ) {
			return;
		}

		$styles = $content['styles'];
		$name   = sanitize_key( $content['name'] );

		$saved_styles = get_posts(
			array(
				'numberposts' => 1,
				'post_type'   => 'wp_global_styles',
				'post_name'   => $name,
				'post_status' => 'publish',
			)
		);

		if ( ! empty( $saved_styles ) ) {

			$styles['isGlobalStylesUserThemeJSON'] = true;

			$styles = wp_update_post(
				array(
					'ID'           => $saved_styles[0]->ID,
					'post_content' => wp_json_encode( $styles ),
					'post_author'  => get_current_user_id(),
					'post_type'    => 'wp_global_styles',
					'post_name'    => $name,

				)
			);
			wp_send_json( array( 'success' => ! empty( $styles ) ), 200 );
		} else {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Unable to find the global styles.', 'theme-jason' ),
				),
				200
			);
		}
		wp_die();
	}

	public function export_styles() {

		if ( ! current_user_can( 'edit_theme_options' ) || empty( check_ajax_referer( 'theme_json_export_styles' ) ) ) {
			return;
		}

		$styles = get_posts(
			array(
				'numberposts' => 1,
				'post_type'   => 'wp_global_styles',
				'post_status' => 'publish',
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
						'styles' => $content,
					),
				),
				200
			);
		}
		wp_die();
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'theme-jason-admin-css', THEME_JASON_DIRECTORY_URL . 'assets/admin/css/main.css', array(), time(), 'all' );
		wp_enqueue_script(
			'theme-jason-admin-js',
			THEME_JASON_DIRECTORY_URL . 'assets/admin/js/main.js',
			array( 'wp-blocks', 'wp-element', 'wp-hooks', 'wp-components', 'wp-i18n', 'wp-edit-post', 'wp-compose' ),
			time(),
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
				'success'       => __( 'Success.', 'theme-jason' ),
				'error'         => __( 'An error occurred.', 'theme-jason' ),
			),
		);
		wp_localize_script( 'theme-jason-admin-js', 'scriptParams', $script_params );
	}

}
