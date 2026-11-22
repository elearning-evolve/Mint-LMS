<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Submenu icons matching Mint LMS glyphs, coloured via currentColor
 * so they follow wp-admin menu link colours (inactive / current / hover).
 */
final class MenuIcons {

	/**
	 * Icon + escaped label for add_submenu_page() menu_title.
	 */
	public static function labeled( string $key, string $label ): string {
		return self::markup( $key ) . '<span class="mint-lms-menu-label">' . esc_html( $label ) . '</span>';
	}

	public static function markup( string $key ): string {
		$inner = self::svg( $key );
		if ( '' === $inner ) {
			return '';
		}

		return '<span class="mint-lms-menu-icon mint-lms-menu-icon--' . esc_attr( $key ) . '" aria-hidden="true">' . $inner . '</span>';
	}

	private static function svg( string $key ): string {
		$attrs = 'width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"';

		return match ( $key ) {
			'dashboard' => '<svg ' . $attrs . '><rect x="3" y="3" width="6" height="6" rx="1.2"/><rect x="11" y="3" width="6" height="6" rx="1.2"/><rect x="3" y="11" width="6" height="6" rx="1.2"/><rect x="11" y="11" width="6" height="6" rx="1.2"/></svg>',
			'courses'   => '<svg ' . $attrs . '><path d="M4 5.5c0-.8.7-1.5 1.5-1.5H10v12H5.5A1.5 1.5 0 0 1 4 14.5v-9Z"/><path d="M16 5.5c0-.8-.7-1.5-1.5-1.5H10v12h4.5a1.5 1.5 0 0 0 1.5-1.5v-9Z"/></svg>',
			'lessons'   => '<svg ' . $attrs . '><rect x="3" y="4" width="14" height="12" rx="2.5"/></svg>',
			'quizzes'   => '<svg ' . $attrs . ' stroke-width="2.4"><path d="M4.5 10.5 8 14 15.5 5.5"/></svg>',
			'questions' => '<svg ' . $attrs . '><circle cx="10" cy="10" r="7.25"/><path d="M7.75 7.9a2.4 2.4 0 0 1 4.5.85c0 1.35-1.35 1.85-2.25 2.45"/><circle cx="10" cy="14.35" r="0.9" fill="currentColor" stroke="none"/></svg>',
			'students'  => '<svg ' . $attrs . '><circle cx="7.5" cy="7" r="2.5"/><circle cx="13.5" cy="7.5" r="2"/><path d="M3.5 15.5c.6-2.2 2.2-3.5 4-3.5s3.4 1.3 4 3.5"/><path d="M11.5 12.2c1.1-.5 2.4-.4 3.5.4.7.5 1.2 1.3 1.5 2.4"/></svg>',
			'reports'   => '<svg ' . $attrs . '><path d="M4 15.5V9.5M10 15.5V4.5M16 15.5v-4"/></svg>',
			'settings'  => '<svg ' . $attrs . '><circle cx="10" cy="10" r="2.4"/><path d="M10 3.2v1.7M10 15.1v1.7M3.2 10h1.7M15.1 10h1.7M5.2 5.2l1.2 1.2M13.6 13.6l1.2 1.2M14.8 5.2l-1.2 1.2M6.4 13.6l-1.2 1.2"/></svg>',
			'ai'        => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/><path d="M7.5 7.5 9.5 9.5M14.5 14.5l2 2M16.5 7.5 14.5 9.5M9.5 14.5l-2 2"/><circle cx="12" cy="12" r="3.2"/></svg>',
			default     => '',
		};
	}
}
