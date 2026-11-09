<?php
/**
 * Quiz Settings full-page view (course builder).
 *
 * @package MintLMS
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mint-settings-page" x-show="quizSettingsView && lessonQuiz && lessonQuiz.settings" x-cloak>
	<button type="button" class="mint-settings-page__back" @click="closeQuizSettings()">
		<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 4.5-6 5.5 6 5.5"/></svg>
		<?php esc_html_e( 'Back to quiz', 'mint-lms' ); ?>
	</button>
	<h1 class="mint-settings-page__title"><?php esc_html_e( 'Quiz Settings', 'mint-lms' ); ?></h1>
	<div class="mint-settings-page__subtitle" x-text="lessonQuiz.title || t('newQuiz')"></div>

	<div class="mint-settings-card">
		<button type="button" class="mint-settings-card__head" :class="settingsAccordion.quizAccess ? 'is-open' : ''" @click="settingsAccordion.quizAccess = !settingsAccordion.quizAccess" :aria-expanded="settingsAccordion.quizAccess ? 'true' : 'false'">
			<div>
				<div class="mint-settings-card__head-title"><?php esc_html_e( 'Quiz access settings', 'mint-lms' ); ?></div>
				<div class="mint-settings-card__head-copy"><?php esc_html_e( 'Controls the requirements for accessing the quiz', 'mint-lms' ); ?></div>
			</div>
			<svg class="mint-settings-card__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
		</button>
		<div class="mint-settings-card__body" x-show="settingsAccordion.quizAccess" x-cloak>

			<div class="mint-settings-row mint-settings-row--top">
				<div class="mint-settings-row__label mint-settings-row__label--pad"><?php esc_html_e( 'When students get it', 'mint-lms' ); ?></div>
				<div class="mint-settings-choices">
					<button type="button" class="mint-settings-choice" :class="lessonQuiz.settings.releaseSchedule === 'immediately' ? 'is-selected' : ''" @click="lessonQuiz.settings.releaseSchedule = 'immediately'">
						<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
						<span><span class="mint-settings-choice__title"><?php esc_html_e( 'Right away', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Available as soon as a student enrolls.', 'mint-lms' ); ?></span></span>
					</button>
					<button type="button" class="mint-settings-choice" :class="lessonQuiz.settings.releaseSchedule === 'enrollment' ? 'is-selected' : ''" @click="lessonQuiz.settings.releaseSchedule = 'enrollment'">
						<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
						<span><span class="mint-settings-choice__title"><?php esc_html_e( 'Days after enrolling', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Opens a set number of days after a student enrolls.', 'mint-lms' ); ?></span></span>
					</button>
					<div class="mint-settings-nested--indent" x-show="lessonQuiz.settings.releaseSchedule === 'enrollment'" x-cloak style="display:flex;align-items:center;gap:10px">
						<input type="number" min="0" class="mint-settings-input mint-settings-input--days" x-model.number="lessonQuiz.settings.releaseDaysAfterEnrollment" placeholder="7" />
						<span class="mint-settings-hint-text"><?php esc_html_e( 'days after enrolling', 'mint-lms' ); ?></span>
					</div>
					<button type="button" class="mint-settings-choice" :class="lessonQuiz.settings.releaseSchedule === 'specific_date' ? 'is-selected' : ''" @click="lessonQuiz.settings.releaseSchedule = 'specific_date'">
						<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
						<span><span class="mint-settings-choice__title"><?php esc_html_e( 'On a specific date', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Opens for everyone on the date you choose.', 'mint-lms' ); ?></span></span>
					</button>
					<div class="mint-settings-nested--indent" x-show="lessonQuiz.settings.releaseSchedule === 'specific_date'" x-cloak>
						<input type="text" class="mint-settings-input mint-settings-input--date" x-model="lessonQuiz.settings.releaseDate" placeholder="<?php echo esc_attr__( 'DD / MM / YYYY', 'mint-lms' ); ?>" />
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'External quiz', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Quiz happens outside this course', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.externalQuiz ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.externalQuiz ? 'true' : 'false'" @click="lessonQuiz.settings.externalQuiz = !lessonQuiz.settings.externalQuiz"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.externalQuiz" x-cloak><?php esc_html_e( 'Taken outside Mint LMS, for example on another site', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Quiz prerequisites', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Quizzes students must finish first', 'mint-lms' ); ?>">?</span>
					<div class="mint-settings-select" :class="settingsDropdowns.prereq ? 'is-open' : ''" @click.outside="settingsDropdowns.prereq = false">
						<button type="button" class="mint-settings-select__trigger" @click="settingsDropdowns.prereq = !settingsDropdowns.prereq">
							<span x-text="quizPrereqLabel()"></span>
							<svg class="mint-settings-select__chevron" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
						</button>
						<div class="mint-settings-select__menu" x-show="settingsDropdowns.prereq" x-cloak>
							<button type="button" class="mint-settings-select__option" :class="!lessonQuiz.settings.prerequisiteQuizId ? 'is-active' : ''" @click="lessonQuiz.settings.prerequisiteQuizId = 0; settingsDropdowns.prereq = false"><?php esc_html_e( 'No prerequisite', 'mint-lms' ); ?></button>
							<template x-for="q in quizPrerequisiteOptions()" :key="'prereq-' + q.id">
								<button type="button" class="mint-settings-select__option" :class="Number(lessonQuiz.settings.prerequisiteQuizId) === Number(q.id) ? 'is-active' : ''" @click="lessonQuiz.settings.prerequisiteQuizId = Number(q.id); settingsDropdowns.prereq = false" x-text="q.title"></button>
							</template>
						</div>
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Allowed users', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Hide the quiz from visitors who aren’t logged in', 'mint-lms' ); ?>">?</span>
					<button type="button" class="mint-settings-check" @click="lessonQuiz.settings.allowedRegisteredOnly = !lessonQuiz.settings.allowedRegisteredOnly">
						<span class="mint-settings-check__box" :class="lessonQuiz.settings.allowedRegisteredOnly ? 'is-checked' : ''" aria-hidden="true">
							<svg x-show="lessonQuiz.settings.allowedRegisteredOnly" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
						</span>
						<span class="mint-settings-check__label"><?php esc_html_e( 'Only registered users can take this quiz', 'mint-lms' ); ?></span>
					</button>
				</div>
			</div>

		</div>
	</div>

	<div class="mint-settings-card">
		<button type="button" class="mint-settings-card__head" :class="settingsAccordion.quizProgression ? 'is-open' : ''" @click="settingsAccordion.quizProgression = !settingsAccordion.quizProgression" :aria-expanded="settingsAccordion.quizProgression ? 'true' : 'false'">
			<div>
				<div class="mint-settings-card__head-title"><?php esc_html_e( 'Progression and restriction settings', 'mint-lms' ); ?></div>
				<div class="mint-settings-card__head-copy"><?php esc_html_e( 'Controls the requirements for accessing and completing the quiz', 'mint-lms' ); ?></div>
			</div>
			<svg class="mint-settings-card__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
		</button>
		<div class="mint-settings-card__body" x-show="settingsAccordion.quizProgression" x-cloak>

			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Passing score', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<input type="number" min="0" max="100" class="mint-settings-input mint-settings-input--sm" x-model.number="lessonQuiz.passPercent" />
					<span class="mint-settings-hint-text"><?php esc_html_e( '%', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Quiz certificate', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<div class="mint-settings-select" :class="settingsDropdowns.cert ? 'is-open' : ''" @click.outside="settingsDropdowns.cert = false">
						<button type="button" class="mint-settings-select__trigger" @click="settingsDropdowns.cert = !settingsDropdowns.cert">
							<span x-text="quizCertificateLabel()"></span>
							<svg class="mint-settings-select__chevron" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
						</button>
						<div class="mint-settings-select__menu" x-show="settingsDropdowns.cert" x-cloak>
							<button type="button" class="mint-settings-select__option" :class="lessonQuiz.settings.certificate === 'none' ? 'is-active' : ''" @click="lessonQuiz.settings.certificate = 'none'; settingsDropdowns.cert = false"><?php esc_html_e( 'No certificate', 'mint-lms' ); ?></button>
							<button type="button" class="mint-settings-select__option" :class="lessonQuiz.settings.certificate === 'default' ? 'is-active' : ''" @click="lessonQuiz.settings.certificate = 'default'; settingsDropdowns.cert = false"><?php esc_html_e( 'Default certificate', 'mint-lms' ); ?></button>
						</div>
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Enable quiz saving', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Students can leave and come back to where they stopped', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.quizSavingEnabled ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.quizSavingEnabled ? 'true' : 'false'" @click="lessonQuiz.settings.quizSavingEnabled = !lessonQuiz.settings.quizSavingEnabled"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.quizSavingEnabled" x-cloak><?php esc_html_e( 'Progress is saved as students go', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.quizSavingEnabled" x-cloak>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Save progress every', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<input type="number" min="5" class="mint-settings-input mint-settings-input--sm" x-model.number="lessonQuiz.settings.quizSavingIntervalSeconds" />
						<span class="mint-settings-hint-text"><?php esc_html_e( 'seconds', 'mint-lms' ); ?></span>
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Restrict quiz retakes', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.restrictRetakes ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.restrictRetakes ? 'true' : 'false'" @click="lessonQuiz.settings.restrictRetakes = !lessonQuiz.settings.restrictRetakes"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.restrictRetakes" x-cloak><?php esc_html_e( 'Limit how many times students can retry', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.restrictRetakes" x-cloak>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Retries allowed', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint" title="<?php echo esc_attr__( 'How many extra attempts students get', 'mint-lms' ); ?>">?</span>
						<input type="number" min="0" class="mint-settings-input mint-settings-input--sm" x-model.number="lessonQuiz.settings.retriesAllowed" />
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Retries apply to', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<div class="mint-settings-select" :class="settingsDropdowns.apply ? 'is-open' : ''" @click.outside="settingsDropdowns.apply = false">
							<button type="button" class="mint-settings-select__trigger" @click="settingsDropdowns.apply = !settingsDropdowns.apply">
								<span x-text="quizRetriesApplyLabel()"></span>
								<svg class="mint-settings-select__chevron" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
							</button>
							<div class="mint-settings-select__menu" x-show="settingsDropdowns.apply" x-cloak>
								<button type="button" class="mint-settings-select__option" :class="lessonQuiz.settings.retriesApplicableTo === 'all' ? 'is-active' : ''" @click="lessonQuiz.settings.retriesApplicableTo = 'all'; settingsDropdowns.apply = false"><?php esc_html_e( 'All users', 'mint-lms' ); ?></button>
							</div>
						</div>
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Clears who counts as the same student', 'mint-lms' ); ?>">?</span>
						<button type="button" class="mint-quiz-action" @click="resetQuizUserIdentification()" :disabled="quizSaving"><?php esc_html_e( 'Reset the user identification', 'mint-lms' ); ?></button>
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Question completion', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<button type="button" class="mint-settings-check" @click="lessonQuiz.settings.questionCompletion = !lessonQuiz.settings.questionCompletion">
						<span class="mint-settings-check__box" :class="lessonQuiz.settings.questionCompletion ? 'is-checked' : ''" aria-hidden="true">
							<svg x-show="lessonQuiz.settings.questionCompletion" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
						</span>
						<span class="mint-settings-check__label"><?php esc_html_e( 'All questions required to complete', 'mint-lms' ); ?></span>
					</button>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Time limit', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.timeLimitEnabled ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.timeLimitEnabled ? 'true' : 'false'" @click="lessonQuiz.settings.timeLimitEnabled = !lessonQuiz.settings.timeLimitEnabled"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.timeLimitEnabled" x-cloak><?php esc_html_e( 'Quiz submits itself when time is up', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.timeLimitEnabled" x-cloak>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Submit automatically after', 'mint-lms' ); ?></div>
					<div class="mint-settings-time">
						<input type="text" class="mint-settings-input mint-settings-input--time" maxlength="2" placeholder="HH" x-model="lessonQuiz.settings.timeLimitHours" @blur="lessonQuiz.settings.timeLimitHours = clampTimePart(lessonQuiz.settings.timeLimitHours)" />
						<span class="mint-settings-time__sep">:</span>
						<input type="text" class="mint-settings-input mint-settings-input--time" maxlength="2" placeholder="MM" x-model="lessonQuiz.settings.timeLimitMinutes" @blur="lessonQuiz.settings.timeLimitMinutes = clampTimePart(lessonQuiz.settings.timeLimitMinutes)" />
						<span class="mint-settings-time__sep">:</span>
						<input type="text" class="mint-settings-input mint-settings-input--time" maxlength="2" placeholder="SS" x-model="lessonQuiz.settings.timeLimitSeconds" @blur="lessonQuiz.settings.timeLimitSeconds = clampTimePart(lessonQuiz.settings.timeLimitSeconds)" />
					</div>
				</div>
			</div>

		</div>
	</div>

	<div class="mint-settings-card">
		<button type="button" class="mint-settings-card__head" :class="settingsAccordion.quizDisplay ? 'is-open' : ''" @click="settingsAccordion.quizDisplay = !settingsAccordion.quizDisplay" :aria-expanded="settingsAccordion.quizDisplay ? 'true' : 'false'">
			<div>
				<div class="mint-settings-card__head-title"><?php esc_html_e( 'Display and content options', 'mint-lms' ); ?></div>
				<div class="mint-settings-card__head-copy"><?php esc_html_e( 'Controls how the quiz will look and what will be displayed', 'mint-lms' ); ?></div>
			</div>
			<svg class="mint-settings-card__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
		</button>
		<div class="mint-settings-card__body" x-show="settingsAccordion.quizDisplay" x-cloak>

			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Quiz materials', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Notes or downloads shown before the quiz', 'mint-lms' ); ?>">?</span>
					<button
						type="button"
						role="switch"
						class="mint-lesson-preview-toggle"
						:class="lessonQuiz.settings.materialsEnabled ? 'is-on' : ''"
						:aria-pressed="lessonQuiz.settings.materialsEnabled ? 'true' : 'false'"
						@click="toggleQuizMaterialsEnabled()"
					><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.materialsEnabled" x-cloak><?php esc_html_e( 'Any content added below is displayed on the quiz page', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.materialsEnabled" x-cloak>
				<div class="mint-cs-materials mint-cs-materials--settings">
					<div class="mint-cs-materials__actions">
						<button type="button" class="mint-cs-materials__add-media" @click="addMediaToQuizMaterials()">
							<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="3.5" width="15" height="13" rx="3"/><circle cx="7" cy="8" r="1.4"/><path d="m4 14 4-3.5 3 2 4.5-4"/></svg>
							<?php esc_html_e( 'Add media', 'mint-lms' ); ?>
						</button>
						<div class="mint-cs-materials__view-toggle" role="group" aria-label="<?php echo esc_attr__( 'Editor mode', 'mint-lms' ); ?>">
							<button
								type="button"
								class="mint-cs-materials__view-btn"
								:class="quizMaterialsEditorView === 'visual' ? 'is-active' : ''"
								@click="setQuizMaterialsEditorView('visual')"
							><?php esc_html_e( 'Visual', 'mint-lms' ); ?></button>
							<button
								type="button"
								class="mint-cs-materials__view-btn"
								:class="quizMaterialsEditorView === 'code' ? 'is-active' : ''"
								@click="setQuizMaterialsEditorView('code')"
							><?php esc_html_e( 'Code', 'mint-lms' ); ?></button>
						</div>
					</div>
					<div
						class="mint-cs-materials__frame"
						:class="quizMaterialsEditorView === 'code' ? 'is-code' : 'is-visual'"
					>
						<div
							class="mint-cs-materials__toolbar"
							x-show="quizMaterialsEditorView === 'visual'"
							@click.outside="quizMaterialsFormatMenuOpen = false"
							@mousedown.prevent
						>
							<div class="mint-cs-materials__format">
								<button
									type="button"
									class="mint-cs-materials__format-trigger"
									@click="quizMaterialsFormatMenuOpen = !quizMaterialsFormatMenuOpen"
									:aria-expanded="quizMaterialsFormatMenuOpen ? 'true' : 'false'"
								>
									<span x-text="quizMaterialsFormatLabel"></span>
									<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
								</button>
								<div class="mint-cs-materials__format-menu" x-show="quizMaterialsFormatMenuOpen">
									<template x-for="option in quizMaterialsFormatOptions" :key="'qm-fmt-' + option.value">
										<button
											type="button"
											class="mint-cs-materials__format-option"
											:class="quizMaterialsFormatLabel === option.label ? 'is-active' : ''"
											@click="applyQuizMaterialsFormat(option)"
											x-text="option.label"
										></button>
									</template>
								</div>
							</div>
							<span class="mint-cs-materials__toolbar-divider" aria-hidden="true"></span>
							<button type="button" class="mint-cs-materials__tool" style="font-weight:700" @click="runQuizMaterialsCommand('bold')" title="<?php echo esc_attr__( 'Bold', 'mint-lms' ); ?>">B</button>
							<button type="button" class="mint-cs-materials__tool" style="font-weight:500;font-style:italic" @click="runQuizMaterialsCommand('italic')" title="<?php echo esc_attr__( 'Italic', 'mint-lms' ); ?>">I</button>
							<button type="button" class="mint-cs-materials__tool" @click="runQuizMaterialsCommand('InsertUnorderedList')" title="<?php echo esc_attr__( 'Bullet list', 'mint-lms' ); ?>">•</button>
							<button type="button" class="mint-cs-materials__tool" @click="runQuizMaterialsCommand('InsertOrderedList')" title="<?php echo esc_attr__( 'Numbered list', 'mint-lms' ); ?>">1.</button>
							<button type="button" class="mint-cs-materials__tool" @click="insertQuizMaterialsLink()" title="<?php echo esc_attr__( 'Link', 'mint-lms' ); ?>">🔗</button>
						</div>
						<div
							class="mint-cs-materials__toolbar mint-cs-materials__toolbar--code"
							x-show="quizMaterialsEditorView === 'code'"
							@mousedown.prevent
						>
							<template x-for="tag in quizMaterialsCodeTags" :key="'qm-code-' + tag">
								<button type="button" class="mint-cs-materials__code-chip" @click="insertQuizMaterialsCodeTag(tag)" x-text="tag"></button>
							</template>
						</div>
						<div
							id="mint_quiz_materials_visual"
							class="mint-cs-materials__visual"
							contenteditable="true"
							role="textbox"
							aria-multiline="true"
							x-show="quizMaterialsEditorView === 'visual'"
							@input="onQuizMaterialsVisualInput()"
							@blur="onQuizMaterialsVisualInput()"
							data-placeholder="<?php echo esc_attr__( 'Add notes, links or downloads for the quiz page…', 'mint-lms' ); ?>"
						></div>
						<textarea
							id="mint_quiz_materials_code"
							class="mint-cs-materials__code"
							rows="8"
							x-show="quizMaterialsEditorView === 'code'"
							@input="onQuizMaterialsCodeInput($event)"
							placeholder="<?php echo esc_attr__( 'Add notes, links or downloads for the quiz page…', 'mint-lms' ); ?>"
						></textarea>
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Autostart', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<button type="button" class="mint-settings-check" @click="lessonQuiz.settings.autostart = !lessonQuiz.settings.autostart">
						<span class="mint-settings-check__box" :class="lessonQuiz.settings.autostart ? 'is-checked' : ''" aria-hidden="true">
							<svg x-show="lessonQuiz.settings.autostart" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
						</span>
						<span class="mint-settings-check__label"><?php esc_html_e( 'Start automatically, without the “Start Quiz” button', 'mint-lms' ); ?></span>
					</button>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row mint-settings-row--top">
				<div class="mint-settings-row__label mint-settings-row__label--pad"><?php esc_html_e( 'Question display', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control mint-settings-row__control--col">
					<div class="mint-settings-select" :class="settingsDropdowns.disp ? 'is-open' : ''" @click.outside="settingsDropdowns.disp = false">
						<button type="button" class="mint-settings-select__trigger" @click="settingsDropdowns.disp = !settingsDropdowns.disp">
							<span x-text="quizQuestionDisplayLabel()"></span>
							<svg class="mint-settings-select__chevron" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
						</button>
						<div class="mint-settings-select__menu" x-show="settingsDropdowns.disp" x-cloak>
							<button type="button" class="mint-settings-select__option" :class="lessonQuiz.settings.questionDisplay === 'one' ? 'is-active' : ''" @click="lessonQuiz.settings.questionDisplay = 'one'; settingsDropdowns.disp = false"><?php esc_html_e( 'One question at a time', 'mint-lms' ); ?></button>
							<button type="button" class="mint-settings-select__option" :class="lessonQuiz.settings.questionDisplay === 'all' ? 'is-active' : ''" @click="lessonQuiz.settings.questionDisplay = 'all'; settingsDropdowns.disp = false"><?php esc_html_e( 'All questions at once', 'mint-lms' ); ?></button>
						</div>
					</div>
					<button type="button" class="mint-settings-radio-inline" :class="lessonQuiz.settings.resultsMode === 'end' ? 'is-selected' : ''" @click="lessonQuiz.settings.resultsMode = 'end'">
						<span class="mint-settings-radio-inline__dot" aria-hidden="true"><span class="mint-settings-radio-inline__dot-inner"></span></span>
						<span class="mint-settings-radio-inline__label"><?php esc_html_e( 'Display results at the end only', 'mint-lms' ); ?></span>
					</button>
					<div class="mint-settings-nested--indent" x-show="lessonQuiz.settings.resultsMode === 'end'" x-cloak>
						<button type="button" class="mint-settings-check" @click="lessonQuiz.settings.displayBackButton = !lessonQuiz.settings.displayBackButton">
							<span class="mint-settings-check__box" :class="lessonQuiz.settings.displayBackButton ? 'is-checked' : ''" aria-hidden="true">
								<svg x-show="lessonQuiz.settings.displayBackButton" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
							</span>
							<span class="mint-settings-check__label"><?php esc_html_e( 'Display Back button', 'mint-lms' ); ?></span>
						</button>
					</div>
					<button type="button" class="mint-settings-radio-inline" :class="lessonQuiz.settings.resultsMode === 'each' ? 'is-selected' : ''" @click="lessonQuiz.settings.resultsMode = 'each'">
						<span class="mint-settings-radio-inline__dot" aria-hidden="true"><span class="mint-settings-radio-inline__dot-inner"></span></span>
						<span class="mint-settings-radio-inline__label"><?php esc_html_e( 'Display results after each submitted answer', 'mint-lms' ); ?></span>
					</button>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Question overview table', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.questionOverview ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.questionOverview ? 'true' : 'false'" @click="lessonQuiz.settings.questionOverview = !lessonQuiz.settings.questionOverview"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.questionOverview" x-cloak><?php esc_html_e( 'An overview table will be shown for all questions', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.questionOverview" x-cloak>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Quiz summary', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
						<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.quizSummary ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.quizSummary ? 'true' : 'false'" @click="lessonQuiz.settings.quizSummary = !lessonQuiz.settings.quizSummary"><span class="mint-lesson-preview-toggle__knob"></span></button>
						<span class="mint-settings-hint-text"><?php esc_html_e( 'Show a summary table before submitting', 'mint-lms' ); ?></span>
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Skip question', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Students can skip a question and come back', 'mint-lms' ); ?>">?</span>
						<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.skipQuestion ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.skipQuestion ? 'true' : 'false'" @click="lessonQuiz.settings.skipQuestion = !lessonQuiz.settings.skipQuestion"><span class="mint-lesson-preview-toggle__knob"></span></button>
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Custom question ordering', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.customOrdering ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.customOrdering ? 'true' : 'false'" @click="lessonQuiz.settings.customOrdering = !lessonQuiz.settings.customOrdering"><span class="mint-lesson-preview-toggle__knob"></span></button>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.customOrdering" x-cloak>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Sort by category', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<button type="button" class="mint-settings-check" @click="lessonQuiz.settings.sortByCategory = !lessonQuiz.settings.sortByCategory">
							<span class="mint-settings-check__box" :class="lessonQuiz.settings.sortByCategory ? 'is-checked' : ''" aria-hidden="true">
								<svg x-show="lessonQuiz.settings.sortByCategory" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
							</span>
						</button>
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Randomize order', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.randomizeOrder ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.randomizeOrder ? 'true' : 'false'" @click="lessonQuiz.settings.randomizeOrder = !lessonQuiz.settings.randomizeOrder"><span class="mint-lesson-preview-toggle__knob"></span></button>
					</div>
				</div>
				<div x-show="lessonQuiz.settings.randomizeOrder" x-cloak style="padding-left:30px;display:grid;gap:10px">
					<button type="button" class="mint-settings-radio-inline" :class="lessonQuiz.settings.randomizeMode === 'all' ? 'is-selected' : ''" @click="lessonQuiz.settings.randomizeMode = 'all'">
						<span class="mint-settings-radio-inline__dot" aria-hidden="true"><span class="mint-settings-radio-inline__dot-inner"></span></span>
						<span class="mint-settings-radio-inline__label"><?php esc_html_e( 'Show all questions', 'mint-lms' ); ?></span>
					</button>
					<button type="button" class="mint-settings-radio-inline" :class="lessonQuiz.settings.randomizeMode === 'subset' ? 'is-selected' : ''" @click="lessonQuiz.settings.randomizeMode = 'subset'">
						<span class="mint-settings-radio-inline__dot" aria-hidden="true"><span class="mint-settings-radio-inline__dot-inner"></span></span>
						<span class="mint-settings-radio-inline__label"><?php esc_html_e( 'Show only some questions', 'mint-lms' ); ?></span>
					</button>
					<div x-show="lessonQuiz.settings.randomizeMode === 'subset'" x-cloak style="display:flex;align-items:center;gap:10px">
						<input type="number" min="0" class="mint-settings-input" style="width:72px" x-model.number="lessonQuiz.settings.randomizeCount" />
						<span class="mint-settings-hint-text" x-text="t('outOf') + ' ' + (lessonQuiz.questions?.length || 0) + ' ' + t('questions')"></span>
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Custom question elements', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Choose what students see with each question', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.customElements ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.customElements ? 'true' : 'false'" @click="lessonQuiz.settings.customElements = !lessonQuiz.settings.customElements"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.customElements" x-cloak><?php esc_html_e( 'Pick what shows with each question', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.customElements" x-cloak>
				<template x-for="el in quizCustomElementToggles()" :key="el.key">
					<div class="mint-settings-subrow">
						<div class="mint-settings-subrow__label" x-text="el.label"></div>
						<div class="mint-settings-row__control">
							<span class="mint-settings-hint" x-show="el.hint" :title="el.hint || ''">?</span>
							<span class="mint-settings-hint mint-settings-hint--spacer" x-show="!el.hint" aria-hidden="true">?</span>
							<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings[el.key] ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings[el.key] ? 'true' : 'false'" @click="lessonQuiz.settings[el.key] = !lessonQuiz.settings[el.key]"><span class="mint-lesson-preview-toggle__knob"></span></button>
						</div>
					</div>
				</template>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Quiz title', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Show the quiz title on the quiz page', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.showQuizTitle ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.showQuizTitle ? 'true' : 'false'" @click="lessonQuiz.settings.showQuizTitle = !lessonQuiz.settings.showQuizTitle"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.showQuizTitle" x-cloak><?php esc_html_e( 'The quiz title is shown on the quiz page as well. Best for quizzes added with a shortcode', 'mint-lms' ); ?></span>
				</div>
			</div>

		</div>
	</div>

	<div class="mint-settings-card">
		<button type="button" class="mint-settings-card__head" :class="settingsAccordion.quizResults ? 'is-open' : ''" @click="settingsAccordion.quizResults = !settingsAccordion.quizResults" :aria-expanded="settingsAccordion.quizResults ? 'true' : 'false'">
			<div>
				<div class="mint-settings-card__head-title"><?php esc_html_e( 'Results page display', 'mint-lms' ); ?></div>
				<div class="mint-settings-card__head-copy"><?php esc_html_e( 'Controls how the results page will look', 'mint-lms' ); ?></div>
			</div>
			<svg class="mint-settings-card__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
		</button>
		<div class="mint-settings-card__body" x-show="settingsAccordion.quizResults" x-cloak>

			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Result message(s)', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Custom messages shown after the quiz', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.resultMessagesEnabled ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.resultMessagesEnabled ? 'true' : 'false'" @click="toggleQuizResultMessages()"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.resultMessagesEnabled" x-cloak><?php esc_html_e( 'The message below is shown on the quiz results page', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested mint-settings-nested--stack" x-show="lessonQuiz.settings.resultMessagesEnabled" x-cloak>
				<template x-for="(rb, ri) in lessonQuiz.settings.resultMessages" :key="'rm-' + ri">
					<div class="mint-settings-grad" :class="rb.open === false ? 'is-collapsed' : ''">
						<div
							class="mint-settings-grad__head"
							role="button"
							tabindex="0"
							@click="rb.open = rb.open === false"
							@keydown.enter.prevent="rb.open = rb.open === false"
							@keydown.space.prevent="rb.open = rb.open === false"
						>
							<div class="mint-settings-grad__meta">
								<span class="mint-settings-grad__meta-label"><?php esc_html_e( 'From', 'mint-lms' ); ?></span>
								<input
									type="number"
									min="0"
									max="100"
									class="mint-settings-input mint-settings-input--grad"
									x-model.number="rb.from"
									@click.stop
								/>
								<span><?php esc_html_e( '% score, show this message:', 'mint-lms' ); ?></span>
							</div>
							<div class="mint-settings-grad__actions">
								<button
									type="button"
									class="mint-settings-delete-grad"
									x-show="lessonQuiz.settings.resultMessages.length > 1"
									@click.stop="removeQuizResultMessage(ri)"
								>
									<svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="8.5" fill="#C22B2B"/><path d="m7 7 6 6M13 7l-6 6" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"/></svg>
									<?php esc_html_e( 'Delete graduation', 'mint-lms' ); ?>
								</button>
								<svg
									class="mint-settings-grad__chevron"
									width="16"
									height="16"
									viewBox="0 0 20 20"
									fill="none"
									stroke="#0B4F3F"
									stroke-width="2.4"
									stroke-linecap="round"
									stroke-linejoin="round"
									:class="rb.open === false ? '' : 'is-open'"
									aria-hidden="true"
								><path d="m5 8 5 5 5-5"/></svg>
							</div>
						</div>
						<div class="mint-settings-grad__body">
							<div class="mint-settings-rte">
								<div class="mint-settings-rte__top">
									<button
										type="button"
										class="mint-settings-rte__media"
										@click="addMediaToResultMessage(ri)"
									>
										<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="3.5" width="15" height="13" rx="3"/><circle cx="7" cy="8" r="1.4"/><path d="m4 14 4-3.5 3 2 4.5-4"/></svg>
										<?php esc_html_e( 'Add media', 'mint-lms' ); ?>
									</button>
									<div class="mint-settings-rte__views" role="group" aria-label="<?php echo esc_attr__( 'Editor mode', 'mint-lms' ); ?>">
										<button
											type="button"
											class="mint-settings-rte__view"
											:class="(rb.editorView || 'visual') === 'visual' ? 'is-active' : ''"
											@click="rb.editorView = 'visual'"
										><?php esc_html_e( 'Visual', 'mint-lms' ); ?></button>
										<button
											type="button"
											class="mint-settings-rte__view"
											:class="rb.editorView === 'code' ? 'is-active' : ''"
											@click="rb.editorView = 'code'"
										><?php esc_html_e( 'Code', 'mint-lms' ); ?></button>
									</div>
								</div>
								<div class="mint-settings-editor">
									<div class="mint-settings-editor__toolbar mint-settings-editor__toolbar--tags">
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'b')">b</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'i')">i</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'link')">link</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'b-quote')">b-quote</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'del')">del</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'ins')">ins</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'img')">img</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'ul')">ul</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'ol')">ol</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'li')">li</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'code')">code</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'more')">more</button>
										<button type="button" class="mint-settings-editor__chip" @click="insertResultMessageCodeTag(ri, 'close tags')">close tags</button>
									</div>
									<textarea
										:id="'mint-result-msg-' + ri"
										class="mint-settings-editor__textarea"
										x-model="rb.message"
										placeholder="<?php echo esc_attr__( 'Write the message students see for this score…', 'mint-lms' ); ?>"
									></textarea>
								</div>
							</div>
						</div>
					</div>
				</template>
				<button type="button" class="mint-settings-add-grad" @click="addQuizResultMessage()">
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
					<?php esc_html_e( 'Add graduation', 'mint-lms' ); ?>
				</button>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Restart quiz button', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.restartButton ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.restartButton ? 'true' : 'false'" @click="lessonQuiz.settings.restartButton = !lessonQuiz.settings.restartButton"><span class="mint-lesson-preview-toggle__knob"></span></button>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Custom results display', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.customResults ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.customResults ? 'true' : 'false'" @click="lessonQuiz.settings.customResults = !lessonQuiz.settings.customResults"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.customResults" x-cloak><?php esc_html_e( 'Choose what to show on the results page', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.customResults" x-cloak>
				<template x-for="el in quizCustomResultsToggles()" :key="'cr-' + el.key">
					<div class="mint-settings-subrow">
						<div class="mint-settings-subrow__label" x-text="el.label"></div>
						<div class="mint-settings-row__control">
							<span class="mint-settings-hint" x-show="el.hint" :title="el.hint || ''">?</span>
							<span class="mint-settings-hint mint-settings-hint--spacer" x-show="!el.hint" aria-hidden="true">?</span>
							<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings[el.key] ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings[el.key] ? 'true' : 'false'" @click="lessonQuiz.settings[el.key] = !lessonQuiz.settings[el.key]"><span class="mint-lesson-preview-toggle__knob"></span></button>
						</div>
					</div>
				</template>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Custom answer feedback', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Choose what feedback students get on each answer', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.customAnswerFeedback ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.customAnswerFeedback ? 'true' : 'false'" @click="lessonQuiz.settings.customAnswerFeedback = !lessonQuiz.settings.customAnswerFeedback"><span class="mint-lesson-preview-toggle__knob"></span></button>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.customAnswerFeedback" x-cloak>
				<template x-for="el in quizAnswerFeedbackToggles()" :key="'caf-' + el.key">
					<div class="mint-settings-subrow">
						<div class="mint-settings-subrow__label" x-text="el.label"></div>
						<div class="mint-settings-row__control">
							<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
							<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings[el.key] ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings[el.key] ? 'true' : 'false'" @click="lessonQuiz.settings[el.key] = !lessonQuiz.settings[el.key]"><span class="mint-lesson-preview-toggle__knob"></span></button>
						</div>
					</div>
				</template>
			</div>

		</div>
	</div>

	<div class="mint-settings-footer">
		<button type="button" class="mint-settings-save" @click="saveQuiz()" :disabled="quizSaving"><?php esc_html_e( 'Save quiz', 'mint-lms' ); ?></button>
	</div>
</div>
