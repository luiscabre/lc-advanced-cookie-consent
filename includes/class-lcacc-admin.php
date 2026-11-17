<?php
/**
 * Registers the plugin's administration interface.
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the administration interface.
 */
final class Admin {

	/**
	 * Register administration hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action(
			'admin_menu',
			array( $this, 'register_menu' )
		);

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_assets' )
		);
	}

	/**
	 * Register the top-level administration menu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'LC Advanced Cookie Consent', 'lc-advanced-cookie-consent' ),
			__( 'LC Cookie Consent', 'lc-advanced-cookie-consent' ),
			'manage_options',
			'lc-advanced-cookie-consent',
			array( $this, 'render_page' ),
			'dashicons-shield-alt',
			58
		);
	}

	/**
	 * Load assets only on the plugin settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_lc-advanced-cookie-consent' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'lcacc-admin',
			LCACC_URL . 'assets/css/admin.css',
			array(),
			LCACC_VERSION
		);
	}

	/**
	 * Render the plugin settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'You are not allowed to access this page.',
					'lc-advanced-cookie-consent'
				)
			);
		}

		$settings = get_option( 'lcacc_settings', array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$enabled = ! empty( $settings['enabled'] );

		$categories = isset( $settings['categories'] )
			&& is_array( $settings['categories'] )
			? $settings['categories']
			: array();

		$category_data = array(
			'necessary'  => array(
				'title'       => __( 'Strictly Necessary', 'lc-advanced-cookie-consent' ),
				'description' => __( 'Required for essential website functions and security. These cookies cannot be disabled through this interface.', 'lc-advanced-cookie-consent' ),
			),
			'functional' => array(
				'title'       => __( 'Functional', 'lc-advanced-cookie-consent' ),
				'description' => __( 'Allow enhanced functionality, such as remembering user preferences and settings.', 'lc-advanced-cookie-consent' ),
			),
			'statistics' => array(
				'title'       => __( 'Statistics', 'lc-advanced-cookie-consent' ),
				'description' => __( 'Help measure website usage and understand how visitors interact with your content.', 'lc-advanced-cookie-consent' ),
			),
			'marketing'  => array(
				'title'       => __( 'Marketing', 'lc-advanced-cookie-consent' ),
				'description' => __( 'Support advertising and the measurement of marketing campaigns.', 'lc-advanced-cookie-consent' ),
			),
		);
		?>
		<div class="wrap lcacc-wrap">
			<div class="lcacc-header">
				<div class="lcacc-header-icon" aria-hidden="true">
					<span class="dashicons dashicons-shield-alt"></span>
				</div>

				<div class="lcacc-header-copy">
					<h1>
						<?php
						echo esc_html__(
							'LC Advanced Cookie Consent',
							'lc-advanced-cookie-consent'
						);
						?>
					</h1>

					<p>
						<?php
						echo esc_html__(
							'Manage your website privacy and cookie consent settings.',
							'lc-advanced-cookie-consent'
						);
						?>
					</p>
				</div>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'lcacc_settings_group' ); ?>

				<section class="lcacc-card">
					<div class="lcacc-card-heading">
						<div>
							<h2>
								<?php
								echo esc_html__(
									'General Settings',
									'lc-advanced-cookie-consent'
								);
								?>
							</h2>

							<p>
								<?php
								echo esc_html__(
									'Control the main consent feature for your website.',
									'lc-advanced-cookie-consent'
								);
								?>
							</p>
						</div>
					</div>

					<div class="lcacc-setting-row">
						<div class="lcacc-setting-copy">
							<label for="lcacc-enabled">
								<?php
								echo esc_html__(
									'Enable cookie consent',
									'lc-advanced-cookie-consent'
								);
								?>
							</label>

							<p>
								<?php
								echo esc_html__(
									'Enable the consent feature when its frontend functionality is available.',
									'lc-advanced-cookie-consent'
								);
								?>
							</p>
						</div>

						<label class="lcacc-toggle" for="lcacc-enabled">
							<input
								type="checkbox"
								id="lcacc-enabled"
								name="lcacc_settings[enabled]"
								value="1"
								<?php checked( $enabled ); ?>
							/>
							<span class="lcacc-toggle-track" aria-hidden="true"></span>
							<span class="screen-reader-text">
								<?php
								echo esc_html__(
									'Enable cookie consent',
									'lc-advanced-cookie-consent'
								);
								?>
							</span>
						</label>
					</div>
				</section>

				<section class="lcacc-card">
					<div class="lcacc-card-heading">
						<div>
							<h2>
								<?php
								echo esc_html__(
									'Cookie Categories',
									'lc-advanced-cookie-consent'
								);
								?>
							</h2>

							<p>
								<?php
								echo esc_html__(
									'Configure which categories can be offered to visitors. These settings do not yet block or control actual cookies.',
									'lc-advanced-cookie-consent'
								);
								?>
							</p>
						</div>
					</div>

					<div class="lcacc-category-list">
						<?php foreach ( $category_data as $key => $category ) : ?>
							<?php
							$is_necessary = 'necessary' === $key;
							$is_checked   = $is_necessary
								|| ! empty( $categories[ $key ] );
							?>
							<div class="lcacc-category">
								<div class="lcacc-category-copy">
									<h3>
										<?php echo esc_html( $category['title'] ); ?>

										<?php if ( $is_necessary ) : ?>
											<span class="lcacc-required-label">
												<?php
												echo esc_html__(
													'Always active',
													'lc-advanced-cookie-consent'
												);
												?>
											</span>
										<?php endif; ?>
									</h3>

									<p>
										<?php echo esc_html( $category['description'] ); ?>
									</p>
								</div>

								<label
									class="lcacc-toggle"
									for="lcacc-category-<?php echo esc_attr( $key ); ?>"
								>
									<input
										type="checkbox"
										id="lcacc-category-<?php echo esc_attr( $key ); ?>"
										name="lcacc_settings[categories][<?php echo esc_attr( $key ); ?>]"
										value="1"
										<?php checked( $is_checked ); ?>
										<?php disabled( $is_necessary ); ?>
									/>

									<span class="lcacc-toggle-track" aria-hidden="true"></span>

									<span class="screen-reader-text">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: Cookie category name. */
												__( 'Enable %s cookies', 'lc-advanced-cookie-consent' ),
												$category['title']
											)
										);
										?>
									</span>
								</label>
							</div>
						<?php endforeach; ?>
					</div>
				</section>

				<div class="lcacc-form-footer">
					<?php
					submit_button(
						__( 'Save Settings', 'lc-advanced-cookie-consent' ),
						'primary',
						'submit',
						false
					);
					?>
				</div>
			</form>

			<p class="lcacc-footer-note">
				<span class="dashicons dashicons-shield-alt" aria-hidden="true"></span>
				<?php
				echo esc_html__(
					'Your settings are managed securely through WordPress.',
					'lc-advanced-cookie-consent'
				);
				?>
			</p>
		</div>
		<?php
	}
}

