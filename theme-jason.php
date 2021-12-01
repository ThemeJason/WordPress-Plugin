<?php
/**
 * Plugin Name: Theme Jason
 * Text Domain: theme-jason
 * Domain Path: /languages
 * Plugin URI: https://themejason.com
 * Assets URI: https://themejason.com
 * Author: Theme Json
 * Author URI: https://themejason.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: The secret sauce to using all the fun styles on themejason.com.
 * Requires PHP: 7.0
 * Requires At Least: 5.8
 * Version: 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'THEME_JASON_DIRECTORY_ROOT', __DIR__ );
define( 'THEME_JASON_DIRECTORY_URL', plugin_dir_url( __FILE__ ) );

function theme_jason_init() {
	require_once THEME_JASON_DIRECTORY_ROOT . '/classes/admin/Admin.php';
	require_once THEME_JASON_DIRECTORY_ROOT . '/classes/front/Front.php';
	new ThemeJason\Classes\Admin\Admin();
	new ThemeJason\Classes\Front\Front();
}
theme_jason_init();
