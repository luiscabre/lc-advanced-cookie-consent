<?php
/**
 * Handles plugin deactivation tasks.
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;

/**
 * Deactivator class.
 */
final class Deactivator {

	/**
	 * Run deactivation tasks.
	 *
	 * Deactivation must not delete saved settings or user data.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'lcacc_daily_scan' );
	}
}
