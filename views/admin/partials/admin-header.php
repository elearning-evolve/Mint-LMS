<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$newCourseUrl  = $newCourseUrl ?? \MintLMS\Infrastructure\Admin\CourseBuilderPage::newCourseUrl();
$pageLabel     = $pageLabel ?? '';
$pageLabelHtml = $pageLabelHtml ?? '';
$headerActions = $headerActions ?? '';
$maxWidthClass = $maxWidthClass ?? 'mint-max-w-content';
$headerWidth   = $maxWidthClass;
?>
<header class="mint-admin-header">
	<div class="mint-admin-header__inner mint-mx-auto <?php echo esc_attr( $headerWidth ); ?>">
		<div class="mint-admin-brand">
			<div class="mint-admin-brand__mark" aria-hidden="true">
				<div class="mint-admin-brand__mark-inner"></div>
			</div>
			<span class="mint-admin-brand__name"><?php esc_html_e( 'Mint LMS', 'mint-lms' ); ?></span>
			<?php if ( '' !== $pageLabelHtml ) : ?>
				<div class="mint-admin-brand__sep" aria-hidden="true"></div>
				<span class="mint-admin-brand__page mint-admin-brand__page--crumb"><?php echo $pageLabelHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller-escaped crumb HTML. ?></span>
			<?php elseif ( '' !== $pageLabel ) : ?>
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
				<form
					class="mint-dash-header-search"
					method="get"
					action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>"
					role="search"
				>
					<input type="hidden" name="post_type" value="<?php echo esc_attr( \MintLMS\Infrastructure\PostType\PostTypes::COURSE ); ?>" />
					<label class="mint-relative mint-block">
						<span class="mint-sr-only"><?php esc_html_e( 'Search courses', 'mint-lms' ); ?></span>
						<span class="mint-pointer-events-none mint-absolute mint-left-[13px] mint-top-1/2 mint--translate-y-1/2 mint-text-ink-2" aria-hidden="true">
							<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3 3"/></svg>
						</span>
						<input
							type="search"
							name="s"
							class="mint-search-input mint-h-[38px] mint-min-w-[220px] mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-white mint-py-0 mint-pl-10 mint-pr-[13px] mint-text-[15px] mint-text-ink-2 placeholder:mint-text-ink-2 focus:mint-outline-none"
							placeholder="<?php echo esc_attr__( 'Search courses', 'mint-lms' ); ?>"
							autocomplete="off"
							value=""
						/>
					</label>
				</form>
				<a
					href="<?php echo esc_url( $newCourseUrl ); ?>"
					class="mint-dash-new-course mint-inline-flex mint-h-[38px] mint-items-center mint-justify-center mint-gap-2 mint-rounded-md mint-border-0 mint-px-[15px] mint-text-sm mint-font-semibold mint-no-underline focus-visible:mint-outline-none"
				>
					<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11"/></svg>
					<?php esc_html_e( 'New course', 'mint-lms' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</header>
