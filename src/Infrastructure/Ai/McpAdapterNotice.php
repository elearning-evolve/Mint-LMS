<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ai;

defined( 'ABSPATH' ) || exit;

/**
 * Soft dependency notice for MCP Adapter (WordPress.org).
 *
 * Mint LMS activates and runs without MCP Adapter. AI Assistance via Claude Desktop
 * needs MCP Adapter — show a non-blocking admin alert on the Plugins screen.
 */
final class McpAdapterNotice {

	public const MCP_PLUGIN_FILE = 'mcp-adapter/mcp-adapter.php';

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'renderPluginsScreenNotice' ) );
		add_action( 'after_plugin_row_' . plugin_basename( MINTLMS_FILE ), array( $this, 'renderPluginRowNotice' ), 10, 0 );
		add_action( 'activated_plugin', array( $this, 'onPluginToggled' ), 10, 1 );
		add_action( 'deactivated_plugin', array( $this, 'onPluginToggled' ), 10, 1 );
	}

	/**
	 * Top-of-Plugins-page alert. Does not suppress or replace other WP notices.
	 */
	public function renderPluginsScreenNotice(): void {
		if ( ! $this->shouldShow() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen || 'plugins' !== $screen->id ) {
			return;
		}

		$this->printAlert();
	}

	/**
	 * Extra row under Mint LMS on the plugins table.
	 */
	public function renderPluginRowNotice(): void {
		if ( ! $this->shouldShow() ) {
			return;
		}

		$colspan = 3;
		if ( is_multisite() && is_network_admin() ) {
			$colspan = 4;
		}

		echo '<tr class="plugin-update-tr mintlms-mcp-adapter-notice active"><td colspan="' . esc_attr( (string) $colspan ) . '" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt" style="margin:8px 0 10px;"><p>';
		echo wp_kses_post( $this->messageHtml() );
		echo '</p></div></td></tr>';
	}

	/**
	 * Keep notice logic fresh when Mint or MCP Adapter is toggled.
	 *
	 * @param string $plugin Plugin file.
	 */
	public function onPluginToggled( string $plugin ): void {
		$watched = array(
			plugin_basename( MINTLMS_FILE ),
			self::MCP_PLUGIN_FILE,
		);

		if ( ! in_array( $plugin, $watched, true ) ) {
			return;
		}

		// No persistent option required — is_plugin_active() is live on next load.
		// Hook exists so activate/deactivate of either plugin always re-evaluates notice.
		delete_transient( 'mintlms_mcp_adapter_notice_cache' );
	}

	public static function isMcpAdapterActive(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( self::MCP_PLUGIN_FILE ) ) {
			return true;
		}

		// Fallbacks if the main file path differs by packaging.
		$candidates = array(
			'mcp-adapter/plugin.php',
			'mcp-adapter/mcp-adapter.php',
		);
		foreach ( $candidates as $file ) {
			if ( is_plugin_active( $file ) ) {
				return true;
			}
		}

		return class_exists( '\WP\MCP\Plugin', false )
			|| class_exists( '\WP\MCP\Core\McpAdapter', false );
	}

	private function shouldShow(): bool {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return false;
		}

		return ! self::isMcpAdapterActive();
	}

	private function printAlert(): void {
		echo '<div class="notice notice-error mintlms-mcp-adapter-alert"><p>';
		echo wp_kses_post( $this->messageHtml() );
		echo '</p></div>';
	}

	private function messageHtml(): string {
		$install_url = self_admin_url( 'plugin-install.php?s=MCP%20Adapter&tab=search&type=term' );
		$plugins_url = self_admin_url( 'plugins.php' );

		return sprintf(
			/* translators: 1: open strong, 2: close strong, 3: install link open, 4: close anchor, 5: plugins link open */
			__( '%1$sMint LMS AI Assistance:%2$s To use AI Assistance with Claude Desktop (MCP), please %3$sinstall or activate the MCP Adapter%4$s plugin. Mint LMS works without it — only the external AI connection needs MCP Adapter. %5$sView plugins%4$s.', 'mint-lms' ),
			'<strong>',
			'</strong>',
			'<a href="' . esc_url( $install_url ) . '">',
			'</a>',
			'<a href="' . esc_url( $plugins_url ) . '">'
		);
	}
}
