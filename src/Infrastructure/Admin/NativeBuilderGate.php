<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Shared Mint LMS sidebar / submitbox gate for native WP editors.
 *
 * Pending copy shows until Draft/Publish; the builder button is revealed in JS
 * without a full page reload (see native-editor-save-guard.js).
 */
final class NativeBuilderGate {

	/**
	 * @param array{pending:string,label:string,url:string} $config Gate copy + link.
	 */
	public static function render( \WP_Post $post, array $config ): void {
		$ready   = PostTypes::hasExplicitSave( $post );
		$pending = (string) ( $config['pending'] ?? '' );
		$label   = (string) ( $config['label'] ?? '' );
		$url     = (string) ( $config['url'] ?? '#' );

		echo '<div class="mintlms-builder-gate" data-mintlms-builder-gate>';
		echo '<p class="mintlms-builder-gate__pending"' . ( $ready ? ' hidden' : '' ) . '>';
		echo esc_html( $pending );
		echo '</p>';
		echo '<a class="button button-primary mintlms-builder-gate__button"';
		echo ' style="width:100%;text-align:center;box-sizing:border-box;"';
		echo ' href="' . esc_url( $url ) . '"';
		echo ' data-url="' . esc_url( $url ) . '"';
		echo $ready ? '' : ' hidden';
		echo '>';
		echo esc_html( $label );
		echo '</a>';
		echo '</div>';
	}
}
