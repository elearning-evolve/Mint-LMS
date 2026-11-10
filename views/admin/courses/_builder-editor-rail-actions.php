<?php
/**
 * Lesson / Quiz / Question actions above the attach rail.
 *
 * @package MintLMS
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mint-editor-rail__actions">
	<button
		type="button"
		class="mint-quiz-settings-btn"
		x-show="selected?.type === 'lesson' && selectedLesson?.id"
		x-cloak
		@click="openLessonSettings()"
		:disabled="!selectedLesson?.id"
	>
		<img
			class="mint-quiz-settings-btn__icon"
			src="<?php echo esc_url( MINTLMS_URL . 'assets/img/quiz-settings-icon.png' ); ?>"
			width="18"
			height="18"
			alt=""
		/>
		<?php esc_html_e( 'Lesson Settings', 'mint-lms' ); ?>
	</button>
	<button
		type="button"
		class="mint-quiz-settings-btn"
		x-show="selected?.type === 'quiz' && lessonQuiz?.id"
		x-cloak
		@click="openQuizSettings()"
		:disabled="!lessonQuiz?.id"
	>
		<img
			class="mint-quiz-settings-btn__icon"
			src="<?php echo esc_url( MINTLMS_URL . 'assets/img/quiz-settings-icon.png' ); ?>"
			width="18"
			height="18"
			alt=""
		/>
		<?php esc_html_e( 'Quiz Settings', 'mint-lms' ); ?>
	</button>
	<button
		type="button"
		class="mint-builder-btn-save"
		@click="headerSaveAction()"
		:disabled="headerSaveDisabled()"
	>
		<span x-show="saveStatus !== 'saving' && !quizSaving"><?php esc_html_e( 'Save changes', 'mint-lms' ); ?></span>
		<span x-show="saveStatus === 'saving' || quizSaving" x-cloak><?php esc_html_e( 'Saving…', 'mint-lms' ); ?></span>
	</button>
	<button
		type="button"
		class="mint-builder-delete mint-builder-delete--header"
		x-show="showHeaderDeleteButton()"
		x-cloak
		@click="headerDeleteAction()"
	>
		<svg width="15" height="15" viewBox="0 0 20 20" fill="none" aria-hidden="true">
			<path d="M4.5 6h11M8 6V4.5A1.5 1.5 0 0 1 9.5 3h1A1.5 1.5 0 0 1 12 4.5V6M6.5 6v9.5A1.5 1.5 0 0 0 8 17h4a1.5 1.5 0 0 0 1.5-1.5V6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
		<span x-text="headerDeleteLabel()"><?php esc_html_e( 'Delete', 'mint-lms' ); ?></span>
	</button>
</div>
