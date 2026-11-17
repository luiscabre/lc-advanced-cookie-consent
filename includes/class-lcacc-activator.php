<?php
/**
 * Handles plugin activation tasks.
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;

/**
 * Activator class.
 */
final class Activator {

	/**
	 * Run activation tasks.
	 *
	 * This method must remain safe to execute again if the plugin
	 * is deactivated and subsequently reactivated.
	 *
	 * @return void
	 */
	public static function activate(): void {
		add_option(
			'lcacc_version',
			LCACC_VERSION,
			'',
			false
		);

		update_option(
			'lcacc_version',
			LCACC_VERSION,
			false
		);
	}
}
