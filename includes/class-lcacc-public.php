<?php
/**
 * Handles the public-facing cookie consent banner.
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;

/**
 * Registers public assets and renders the consent banner.
 */
final class Public_Frontend {

	/**
	 * Register public hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action(
			'wp_enqueue_scripts',
			array( $this, 'enqueue_assets' )
		);

		add_action(
			'wp_footer',
			array( $this, 'render_banner' )
		);
	}

	/**
	 * Determine whether consent management is enabled.
	 *
	 * @return bool
	 */
	private function is_enabled(): bool {
		$settings = get_option( 'lcacc_settings', array() );

		return is_array( $settings )
			&& ! empty( $settings['enabled'] );
	}

	/**
	 * Retrieve enabled optional categories.
	 *
	 * @return array<string, bool>
	 */
	private function get_categories(): array {
		$settings = get_option( 'lcacc_settings', array() );

		$categories = isset( $settings['categories'] )
			&& is_array( $settings['categories'] )
			? $settings['categories']
			: array();

		return array(
			'functional' => ! empty( $categories['functional'] ),
			'statistics' => ! empty( $categories['statistics'] ),
			'marketing'  => ! empty( $categories['marketing'] ),
		);
	}

	/**
	 * Enqueue public CSS and JavaScript.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		wp_enqueue_style(
			'lcacc-frontend',
			LCACC_URL . 'assets/css/frontend.css',
			array(),
			LCACC_VERSION
		);

		wp_enqueue_script(
			'lcacc-frontend',
			LCACC_URL . 'assets/js/frontend.js',
			array(),
			LCACC_VERSION,
			true
		);

		wp_localize_script(
			'lcacc-frontend',
			'lcaccConfig',
			array(
				'restUrl' => esc_url_raw(
					rest_url( 'lcacc/v1/consent' )
				),
				'categories' => $this->get_categories(),
				'scriptBlockingEnabled' => true,
				'strings' => array(
					'saving' => __(
						'Saving your preferences...',
						'lc-advanced-cookie-consent'
					),
					'error' => __(
						'Your preferences could not be saved. Please try again.',
						'lc-advanced-cookie-consent'
					),
				),
			)
		);
	}

	/**
	 * Render the banner.
	 *
	 * @return void
	 */
	public function render_banner(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$categories = $this->get_categories();

		$view_path = LCACC_PATH . 'public/views/cookie-banner.php';

		if ( is_readable( $view_path ) ) {
			include $view_path;
		}
	}
}

