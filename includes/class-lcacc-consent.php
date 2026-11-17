<?php
/**
 * Manages cookie consent preferences.
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;

/**
 * Handles consent data validation and normalization.
 */
final class Consent {

	/**
	 * Supported optional cookie categories.
	 *
	 * @var string[]
	 */
	private const OPTIONAL_CATEGORIES = array(
		'functional',
		'statistics',
		'marketing',
	);

	/**
	 * Get the optional categories enabled by the administrator.
	 *
	 * Categories not explicitly enabled remain disabled.
	 *
	 * @return array<string, bool>
	 */
	private function get_enabled_categories(): array {
		$settings = get_option( 'lcacc_settings', array() );

		$categories = array(
			'functional' => false,
			'statistics' => false,
			'marketing'  => false,
		);

		if (
			! is_array( $settings )
			|| ! isset( $settings['categories'] )
			|| ! is_array( $settings['categories'] )
		) {
			return $categories;
		}

		foreach ( self::OPTIONAL_CATEGORIES as $category ) {
			$categories[ $category ] =
				isset( $settings['categories'][ $category ] )
				&& (
					true === $settings['categories'][ $category ]
					|| 1 === $settings['categories'][ $category ]
					|| '1' === $settings['categories'][ $category ]
				);
		}

		return $categories;
	}

	/**
	 * Normalize consent preferences.
	 *
	 * Necessary cookies are always enabled. Optional categories
	 * can only be enabled when permitted by the administrator.
	 *
	 * @param mixed $preferences Submitted preferences.
	 * @return array<string, bool>
	 */
	public function normalize_preferences( $preferences ): array {
		$enabled_categories = $this->get_enabled_categories();

		$normalized = array(
			'necessary'  => true,
			'functional' => false,
			'statistics' => false,
			'marketing'  => false,
		);

		if ( ! is_array( $preferences ) ) {
			return $normalized;
		}

		foreach ( self::OPTIONAL_CATEGORIES as $category ) {
			$user_consented =
				isset( $preferences[ $category ] )
				&& (
					true === $preferences[ $category ]
					|| 1 === $preferences[ $category ]
					|| '1' === $preferences[ $category ]
				);

			$normalized[ $category ] =
				$enabled_categories[ $category ] && $user_consented;
		}

		return $normalized;
	}

	/**
	 * Build a normalized consent record.
	 *
	 * @param mixed  $preferences Submitted preferences.
	 * @param string $decision    Consent decision.
	 * @return array<string, mixed>
	 */
	public function create_record(
		$preferences,
		string $decision
	): array {
		$allowed_decisions = array(
			'accepted',
			'rejected',
			'customized',
		);

		if ( ! in_array( $decision, $allowed_decisions, true ) ) {
			$decision = 'customized';
		}

		$normalized = $this->normalize_preferences( $preferences );

		if ( 'accepted' === $decision ) {
			$enabled_categories = $this->get_enabled_categories();

			foreach ( self::OPTIONAL_CATEGORIES as $category ) {
				$normalized[ $category ] =
					$enabled_categories[ $category ];
			}
		}

		if ( 'rejected' === $decision ) {
			foreach ( self::OPTIONAL_CATEGORIES as $category ) {
				$normalized[ $category ] = false;
			}
		}

		return array(
			'version'      => '1.0',
			'decision'     => $decision,
			'preferences'  => $normalized,
			'consented_at' => gmdate( 'c' ),
		);
	}
}

