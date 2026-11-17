<?php
/**
 * Plugin Name: LC Advanced Cookie Consent
 * Plugin URI:  https://www.luiscabre.com/my-plugins 
 * Description: A configurable cookie consent and privacy preference manager for WordPress.
 * Version:     1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Author:      Luis Cabré
 * Author URI:  https://www.luiscabre.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lc-advanced-cookie-consent
 * Domain Path: /languages
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin version.
 */
define( 'LCACC_VERSION', '1.0.0' );

/**
 * Absolute path to the plugin's main file.
 */
define( 'LCACC_FILE', __FILE__ );

/**
 * Absolute path to the plugin directory.
 */
define( 'LCACC_PATH', plugin_dir_path( LCACC_FILE ) );

/**
 * URL to the plugin directory.
 */
define( 'LCACC_URL', plugin_dir_url( LCACC_FILE ) );

require_once LCACC_PATH . 'includes/class-lcacc-activator.php';
require_once LCACC_PATH . 'includes/class-lcacc-deactivator.php';
require_once LCACC_PATH . 'includes/class-lcacc-plugin.php';
require_once LCACC_PATH . 'includes/class-lcacc-admin.php';
require_once LCACC_PATH . 'includes/class-lcacc-settings.php';
require_once LCACC_PATH . 'includes/class-lcacc-consent.php';
require_once LCACC_PATH . 'includes/class-lcacc-rest.php';
require_once LCACC_PATH . 'includes/class-lcacc-public.php';

register_activation_hook(
	LCACC_FILE,
	array( Activator::class, 'activate' )
);

register_deactivation_hook(
	LCACC_FILE,
	array( Deactivator::class, 'deactivate' )
);

/**
 * Initialize the plugin.
 */
Plugin::get_instance();