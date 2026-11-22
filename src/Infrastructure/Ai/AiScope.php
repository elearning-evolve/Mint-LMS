<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ai;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Ai\AiScope as ApplicationAiScope;
use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Infrastructure facade over Application AiScope, cross-checked with PostTypes.
 *
 * Prefer Application\Ai\AiScope from Application code.
 */
final class AiScope {

	public const ROOT_COURSE = ApplicationAiScope::ROOT_COURSE;
	public const ROOT_LESSON = ApplicationAiScope::ROOT_LESSON;
	public const ROOT_QUIZ   = ApplicationAiScope::ROOT_QUIZ;

	/**
	 * @return list<string>
	 */
	public static function allowedPostTypes(): array {
		return PostTypes::all();
	}

	public static function isAllowedPostType( string $postType ): bool {
		$type = sanitize_key( $postType );

		return PostTypes::isContentType( $type ) && ApplicationAiScope::isAllowedPostType( $type );
	}

	/**
	 * @return list<string>
	 */
	public static function allowedRoots(): array {
		return ApplicationAiScope::allowedRoots();
	}

	public static function isAllowedRoot( string $root ): bool {
		return ApplicationAiScope::isAllowedRoot( sanitize_key( $root ) );
	}

	/**
	 * @return list<string>
	 */
	public static function forbiddenPostTypes(): array {
		return ApplicationAiScope::forbiddenPostTypes();
	}

	public static function isForbiddenPostType( string $postType ): bool {
		return ApplicationAiScope::isForbiddenPostType( sanitize_key( $postType ) );
	}

	/**
	 * @return list<string>
	 */
	public static function allowedQuestionTypes(): array {
		return ApplicationAiScope::allowedQuestionTypes();
	}

	public static function systemBoundaryText(): string {
		return ApplicationAiScope::systemBoundaryText();
	}
}
