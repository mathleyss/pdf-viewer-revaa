<?php
/**
 * Plugin Name: REVAA PDF Viewer
 * Description: Visionneuse PDF protégée avec bloc Gutenberg
 * Version: 1.1.0
 * Author: Mathieu Leyssene
 * Author URI: https://mathieu-leyssene.fr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REVAA_PDF_VIEWER_PATH', plugin_dir_path( __FILE__ ) );
define( 'REVAA_PDF_VIEWER_URL', plugin_dir_url( __FILE__ ) );

require_once REVAA_PDF_VIEWER_PATH . 'includes/class-pdf-storage.php';
require_once REVAA_PDF_VIEWER_PATH . 'includes/class-pdf-endpoint.php';
require_once REVAA_PDF_VIEWER_PATH . 'includes/class-pdf-block.php';

add_action( 'plugins_loaded', function () {
	REVAA_PDF_Storage::init();
	REVAA_PDF_Endpoint::init();
	REVAA_PDF_Block::init();
} );

register_activation_hook( __FILE__, function () {
	REVAA_PDF_Endpoint::register_rewrite_rule();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );
