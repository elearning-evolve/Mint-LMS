<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var string $initialStatus */
$initialStatus = $initialStatus ?? 'pending';
?>
<div
	class="mint-py-8"
	x-data="mintEssayGrading({ status: '<?php echo esc_js( $initialStatus ); ?>' })"
	x-init="load()"
>
	<div class="mint-mb-6 mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-3">
		<div>
			<h1 class="mint-m-0 mint-text-2xl mint-font-semibold mint-text-ink"><?php esc_html_e( 'Submitted essays', 'mint-lms' ); ?></h1>
			<p class="mint-mt-1 mint-mb-0 mint-text-sm mint-text-ink-2"><?php esc_html_e( 'Review student essay answers and award points. Quiz scores update when you grade.', 'mint-lms' ); ?></p>
		</div>
		<div class="mint-inline-flex mint-rounded-lg mint-border mint-border-rule mint-bg-bg mint-p-1">
			<button type="button" class="mint-rounded-md mint-border-0 mint-px-3 mint-py-1.5 mint-text-sm mint-font-medium" :class="status === 'pending' ? 'mint-bg-mint-blue mint-text-ink' : 'mint-bg-transparent mint-text-ink-2'" @click="setStatus('pending')"><?php esc_html_e( 'Pending', 'mint-lms' ); ?></button>
			<button type="button" class="mint-rounded-md mint-border-0 mint-px-3 mint-py-1.5 mint-text-sm mint-font-medium" :class="status === 'graded' ? 'mint-bg-mint-blue mint-text-ink' : 'mint-bg-transparent mint-text-ink-2'" @click="setStatus('graded')"><?php esc_html_e( 'Graded', 'mint-lms' ); ?></button>
			<button type="button" class="mint-rounded-md mint-border-0 mint-px-3 mint-py-1.5 mint-text-sm mint-font-medium" :class="status === 'all' ? 'mint-bg-mint-blue mint-text-ink' : 'mint-bg-transparent mint-text-ink-2'" @click="setStatus('all')"><?php esc_html_e( 'All', 'mint-lms' ); ?></button>
		</div>
	</div>

	<p x-show="loading" class="mint-text-sm mint-text-ink-2" x-cloak><?php esc_html_e( 'Loading…', 'mint-lms' ); ?></p>
	<p x-show="!loading && error" class="mint-text-sm mint-text-danger" x-text="error" x-cloak></p>
	<p x-show="!loading && !error && items.length === 0" class="mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-p-6 mint-text-sm mint-text-ink-2" x-cloak>
		<?php esc_html_e( 'No essay submissions in this filter.', 'mint-lms' ); ?>
	</p>

	<div class="mint-flex mint-flex-col mint-gap-4" x-show="!loading && items.length" x-cloak>
		<template x-for="item in items" :key="item.id">
			<article class="mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-p-5">
				<div class="mint-mb-3 mint-flex mint-flex-wrap mint-items-start mint-justify-between mint-gap-3">
					<div>
						<div class="mint-text-xs mint-font-semibold mint-uppercase mint-tracking-widest mint-text-ink-3" x-text="item.quizTitle || ('Quiz #' + item.quizId)"></div>
						<h2 class="mint-mt-1 mint-mb-0 mint-text-lg mint-font-semibold mint-text-ink" x-text="item.questionPrompt || ('Question #' + item.questionId)"></h2>
						<p class="mint-mt-1 mint-mb-0 mint-text-sm mint-text-ink-2">
							<span x-text="item.userName || ('User #' + item.userId)"></span>
							<span class="mint-text-ink-3"> · </span>
							<span x-text="item.status === 'graded' ? '<?php echo esc_js( __( 'Graded', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Pending', 'mint-lms' ) ); ?>'"></span>
						</p>
					</div>
					<div class="mint-text-sm mint-text-ink-2">
						<span x-text="item.pointsAwarded"></span>
						<span>/</span>
						<span x-text="item.pointsPossible"></span>
						<span><?php esc_html_e( 'pts', 'mint-lms' ); ?></span>
					</div>
				</div>

				<div class="mint-mb-4 mint-rounded-lg mint-border mint-border-rule mint-bg-white mint-p-4 mint-text-sm mint-text-ink mint-whitespace-pre-wrap" x-text="item.answerText || '—'"></div>
				<p class="mint-mb-4 mint-mt-0" x-show="item.attachmentUrl">
					<a class="mint-text-sm mint-font-medium mint-text-ink mint-underline" :href="item.attachmentUrl" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Download uploaded file', 'mint-lms' ); ?></a>
				</p>

				<div class="mint-flex mint-flex-wrap mint-items-end mint-gap-3" x-show="item.status === 'pending' || status === 'graded' || status === 'all'">
					<label class="mint-flex mint-flex-col mint-gap-1">
						<span class="mint-text-xs mint-font-semibold mint-uppercase mint-tracking-widest mint-text-ink-3"><?php esc_html_e( 'Points', 'mint-lms' ); ?></span>
						<input type="number" min="0" :max="item.pointsPossible" step="0.5" class="mint-h-10 mint-w-28 mint-rounded-lg mint-border mint-border-rule mint-px-3 mint-text-sm" x-model.number="item._points" />
					</label>
					<button
						type="button"
						class="mint-cta-lime mint-inline-flex mint-h-10 mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-px-4 mint-text-sm mint-font-semibold"
						:disabled="item._saving"
						@click="grade(item)"
					>
						<span x-text="item._saving ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Approve grade', 'mint-lms' ) ); ?>'"></span>
					</button>
				</div>
			</article>
		</template>
	</div>
</div>
