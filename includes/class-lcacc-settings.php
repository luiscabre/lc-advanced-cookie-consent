<?php
/**
 * Registers and manages plugin settings.
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin settings registration.
 */
final class Settings {

	/**
	 * WordPress option name.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'lcacc_settings';

	/**
	 * Allowed optional cookie categories.
	 *
	 * @var string[]
	 */
	private const OPTIONAL_CATEGORIES = array(
		'functional',
		'statistics',
		'marketing',
	);

	/**
	 * Register settings hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action(
			'admin_init',
			array( $this, 'register_settings' )
		);
	}

	/**
	 * Register the plugin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'lcacc_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'enabled'    => false,
					'categories' => array(
						'necessary'  => true,
						'functional' => false,
						'statistics' => false,
						'marketing'  => false,
					),
				),
			)
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param mixed $input Submitted settings.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return array(
				'enabled'    => false,
				'categories' => array(
					'necessary'  => true,
					'functional' => false,
					'statistics' => false,
					'marketing'  => false,
				),
			);
		}

		$submitted_categories = isset( $input['categories'] )
			&& is_array( $input['categories'] )
			? $input['categories']
			: array();

		$categories = array(
			'necessary' => true,
		);

		foreach ( self::OPTIONAL_CATEGORIES as $category ) {
			$categories[ $category ] = isset( $submitted_categories[ $category ] )
				&& '1' === (string) $submitted_categories[ $category ];
		}

		return array(
			'enabled'    => isset( $input['enabled'] )
				&& '1' === (string) $input['enabled'],
			'categories' => $categories,
		);
	}
}

