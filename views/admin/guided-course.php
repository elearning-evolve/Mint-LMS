<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Infrastructure\Admin\ViewRenderer;

$renderer = new ViewRenderer();

$inputClass = 'mint-block mint-w-full mint-h-[50px] mint-rounded-lg mint-border mint-border-control mint-bg-bg mint-px-[14px] mint-text-[17px] mint-text-ink hover:mint-border-mint-blue focus:mint-border-mint-blue focus:mint-shadow-focus focus:mint-outline-none';
$textareaClass = 'mint-block mint-w-full mint-min-h-[100px] mint-resize-y mint-rounded-lg mint-border mint-border-control mint-bg-bg mint-px-[14px] mint-py-[11px] mint-text-base mint-text-ink hover:mint-border-mint-blue focus:mint-border-mint-blue focus:mint-shadow-focus focus:mint-outline-none';
?>
<div class="wrap mint-lms-admin-wrap mint-m-0 mint-p-0">
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::open( 'admin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div x-data="mintGuidedCourse()" class="mint-flex mint-min-h-screen mint-flex-col mint-bg-bg">

			<div class="mint-h-1 mint-shrink-0 mint-bg-neutral-200">
				<div
					class="mint-h-full mint-rounded-r-sm mint-bg-accent mint-transition-[width] mint-duration-300 mint-ease-out"
					:class="{
						'mint-w-1/3': step === 1,
						'mint-w-2/3': step === 2,
						'mint-w-full': step === 3
					}"
				></div>
			</div>

			<header class="mint-flex mint-h-16 mint-shrink-0 mint-items-center mint-justify-between mint-border-b mint-border-rule-soft mint-px-7">
				<div class="mint-flex mint-items-center mint-gap-[10px]">
					<template x-for="i in 3" :key="i">
						<div class="mint-contents">
							<div
								class="mint-flex mint-h-[30px] mint-w-[30px] mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-text-sm mint-font-bold"
								:class="i < step ? 'mint-bg-accent mint-text-neutral-50' : (i === step ? 'mint-bg-accent mint-text-neutral-50' : 'mint-bg-bg-subtle mint-text-ink-2')"
							>
								<template x-if="i < step">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
								</template>
								<template x-if="i >= step">
									<span x-text="i"></span>
								</template>
							</div>
							<template x-if="i < 3">
								<div
									class="mint-h-[2px] mint-w-6 mint-shrink-0"
									:class="i < step ? 'mint-bg-accent' : 'mint-bg-bg-subtle'"
								></div>
							</template>
						</div>
					</template>
				</div>

				<?php
				echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
					'button',
					array(
						'variant' => 'ghost',
						'size'    => 'sm',
						'label'   => esc_html__( 'Skip setup', 'mint-lms' ),
						'type'    => 'button',
						'attrs'   => '@click="skip()" :disabled="loading"',
					)
				);
				?>
			</header>

			<div class="mint-flex mint-flex-1 mint-items-start mint-justify-center mint-px-7 mint-pb-10 mint-pt-14">
				<div class="mint-w-full mint-max-w-[580px]">

					<span class="mint-text-over mint-font-semibold mint-uppercase mint-text-accent" x-text="stepLabel"></span>

					<h1 class="mint-m-0 mint-mt-3 mint-text-h1 mint-font-semibold mint-text-ink" x-text="stepTitle"></h1>

					<p class="mint-m-0 mint-mt-3 mint-text-[19px] mint-leading-[30px] mint-text-ink-2" x-show="step === 1">
						<?php esc_html_e( 'Students see this on your course page.', 'mint-lms' ); ?>
					</p>
					<p class="mint-m-0 mint-mt-3 mint-text-[19px] mint-leading-[30px] mint-text-ink-2" x-show="step === 2" x-cloak>
						<?php esc_html_e( 'Every course needs at least one lesson group and lesson.', 'mint-lms' ); ?>
					</p>
					<p class="mint-m-0 mint-mt-3 mint-text-[19px] mint-leading-[30px] mint-text-ink-2" x-show="step === 3" x-cloak>
						<?php esc_html_e( 'Publish now or keep editing as a draft.', 'mint-lms' ); ?>
					</p>

					<div x-show="loading" x-cloak class="mint-py-12 mint-text-center mint-text-base mint-text-ink-2">
						<?php esc_html_e( 'Saving…', 'mint-lms' ); ?>
					</div>

					<template x-if="step === 1 && !loading">
						<div class="mint-mt-9 mint-flex mint-flex-col mint-gap-6">
							<div>
								<label for="mint-guided-course-title" class="mint-mb-2 mint-block mint-text-base mint-font-semibold mint-text-ink">
									<?php esc_html_e( 'Course title', 'mint-lms' ); ?> <span class="mint-text-danger">*</span>
								</label>
								<input
									id="mint-guided-course-title"
									type="text"
									x-model="courseTitle"
									class="<?php echo esc_attr( $inputClass ); ?>"
									placeholder="<?php echo esc_attr__( 'Introduction to Photography', 'mint-lms' ); ?>"
								/>
							</div>
							<div>
								<label for="mint-guided-course-description" class="mint-mb-2 mint-block mint-text-base mint-font-semibold mint-text-ink">
									<?php esc_html_e( 'Description', 'mint-lms' ); ?>
								</label>
								<p class="mint-m-0 mint-mb-2 mint-text-sm mint-text-ink-2"><?php esc_html_e( 'A short summary shown on the course landing page.', 'mint-lms' ); ?></p>
								<textarea
									id="mint-guided-course-description"
									x-model="courseDescription"
									rows="4"
									class="<?php echo esc_attr( $textareaClass ); ?>"
									placeholder="<?php echo esc_attr__( 'What will students learn?', 'mint-lms' ); ?>"
								></textarea>
							</div>
						</div>
					</template>

					<template x-if="step === 2 && !loading">
						<div class="mint-mt-9 mint-flex mint-flex-col mint-gap-6">
							<div>
								<label for="mint-guided-section-title" class="mint-mb-2 mint-block mint-text-base mint-font-semibold mint-text-ink">
									<?php esc_html_e( 'Lesson group name', 'mint-lms' ); ?> <span class="mint-text-danger">*</span>
								</label>
								<p class="mint-m-0 mint-mb-2 mint-text-sm mint-text-ink-2"><?php esc_html_e( 'Group related lessons together.', 'mint-lms' ); ?></p>
								<input
									id="mint-guided-section-title"
									type="text"
									x-model="sectionTitle"
									class="<?php echo esc_attr( $inputClass ); ?>"
									placeholder="<?php echo esc_attr__( 'Getting started', 'mint-lms' ); ?>"
								/>
							</div>
							<div>
								<label for="mint-guided-lesson-title" class="mint-mb-2 mint-block mint-text-base mint-font-semibold mint-text-ink">
									<?php esc_html_e( 'First lesson', 'mint-lms' ); ?> <span class="mint-text-danger">*</span>
								</label>
								<p class="mint-m-0 mint-mb-2 mint-text-sm mint-text-ink-2"><?php esc_html_e( 'You can add more lessons later in the builder.', 'mint-lms' ); ?></p>
								<input
									id="mint-guided-lesson-title"
									type="text"
									x-model="lessonTitle"
									class="<?php echo esc_attr( $inputClass ); ?>"
									placeholder="<?php echo esc_attr__( 'Welcome & overview', 'mint-lms' ); ?>"
								/>
							</div>
						</div>
					</template>

					<template x-if="step === 3 && !loading">
						<div class="mint-mt-12 mint-text-center">
							<div class="mint-mx-auto mint-mb-5 mint-inline-flex mint-h-16 mint-w-16 mint-items-center mint-justify-center mint-rounded-2xl mint-bg-success-wash mint-text-success">
								<svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
							</div>
							<p class="mint-m-0 mint-text-[20px] mint-font-semibold mint-text-ink" x-text="courseTitle"></p>
							<p class="mint-m-0 mint-mt-2 mint-text-base mint-text-ink-2"><?php esc_html_e( 'Ready to go live', 'mint-lms' ); ?></p>
						</div>
					</template>

					<div x-show="error" x-cloak class="mint-mt-6 mint-rounded-lg mint-bg-danger-wash mint-px-[18px] mint-py-[14px] mint-text-sm mint-text-danger" x-text="error"></div>
				</div>
			</div>

			<footer class="mint-flex mint-shrink-0 mint-items-center mint-justify-between mint-border-t mint-border-rule-soft mint-px-7 mint-py-4">
				<div>
					<button
						type="button"
						class="mint-inline-flex mint-h-control-lg mint-items-center mint-justify-center mint-rounded-lg mint-bg-transparent mint-px-[18px] mint-text-base mint-font-semibold mint-text-ink-2 hover:mint-bg-bg-subtle hover:mint-text-ink disabled:mint-opacity-[0.45]"
						x-show="step > 1"
						x-cloak
						@click="prevStep()"
						:disabled="loading"
					><?php esc_html_e( 'Back', 'mint-lms' ); ?></button>
				</div>

				<div class="mint-flex mint-items-center mint-gap-4">
					<template x-if="step < 3">
						<div class="mint-flex mint-items-center mint-gap-4">
							<span class="mint-text-sm mint-text-ink-3"><?php esc_html_e( 'Takes about 2 minutes', 'mint-lms' ); ?></span>
							<button type="button" class="mint-cta-lime mint-inline-flex mint-h-control-lg mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-px-[18px] mint-text-base mint-font-semibold disabled:mint-opacity-[0.45]" @click="nextStep()" :disabled="loading">
								<span x-text="loading ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Continue', 'mint-lms' ) ); ?>'"></span>
							</button>
						</div>
					</template>
					<template x-if="step === 3">
						<div class="mint-flex mint-gap-[10px]">
							<button type="button" class="mint-inline-flex mint-h-control-lg mint-items-center mint-justify-center mint-rounded-lg mint-border mint-border-control mint-bg-bg mint-px-[18px] mint-text-base mint-font-semibold mint-text-ink hover:mint-bg-bg-subtle disabled:mint-opacity-[0.45]" @click="finish(false)" :disabled="loading"><?php esc_html_e( 'Save draft', 'mint-lms' ); ?></button>
							<button type="button" class="mint-cta-lime mint-inline-flex mint-h-control-lg mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-px-[18px] mint-text-base mint-font-semibold disabled:mint-opacity-[0.45]" @click="finish(true)" :disabled="loading"><?php esc_html_e( 'Publish', 'mint-lms' ); ?></button>
						</div>
					</template>
				</div>
			</footer>

		</div>

		<?php echo $renderer->component( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
