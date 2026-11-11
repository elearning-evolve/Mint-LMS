<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Setup;

defined( 'ABSPATH' ) || exit;

final class PageSettings {

	public const OPTION_DASHBOARD          = 'mintlms_page_dashboard';
	public const OPTION_CATALOG            = 'mintlms_page_catalog';
	public const OPTION_PLAYER             = 'mintlms_page_player';
	public const OPTION_DEFAULT_ENROLLMENT = 'mintlms_default_enrollment';
	public const OPTION_EMAIL_ENROLL       = 'mintlms_email_enroll_enabled';
	public const OPTION_EMAIL_COMPLETE     = 'mintlms_email_complete_enabled';

	public const COURSE_QUERY_ARG = 'mintlms_course';

	/**
	 * @param array<string, mixed>|null $optionsOverride Used in tests to avoid WordPress option calls.
	 */
	public function __construct(
		private ?array $optionsOverride = null,
	) {
	}

	public function getDashboardPageId(): int {
		return max( 0, (int) $this->option( self::OPTION_DASHBOARD, 0 ) );
	}

	public function getCatalogPageId(): int {
		return max( 0, (int) $this->option( self::OPTION_CATALOG, 0 ) );
	}

	public function getPlayerPageId(): int {
		return max( 0, (int) $this->option( self::OPTION_PLAYER, 0 ) );
	}

	public function getDefaultEnrollment(): string {
		$value = (string) $this->option( self::OPTION_DEFAULT_ENROLLMENT, 'free' );

		return in_array( $value, array( 'open', 'free', 'manual', 'paid' ), true ) ? $value : 'free';
	}

	public function isEmailEnrollEnabled(): bool {
		return (bool) $this->option( self::OPTION_EMAIL_ENROLL, true );
	}

	public function isEmailCompleteEnabled(): bool {
		return (bool) $this->option( self::OPTION_EMAIL_COMPLETE, true );
	}

	public function getDashboardUrl(): string {
		return $this->permalinkForPageId( $this->getDashboardPageId() );
	}

	public function getCatalogUrl(): string {
		return $this->permalinkForPageId( $this->getCatalogPageId() );
	}

	public function getPlayerUrl(): string {
		return $this->permalinkForPageId( $this->getPlayerPageId() );
	}

	public function getCourseUrl( int $courseId ): string {
		if ( $courseId <= 0 ) {
			return $this->getCatalogUrl();
		}

		return add_query_arg( self::COURSE_QUERY_ARG, (string) $courseId, $this->getCatalogUrl() );
	}

	/**
	 * Resolve player URL: shortcode attribute overrides stored option.
	 */
	public function resolvePlayerUrl( string $attributeOverride = '' ): string {
		if ( '' !== $attributeOverride ) {
			$url = $this->permalinkForPageId( (int) $attributeOverride );

			if ( '' !== $url && home_url( '/' ) !== $url ) {
				return $url;
			}
		}

		$configured = $this->getPlayerUrl();

		if ( home_url( '/' ) !== $configured ) {
			return $configured;
		}

		return $this->currentPageUrl();
	}

	/**
	 * Resolve dashboard URL: shortcode attribute overrides stored option.
	 */
	public function resolveDashboardUrl( string $attributeOverride = '' ): string {
		if ( '' !== $attributeOverride ) {
			$url = $this->permalinkForPageId( (int) $attributeOverride );

			if ( '' !== $url && home_url( '/' ) !== $url ) {
				return $url;
			}
		}

		return $this->getDashboardUrl();
	}

	/**
	 * Resolve catalog/overview URL: shortcode attribute overrides stored option.
	 */
	public function resolveCatalogUrl( string $attributeOverride = '' ): string {
		if ( '' !== $attributeOverride ) {
			$url = $this->permalinkForPageId( (int) $attributeOverride );

			if ( '' !== $url && home_url( '/' ) !== $url ) {
				return $url;
			}
		}

		return $this->getCatalogUrl();
	}

	/**
	 * @return list<array{key: string, label: string, done: bool, detail: string}>
	 */
	public function getSetupChecklist(): array {
		$dashboardId = $this->getDashboardPageId();
		$catalogId   = $this->getCatalogPageId();
		$playerId    = $this->getPlayerPageId();

		return array(
			array(
				'key'    => 'dashboard_page',
				'label'  => __( 'Dashboard page set', 'mint-lms' ),
				'done'   => $this->isValidLmsPage( $dashboardId, '[mint_lms_dashboard]' ),
				'detail' => $this->pageChecklistDetail( $dashboardId, __( 'My Learning', 'mint-lms' ) ),
			),
			array(
				'key'    => 'catalog_page',
				'label'  => __( 'Course catalog page set', 'mint-lms' ),
				'done'   => $this->isValidLmsPage( $catalogId, '[mint_lms_catalog]' ),
				'detail' => $this->pageChecklistDetail( $catalogId, __( 'Courses', 'mint-lms' ) ),
			),
			array(
				'key'    => 'player_page',
				'label'  => __( 'Course player page set', 'mint-lms' ),
				'done'   => $this->isValidLmsPage( $playerId, '[mint_lms_player]' ),
				'detail' => $this->pageChecklistDetail( $playerId, __( 'Course Player', 'mint-lms' ) ),
			),
			array(
				'key'    => 'default_enrollment',
				'label'  => __( 'Default joining method set', 'mint-lms' ),
				'done'   => '' !== $this->getDefaultEnrollment(),
				'detail' => match ( $this->getDefaultEnrollment() ) {
					'open'  => __( 'Open for everyone', 'mint-lms' ),
					'free'  => __( 'Free', 'mint-lms' ),
					'paid'  => __( 'Paid', 'mint-lms' ),
					default => __( 'Invite only', 'mint-lms' ),
				},
			),
		);
	}

	private function isValidLmsPage( int $pageId, string $expectedShortcode ): bool {
		if ( $pageId <= 0 ) {
			return false;
		}

		$post = get_post( $pageId );

		if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
			return false;
		}

		if ( ! in_array( $post->post_status, array( 'publish', 'draft', 'private' ), true ) ) {
			return false;
		}

		return str_contains( (string) $post->post_content, $expectedShortcode );
	}

	private function pageChecklistDetail( int $pageId, string $fallbackTitle ): string {
		if ( $pageId <= 0 ) {
			return __( 'Not configured', 'mint-lms' );
		}

		$title = get_the_title( $pageId );

		if ( is_string( $title ) && '' !== $title ) {
			return $title;
		}

		return $fallbackTitle;
	}

	private function permalinkForPageId( int $pageId ): string {
		if ( $pageId <= 0 ) {
			return home_url( '/' );
		}

		$url = get_permalink( $pageId );

		return is_string( $url ) && '' !== $url ? $url : home_url( '/' );
	}

	private function currentPageUrl(): string {
		$permalink = get_permalink();

		if ( is_string( $permalink ) && '' !== $permalink ) {
			return $permalink;
		}

		return home_url( '/' );
	}

	private function option( string $key, mixed $default ): mixed {
		if ( null !== $this->optionsOverride && array_key_exists( $key, $this->optionsOverride ) ) {
			return $this->optionsOverride[ $key ];
		}

		return get_option( $key, $default );
	}
}
