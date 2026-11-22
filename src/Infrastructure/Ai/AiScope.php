<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ai;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Hard boundary for AI features: Mint LMS content only.
 *
 * Never allow AI flows to target core posts/pages, media libraries as content
 * hosts, or third-party plugin post types. Writes must go through Mint services
 * with Mint capabilities — this class is the allowlist gate for drafts too.
 */
final class AiScope {

	public const ROOT_COURSE = 'course';
	public const ROOT_LESSON = 'lesson';
	public const ROOT_QUIZ   = 'quiz';

	/**
	 * @return list<string>
	 */
	public static function allowedPostTypes(): array {
		return PostTypes::all();
	}

	public static function isAllowedPostType( string $postType ): bool {
		return PostTypes::isContentType( sanitize_key( $postType ) );
	}

	/**
	 * @return list<string>
	 */
	public static function allowedRoots(): array {
		return array( self::ROOT_COURSE, self::ROOT_LESSON, self::ROOT_QUIZ );
	}

	public static function isAllowedRoot( string $root ): bool {
		return in_array( sanitize_key( $root ), self::allowedRoots(), true );
	}

	/**
	 * Types AI must never create/update via Mint AI endpoints.
	 *
	 * @return list<string>
	 */
	public static function forbiddenPostTypes(): array {
		return array(
			'post',
			'page',
			'attachment',
			'revision',
			'nav_menu_item',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_navigation',
			'wp_global_styles',
			'product',
			'product_variation',
			'shop_order',
			'shop_coupon',
		);
	}

	public static function isForbiddenPostType( string $postType ): bool {
		$type = sanitize_key( $postType );
		if ( '' === $type ) {
			return false;
		}
		if ( in_array( $type, self::forbiddenPostTypes(), true ) ) {
			return true;
		}

		// Anything not in the Mint CPT allowlist is out of bounds.
		return ! self::isAllowedPostType( $type );
	}

	/**
	 * @return list<string>
	 */
	public static function allowedQuestionTypes(): array {
		return array( 'mcq', 'true_false' );
	}

	/**
	 * Human-readable scope blurb for AI system prompts.
	 */
	public static function systemBoundaryText(): string {
		return 'You assist Mint LMS only. You may describe ONLY these content types: '
			. 'mint-course, mint-lesson (inside lesson groups), mint-quiz, mint-question. '
			. 'Hierarchy: Lesson Groups → Lessons → Quizzes → Questions. '
			. 'Roots may be a full course, a standalone lesson, or a standalone quiz. '
			. 'NEVER create, edit, delete, or mention WordPress pages, posts, media as site content, '
			. 'menus, widgets, themes, users, WooCommerce, or any other plugin. '
			. 'NEVER output SQL, PHP, shell, or WordPress option keys. '
			. 'Return JSON only for Mint LMS structure — no markdown fences.';
	}
}
