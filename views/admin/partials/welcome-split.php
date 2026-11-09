<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\FirstRunRedirect;

/**
 * Shared first-run / empty-dashboard welcome (A1 design match).
 *
 * Accent mint: #98FBCB · CTA: #0B4F3F · Right wash: #F1FFF9
 */

$createUrl = $createUrl ?? FirstRunRedirect::startGuidedUrl();
$skipUrl   = $skipUrl ?? FirstRunRedirect::skipFirstRunUrl();
$showSkip  = $showSkip ?? true;

$features = array(
	array(
		'label' => __( 'Add lessons as text or video', 'mint-lms' ),
		'icon'  => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 5.5h11M4.5 10h11M4.5 14.5h7"/></svg>',
	),
	array(
		'label' => __( 'Students see their progress as they go', 'mint-lms' ),
		'icon'  => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 14.5 8 10l3 3 4.5-6"/></svg>',
	),
	array(
		'label' => __( 'Works with the theme you already have', 'mint-lms' ),
		'icon'  => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 3.5 16.5 7v6L10 16.5 3.5 13V7z"/></svg>',
	),
);

$structure_steps = array(
	array(
		'key'    => 'courses',
		'label'  => __( 'Courses', 'mint-lms' ),
		'desc'   => __( 'The top-level container for everything your students learn.', 'mint-lms' ),
		'height' => 50,
		'size'   => 17,
	),
	array(
		'key'    => 'lessons',
		'label'  => __( 'Lessons', 'mint-lms' ),
		'desc'   => __( 'A lesson group inside a course, grouping related topics together.', 'mint-lms' ),
		'height' => 46,
		'size'   => 16,
	),
	array(
		'key'    => 'topics',
		'label'  => __( 'Topics', 'mint-lms' ),
		'desc'   => __( 'A single page of content inside a lesson — text or video.', 'mint-lms' ),
		'height' => 44,
		'size'   => 16,
	),
	array(
		'key'    => 'quiz',
		'label'  => __( 'Quiz', 'mint-lms' ),
		'desc'   => __( 'A short check at the end of a topic to test understanding.', 'mint-lms' ),
		'height' => 42,
		'size'   => 15,
	),
	array(
		'key'    => 'question',
		'label'  => __( 'Question', 'mint-lms' ),
		'desc'   => __( 'One graded item inside a quiz.', 'mint-lms' ),
		'height' => 40,
		'size'   => 15,
	),
);

$structure_steps_json = wp_json_encode( $structure_steps );
if ( false === $structure_steps_json ) {
	$structure_steps_json = '[]';
}
?>
<div
	class="mint-grid mint-min-h-[calc(100vh-32px)] mint-grid-cols-1 mint-bg-white lg:mint-grid-cols-2"
	x-data="mintWelcomeDashboard(<?php echo esc_attr( $structure_steps_json ); ?>)"
>
	<section class="mint-flex mint-flex-col mint-justify-between mint-bg-white mint-px-10 mint-py-12 sm:mint-px-14 sm:mint-py-16 lg:mint-px-[2.5rem] lg:mint-py-12 lg:mint-border-r lg:mint-border-[#DAD7E6]">
		<div>
			<div class="mint-mb-11 mint-flex mint-items-center mint-justify-between mint-gap-4">
				<div class="mint-flex mint-items-center mint-gap-2.5">
					<div class="mint-flex mint-h-7 mint-w-7 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-lg mint-bg-[#98FBCB]" aria-hidden="true">
						<div class="mint-h-2.5 mint-w-2.5 mint-rounded-[3px] mint-border-2 mint-border-white"></div>
					</div>
					<span class="mint-text-[17px] mint-font-semibold mint-tracking-[-0.015em] mint-text-[#0F0E1A]"><?php esc_html_e( 'Mint LMS', 'mint-lms' ); ?></span>
				</div>
				<span class="mint-inline-flex mint-items-center mint-rounded-full mint-bg-[#E8FFF3] mint-px-3 mint-py-1.5 mint-text-[13px] mint-font-bold mint-tracking-[0.04em] mint-text-[#0B4F3F]">
					<?php esc_html_e( 'Step 1 of 3', 'mint-lms' ); ?>
				</span>
			</div>

			<h1 class="mint-m-0 mint-max-w-[27rem] mint-text-[2.75rem] mint-font-semibold mint-leading-[1.09] mint-tracking-[-0.04em] mint-text-[#0F0E1A] sm:mint-text-[44px] sm:mint-leading-[48px]">
				<?php esc_html_e( 'Teach what you already know.', 'mint-lms' ); ?>
			</h1>
			<p class="mint-m-0 mint-mt-[18px] mint-max-w-[27rem] mint-text-[19px] mint-leading-[30px] mint-text-[#33334A]">
				<?php esc_html_e( 'Turn what you teach into a course your students can follow at their own pace. No theme edits, no plugins to wire up.', 'mint-lms' ); ?>
			</p>
		</div>

		<ul class="mint-m-0 mint-mt-12 mint-flex mint-list-none mint-flex-col mint-gap-4 mint-p-0">
			<?php foreach ( $features as $feature ) : ?>
				<li class="mint-flex mint-items-center mint-gap-[13px]">
					<span class="mint-flex mint-h-[34px] mint-w-[34px] mint-shrink-0 mint-items-center mint-justify-center mint-rounded-[10px] mint-bg-[#E8FFF3] mint-text-[#0B4F3F]">
						<?php echo $feature['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup. ?>
					</span>
					<span class="mint-text-base mint-font-medium mint-text-[#0F0E1A]"><?php echo esc_html( $feature['label'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<section class="mint-flex mint-flex-col mint-justify-center mint-bg-[#F1FFF9] mint-px-10 mint-py-12 sm:mint-px-14 sm:mint-py-16 lg:mint-px-[2.5rem] lg:mint-py-12">
		<div class="mint-w-full mint-max-w-[390px]">
			<h2 class="mint-m-0 mint-text-[28px] mint-font-semibold mint-leading-[34px] mint-tracking-[-0.025em] mint-text-[#0F0E1A]">
				<?php esc_html_e( 'What are you teaching?', 'mint-lms' ); ?>
			</h2>
			<p
				class="mint-m-0 mint-mt-2 mint-min-h-[28px] mint-text-[17px] mint-leading-7 mint-text-[#33334A]"
				x-text="stepDesc()"
			></p>

			<div class="mint-structure-tree mint-mt-[22px]" aria-label="<?php esc_attr_e( 'Course structure', 'mint-lms' ); ?>">
				<template x-for="(step, index) in steps" :key="step.key">
					<div class="mint-structure-row" :style="'margin-left:' + (index * 18) + 'px'">
						<div
							x-show="index > 0"
							class="mint-structure-l"
							aria-hidden="true"
						></div>
						<button
							type="button"
							class="mint-structure-node"
							:class="{ 'is-active': isActive(step.key) }"
							:style="'height:' + step.height + 'px;font-size:' + step.size + 'px'"
							@click="selectStep(step.key)"
							x-text="step.label"
						></button>
					</div>
				</template>
			</div>

			<div class="mint-mt-[22px] mint-flex mint-flex-wrap mint-items-center mint-gap-[14px]">
				<a
					href="<?php echo esc_url( $createUrl ); ?>"
					class="mint-welcome-cta mint-inline-flex mint-h-12 mint-items-center mint-justify-center mint-rounded-[10px] mint-px-[22px] mint-text-[17px] mint-font-semibold mint-no-underline focus-visible:mint-outline-none"
				>
					<?php esc_html_e( 'Create your first course', 'mint-lms' ); ?>
				</a>
				<?php if ( $showSkip ) : ?>
					<a
						href="<?php echo esc_url( $skipUrl ); ?>"
						class="mint-welcome-skip mint-inline-flex mint-h-12 mint-items-center mint-justify-center mint-rounded-[10px] mint-border-[1.5px] mint-border-transparent mint-bg-transparent mint-px-4 mint-text-base mint-font-semibold mint-text-[#33334A] mint-no-underline focus-visible:mint-outline-none"
					>
						<?php esc_html_e( 'Skip for now', 'mint-lms' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div>
