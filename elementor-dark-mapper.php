<?php
/**
 * Plugin Name: Elementor Global Dark Mode Mapper (EDM)
 * Description: Map Elementor Global Colors to Night counterparts (Elementor Free compatible). Lightweight MVP.
 * Version: 0.2
 * Author: AmirHossein Moradian
 * Text Domain: elementor-dark-mapper
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helpful constants for paths/URLs used across classes
 */
if ( ! defined( 'EDM_PLUGIN_FILE' ) ) {
    define( 'EDM_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'EDM_PLUGIN_DIR' ) ) {
    define( 'EDM_PLUGIN_DIR', plugin_dir_path( EDM_PLUGIN_FILE ) );
}
if ( ! defined( 'EDM_PLUGIN_URL' ) ) {
    define( 'EDM_PLUGIN_URL', plugin_dir_url( EDM_PLUGIN_FILE ) );
}

/**
 * Includes (core)
 */
require_once EDM_PLUGIN_DIR . 'includes/class-plugin.php';
require_once EDM_PLUGIN_DIR . 'includes/class-color-manager.php';
require_once EDM_PLUGIN_DIR . 'includes/class-settings.php';
require_once EDM_PLUGIN_DIR . 'includes/class-css-generator.php';
require_once EDM_PLUGIN_DIR . 'includes/class-switcher.php';

/**
 * Boot
 */
add_action( 'plugins_loaded', array( 'EDM_Plugin', 'instance' ) );
