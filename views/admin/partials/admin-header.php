<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$newCourseUrl  = $newCourseUrl ?? \MintLMS\Infrastructure\Admin\CourseBuilderPage::newCourseUrl();
$pageLabel     = $pageLabel ?? '';
$headerActions = $headerActions ?? '';
$maxWidthClass = $maxWidthClass ?? 'mint-max-w-content';
$searchUrl     = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::COURSE );
$headerWidth   = $maxWidthClass;
?>
<header class="mint-admin-header">
	<div class="mint-admin-header__inner mint-mx-auto <?php echo esc_attr( $headerWidth ); ?>">
		<div class="mint-admin-brand">
			<div class="mint-admin-brand__mark" aria-hidden="true">
				<div class="mint-admin-brand__mark-inner"></div>
			</div>
			<span class="mint-admin-brand__name"><?php esc_html_e( 'Mint LMS', 'mint-lms' ); ?></span>
			<?php if ( '' !== $pageLabel ) : ?>
				<div class="mint-admin-brand__sep" aria-hidden="true"></div>
				<span class="mint-admin-brand__page"><?php echo esc_html( $pageLabel ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $headerActions ) : ?>
			<div class="mint-admin-header__actions">
				<?php echo $headerActions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller-constructed button markup. ?>
			</div>
		<?php else : ?>
			<div class="mint-admin-header__actions">
				<a
					href="<?php echo esc_url( $searchUrl ); ?>"
					class="mint-dash-search mint-inline-flex mint-h-[34px] mint-w-[34px] mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-transparent mint-text-ink-3 mint-no-underline focus-visible:mint-outline-none"
					aria-label="<?php esc_attr_e( 'Search courses', 'mint-lms' ); ?>"
				>
					<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#5C5C77" stroke-width="1.85" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3 3"/></svg>
				</a>
				<a
					href="<?php echo esc_url( $newCourseUrl ); ?>"
					class="mint-dash-new-course mint-inline-flex mint-h-[34px] mint-items-center mint-justify-center mint-gap-[7px] mint-rounded-md mint-border-0 mint-px-[13px] mint-text-sm mint-font-semibold mint-no-underline focus-visible:mint-outline-none"
				>
					<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11"/></svg>
					<?php esc_html_e( 'New course', 'mint-lms' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</header>
