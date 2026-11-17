<?php
/**
 * Main plugin bootstrap.
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;

/**
 * Initializes the plugin.
 */
final class Plugin {

	/**
	 * The single plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Consent service instance.
	 *
	 * @var Consent
	 */
	private Consent $consent;

	/**
	 * Prevent direct instantiation.
	 */
	private function __construct() {
		$this->consent = new Consent();
		$public = new Public_Frontend();
        $public->register_hooks();
		$rest = new REST( $this->consent );
        $rest->register_hooks();

		add_action(
			'plugins_loaded',
			array( $this, 'load_textdomain' )
		);

		if ( is_admin() ) {
			$admin = new Admin();
			$admin->register_hooks();

			$settings = new Settings();
			$settings->register_hooks();
		}
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @return void
	 */
	public function __wakeup(): void {
		throw new \LogicException(
			'Unserializing the plugin instance is not allowed.'
		);
	}

	/**
	 * Return the single plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'lc-advanced-cookie-consent',
			false,
			dirname( plugin_basename( LCACC_FILE ) ) . '/languages'
		);
	}
}


