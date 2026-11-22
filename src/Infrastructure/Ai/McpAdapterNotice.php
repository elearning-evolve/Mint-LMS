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
		add_action( 'admin_notices', array( $this, 'renderPluginsScreenNotice' ), 5 );
		add_action( 'network_admin_notices', array( $this, 'renderPluginsScreenNotice' ), 5 );
		// Generic row hook — reliable even if Mint folder name differs per environment.
		add_action( 'after_plugin_row', array( $this, 'renderAfterPluginRow' ), 10, 2 );
		add_action( 'activated_plugin', array( $this, 'onPluginToggled' ), 10, 1 );
		add_action( 'deactivated_plugin', array( $this, 'onPluginToggled' ), 10, 1 );
	}

	/**
	 * Top-of-Plugins-page alert. Does not suppress or replace other WP notices.
	 */
	public function renderPluginsScreenNotice(): void {
		if ( ! $this->isPluginsAdminScreen() || ! $this->shouldShow() ) {
			return;
		}

		$this->printAlert();
	}

	/**
	 * Extra row under Mint LMS on the plugins table.
	 *
	 * @param string               $plugin_file Plugin basename.
	 * @param array<string, mixed> $plugin_data Plugin headers.
	 */
	public function renderAfterPluginRow( string $plugin_file, array $plugin_data = array() ): void {
		unset( $plugin_data );

		if ( plugin_basename( MINTLMS_FILE ) !== $plugin_file ) {
			return;
		}

		if ( ! $this->shouldShow() ) {
			return;
		}

		// Match core plugin-update row: checkbox + Plugin + Description + Automatic Updates.
		echo '<tr class="plugin-update-tr mintlms-mcp-adapter-notice active" data-plugin="' . esc_attr( $plugin_file ) . '"><td colspan="4" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt" style="margin:8px 0 10px;"><p>';
		echo wp_kses_post( $this->messageHtml() );
		echo '</p></div></td></tr>';
	}

	/**
	 * Keep notice logic fresh when Mint or MCP Adapter is toggled.
	 *
	 * @param string $plugin Plugin file.
	 */
	public function onPluginToggled( string $plugin ): void {
		$watched = array_merge(
			array( plugin_basename( MINTLMS_FILE ) ),
			self::mcpCandidateFiles()
		);

		if ( ! in_array( $plugin, $watched, true ) ) {
			return;
		}

		delete_transient( 'mintlms_mcp_adapter_notice_cache' );
	}

	public static function isMcpAdapterActive(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( self::mcpCandidateFiles() as $file ) {
			if ( is_plugin_active( $file ) ) {
				return true;
			}
			if ( is_multisite() && function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( $file ) ) {
				return true;
			}
		}

		// Installed but path unknown: match by plugin Name header only (no class_exists — avoids false positives).
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( get_plugins() as $file => $data ) {
			$name = isset( $data['Name'] ) ? (string) $data['Name'] : '';
			if ( 0 !== strcasecmp( $name, 'MCP Adapter' ) ) {
				continue;
			}
			if ( is_plugin_active( $file ) ) {
				return true;
			}
			if ( is_multisite() && function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( $file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return list<string>
	 */
	private static function mcpCandidateFiles(): array {
		return array(
			self::MCP_PLUGIN_FILE,
			'mcp-adapter/plugin.php',
			'mcp-adapter/mcp-adapter.php',
		);
	}

	private function shouldShow(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		if ( ! current_user_can( 'activate_plugins' ) && ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return ! self::isMcpAdapterActive();
	}

	private function isPluginsAdminScreen(): bool {
		// Prefer pagenow — more reliable than get_current_screen() on some hosts/staging.
		$pagenow = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
		if ( 'plugins.php' === $pagenow ) {
			return true;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen ) {
			return false;
		}

		return in_array( (string) $screen->id, array( 'plugins', 'plugins-network' ), true );
	}

	private function printAlert(): void {
		echo '<div class="notice notice-error mintlms-mcp-adapter-alert"><p>';
		echo wp_kses_post( $this->messageHtml() );
		echo '</p></div>';
	}

	private function messageHtml(): string {
		$install_url = self_admin_url( 'plugin-install.php?s=MCP%20Adapter&tab=search&type=term' );

		return sprintf(
			/* translators: 1: open strong, 2: close strong, 3: install link open, 4: close anchor */
			__( '%1$sMint LMS AI Assistance:%2$s To use AI Assistance with Claude Desktop (MCP), please %3$sinstall or activate the MCP Adapter%4$s plugin. Mint LMS works without it — only the external AI connection needs MCP Adapter.', 'mint-lms' ),
			'<strong>',
			'</strong>',
			'<a href="' . esc_url( $install_url ) . '">',
			'</a>'
		);
	}
}
