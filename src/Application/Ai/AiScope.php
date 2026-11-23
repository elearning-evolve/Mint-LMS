<?php
declare(strict_types=1);

namespace MintLMS\Application\Ai;

defined( 'ABSPATH' ) || exit;

/**
 * Hard boundary for AI features: Mint LMS content only.
 *
 * Keep this Application-pure (no WordPress APIs). CPT slugs must stay aligned
 * with Infrastructure\PostType\PostTypes.
 */
final class AiScope {

	public const ROOT_COURSE = 'course';
	public const ROOT_LESSON = 'lesson';
	public const ROOT_QUIZ   = 'quiz';

	public const POST_COURSE   = 'mint-course';
	public const POST_LESSON   = 'mint-lesson';
	public const POST_QUIZ     = 'mint-quiz';
	public const POST_QUESTION = 'mint-question';

	/**
	 * @return list<string>
	 */
	public static function allowedPostTypes(): array {
		return array(
			self::POST_COURSE,
			self::POST_LESSON,
			self::POST_QUIZ,
			self::POST_QUESTION,
		);
	}

	public static function isAllowedPostType( string $postType ): bool {
		return in_array( self::normalizeKey( $postType ), self::allowedPostTypes(), true );
	}

	/**
	 * @return list<string>
	 */
	public static function allowedRoots(): array {
		return array( self::ROOT_COURSE, self::ROOT_LESSON, self::ROOT_QUIZ );
	}

	public static function isAllowedRoot( string $root ): bool {
		return in_array( self::normalizeKey( $root ), self::allowedRoots(), true );
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
		$type = self::normalizeKey( $postType );
		if ( '' === $type ) {
			return false;
		}
		if ( in_array( $type, self::forbiddenPostTypes(), true ) ) {
			return true;
		}

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

	private static function normalizeKey( string $value ): string {
		$value = strtolower( trim( $value ) );
		$clean = preg_replace( '/[^a-z0-9_\-]/', '', $value );

		return is_string( $clean ) ? $clean : '';
	}
}
