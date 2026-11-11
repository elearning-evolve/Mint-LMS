<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	public function register(): void {
		add_action(
			'admin_init',
			static function (): void {
				register_setting(
					'mintlms_wc_settings',
					'mintlms_wc_cancel_on_refund',
					array(
						'type'              => 'string',
						'sanitize_callback' => static function ( $value ): string {
							return 'yes' === $value ? 'yes' : 'no';
						},
						'default'           => 'yes',
					)
				);

				register_setting(
					'mintlms_wc_settings',
					'mintlms_wc_enroll_statuses',
					array(
						'type'              => 'string',
						'sanitize_callback' => static function ( $value ): string {
							$raw   = is_string( $value ) ? $value : 'processing,completed';
							$parts = array_filter( array_map( 'sanitize_key', explode( ',', $raw ) ) );
							return array() === $parts ? 'processing,completed' : implode( ',', $parts );
						},
						'default'           => 'processing,completed',
					)
				);
			}
		);

		add_action(
			'admin_menu',
			array( $this, 'registerMenu' )
		);
	}

	public function registerMenu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Mint LMS', 'mint-lms' ),
			__( 'Mint LMS', 'mint-lms' ),
			'manage_woocommerce',
			'mintlms-woocommerce',
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$cancelOnRefund = get_option( 'mintlms_wc_cancel_on_refund', 'yes' );
		$statuses       = (string) get_option( 'mintlms_wc_enroll_statuses', 'processing,completed' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mint LMS WooCommerce', 'mint-lms' ); ?></h1>
			<p><?php esc_html_e( 'Sell Mint LMS courses as WooCommerce products (LearnDash-style). Create a “Mint LMS Course” product or use Sell with WooCommerce on a course.', 'mint-lms' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'mintlms_wc_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enroll on order status', 'mint-lms' ); ?></th>
						<td>
							<input
								type="text"
								class="regular-text"
								name="mintlms_wc_enroll_statuses"
								value="<?php echo esc_attr( $statuses ); ?>"
							/>
							<p class="description"><?php esc_html_e( 'Comma-separated WooCommerce status slugs (without wc-). Default: processing,completed', 'mint-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cancel enrollment on refund', 'mint-lms' ); ?></th>
						<td>
							<input type="hidden" name="mintlms_wc_cancel_on_refund" value="no" />
							<label for="mintlms_wc_cancel_on_refund">
								<input
									type="checkbox"
									id="mintlms_wc_cancel_on_refund"
									name="mintlms_wc_cancel_on_refund"
									value="yes"
									<?php checked( 'yes', $cancelOnRefund ); ?>
								/>
								<?php esc_html_e( 'Revoke Mint LMS course access when an order is refunded or cancelled.', 'mint-lms' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
