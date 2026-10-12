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
	<div class="mint-settings-page__subtitle" x-text="lessonQuiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></div>

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
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.materialsEnabled ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.materialsEnabled ? 'true' : 'false'" @click="lessonQuiz.settings.materialsEnabled = !lessonQuiz.settings.materialsEnabled"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.materialsEnabled" x-cloak><?php esc_html_e( 'Any content added below is displayed on the quiz page', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.materialsEnabled" x-cloak>
				<div class="mint-settings-editor">
					<div class="mint-settings-editor__toolbar">
						<div class="mint-settings-editor__tools"><span class="mint-settings-editor__chip"><?php esc_html_e( 'Paragraph', 'mint-lms' ); ?></span></div>
						<button type="button" class="mint-settings-editor__media"><?php esc_html_e( 'Add media', 'mint-lms' ); ?></button>
					</div>
					<textarea class="mint-settings-editor__textarea" x-model="lessonQuiz.settings.materialsContent" placeholder="<?php echo esc_attr__( 'Add notes, links or downloads for the quiz page…', 'mint-lms' ); ?>"></textarea>
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
						<span class="mint-settings-hint-text" x-text="'<?php echo esc_js( __( 'out of', 'mint-lms' ) ); ?> ' + (lessonQuiz.questions?.length || 0) + ' <?php echo esc_js( __( 'questions', 'mint-lms' ) ); ?>'"></span>
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
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="lessonQuiz.settings.resultMessagesEnabled ? 'is-on' : ''" :aria-pressed="lessonQuiz.settings.resultMessagesEnabled ? 'true' : 'false'" @click="lessonQuiz.settings.resultMessagesEnabled = !lessonQuiz.settings.resultMessagesEnabled"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="lessonQuiz.settings.resultMessagesEnabled" x-cloak><?php esc_html_e( 'The message below is shown on the quiz results page', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="lessonQuiz.settings.resultMessagesEnabled" x-cloak style="gap:14px">
				<template x-for="(rb, ri) in lessonQuiz.settings.resultMessages" :key="'rm-' + ri">
					<div class="mint-settings-grad">
						<button type="button" class="mint-settings-grad__head" @click="rb.open = !rb.open">
							<div class="mint-settings-grad__meta">
								<span style="font-weight:600"><?php esc_html_e( 'From', 'mint-lms' ); ?></span>
								<input type="number" class="mint-settings-input" style="width:64px;height:36px;text-align:center" x-model.number="rb.from" @click.stop />
								<span><?php esc_html_e( '% score, show this message:', 'mint-lms' ); ?></span>
							</div>
							<div style="display:flex;align-items:center;gap:12px">
								<button type="button" class="mint-settings-delete-grad" x-show="lessonQuiz.settings.resultMessages.length > 1" x-cloak @click.stop="removeQuizResultMessage(ri)">
									<svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="8.5" fill="#C22B2B"/><path d="m7 7 6 6M13 7l-6 6" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"/></svg>
									<?php esc_html_e( 'Delete graduation', 'mint-lms' ); ?>
								</button>
								<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" :style="rb.open ? 'transform:rotate(180deg)' : ''" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
							</div>
						</button>
						<div class="mint-settings-grad__body" x-show="rb.open" x-cloak>
							<div class="mint-settings-editor">
								<div class="mint-settings-editor__toolbar">
									<div class="mint-settings-editor__tools"><span class="mint-settings-editor__chip"><?php esc_html_e( 'Paragraph', 'mint-lms' ); ?></span></div>
									<button type="button" class="mint-settings-editor__media"><?php esc_html_e( 'Add media', 'mint-lms' ); ?></button>
								</div>
								<textarea class="mint-settings-editor__textarea" x-model="rb.message" placeholder="<?php echo esc_attr__( 'Write the message students see for this score…', 'mint-lms' ); ?>"></textarea>
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
