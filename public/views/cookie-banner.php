<?php
/**
 * Cookie consent banner template.
 *
 * @package LCACC
 */

namespace LCACC;

defined( 'ABSPATH' ) || exit;
?>

<!-- Persistent button to reopen cookie preferences. -->
<button
	type="button"
	id="lcacc-settings-trigger"
	class="lcacc-settings-trigger"
	aria-controls="lcacc-banner"
	aria-haspopup="dialog"
	hidden
>
	<span class="dashicons dashicons-shield-alt" aria-hidden="true"></span>
	<span><?php esc_html_e( 'Cookie Settings', 'lc-advanced-cookie-consent' ); ?></span>
</button>

<div
	id="lcacc-banner"
	class="lcacc-banner"
	role="region"
	aria-label="<?php esc_attr_e( 'Cookie preferences', 'lc-advanced-cookie-consent' ); ?>"
	hidden
>
	<div class="lcacc-banner__content">
		<div class="lcacc-banner__icon" aria-hidden="true">
			<span class="dashicons dashicons-shield-alt"></span>
		</div>

		<div class="lcacc-banner__body">
			<h2 class="lcacc-banner__title">
				<?php esc_html_e( 'Your privacy matters', 'lc-advanced-cookie-consent' ); ?>
			</h2>

			<p class="lcacc-banner__description">
				<?php
				esc_html_e(
					'We use cookies to improve your experience, understand how our website is used, and support our services. You can accept all cookies, reject optional cookies, or customize your preferences.',
					'lc-advanced-cookie-consent'
				);
				?>
			</p>

			<div
				id="lcacc-preferences"
				class="lcacc-preferences"
				hidden
			>
				<h3>
					<?php esc_html_e( 'Customize your preferences', 'lc-advanced-cookie-consent' ); ?>
				</h3>

				<div class="lcacc-category">
					<div class="lcacc-category__info">
						<strong>
							<?php esc_html_e( 'Strictly Necessary', 'lc-advanced-cookie-consent' ); ?>
						</strong>
						<p>
							<?php esc_html_e( 'Required for essential website functionality.', 'lc-advanced-cookie-consent' ); ?>
						</p>
					</div>

					<label class="lcacc-switch">
						<input type="checkbox" checked disabled>
						<span class="lcacc-switch__slider"></span>
						<span class="screen-reader-text">
							<?php esc_html_e( 'Strictly Necessary cookies are always enabled', 'lc-advanced-cookie-consent' ); ?>
						</span>
					</label>
				</div>

				<?php
				$category_labels = array(
					'functional' => array(
						'title'       => __( 'Functional', 'lc-advanced-cookie-consent' ),
						'description' => __( 'Enable enhanced features and personalization.', 'lc-advanced-cookie-consent' ),
					),
					'statistics' => array(
						'title'       => __( 'Statistics', 'lc-advanced-cookie-consent' ),
						'description' => __( 'Help us understand how visitors use this website.', 'lc-advanced-cookie-consent' ),
					),
					'marketing' => array(
						'title'       => __( 'Marketing', 'lc-advanced-cookie-consent' ),
						'description' => __( 'Support relevant advertising and marketing measurement.', 'lc-advanced-cookie-consent' ),
					),
				);
				?>

				<?php foreach ( $category_labels as $slug => $category ) : ?>
					<?php if ( ! empty( $categories[ $slug ] ) ) : ?>
						<div class="lcacc-category">
							<div class="lcacc-category__info">
								<strong><?php echo esc_html( $category['title'] ); ?></strong>
								<p><?php echo esc_html( $category['description'] ); ?></p>
							</div>

							<label class="lcacc-switch">
								<input
									type="checkbox"
									class="lcacc-category-toggle"
									value="<?php echo esc_attr( $slug ); ?>"
								>
								<span class="lcacc-switch__slider"></span>
								<span class="screen-reader-text">
									<?php
									printf(
										/* translators: %s: cookie category name. */
										esc_html__( 'Enable %s cookies', 'lc-advanced-cookie-consent' ),
										esc_html( $category['title'] )
									);
									?>
								</span>
							</label>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<p
				id="lcacc-status"
				class="lcacc-status"
				role="status"
				aria-live="polite"
			></p>

			<div class="lcacc-banner__actions">
				<button
					type="button"
					id="lcacc-reject"
					class="lcacc-button lcacc-button--secondary"
				>
					<?php esc_html_e( 'Reject Optional', 'lc-advanced-cookie-consent' ); ?>
				</button>

				<button
					type="button"
					id="lcacc-customize"
					class="lcacc-button lcacc-button--secondary"
					aria-expanded="false"
					aria-controls="lcacc-preferences"
				>
					<?php esc_html_e( 'Customize', 'lc-advanced-cookie-consent' ); ?>
				</button>

				<button
					type="button"
					id="lcacc-accept"
					class="lcacc-button lcacc-button--primary"
				>
					<?php esc_html_e( 'Accept All', 'lc-advanced-cookie-consent' ); ?>
				</button>

				<button
					type="button"
					id="lcacc-save"
					class="lcacc-button lcacc-button--primary"
					hidden
				>
					<?php esc_html_e( 'Save Preferences', 'lc-advanced-cookie-consent' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
