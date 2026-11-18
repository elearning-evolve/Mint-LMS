<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$createUrl = $createUrl ?? \MintLMS\Infrastructure\Admin\CourseBuilderPage::titledCourseUrl();
$blankUrl  = $blankUrl ?? \MintLMS\Infrastructure\Admin\CourseBuilderPage::newCourseUrl();
?>
<div
	class="mint-dash-zero"
	x-data="mintDashEmpty()"
>
	<div class="mint-dash-zero__wrap">
		<section class="mint-dash-zero__start">
			<span class="mint-dash-zero__eyebrow">
				<span class="mint-dash-zero__eyebrow-dot" aria-hidden="true"></span>
				<?php esc_html_e( 'Get started', 'mint-lms' ); ?>
			</span>

			<h1 class="mint-dash-zero__title"><?php esc_html_e( 'Let’s build your first course.', 'mint-lms' ); ?></h1>
			<p class="mint-dash-zero__lead">
				<?php esc_html_e( 'Create a course, attach lessons and quizzes, then open it for students when you’re ready.', 'mint-lms' ); ?>
			</p>

			<div class="mint-dash-zero__actions">
				<a href="<?php echo esc_url( $createUrl ); ?>" class="mint-dash-zero__cta">
					<?php esc_html_e( 'Create your first course', 'mint-lms' ); ?>
				</a>
				<a href="<?php echo esc_url( $blankUrl ); ?>" class="mint-dash-zero__ghost">
					<?php esc_html_e( 'Start blank', 'mint-lms' ); ?>
				</a>
				<button type="button" class="mint-dash-zero__link-struct" @click="seeStructure()">
					<?php esc_html_e( 'See course structure', 'mint-lms' ); ?>
				</button>
			</div>

			<div class="mint-dash-zero__steps" aria-label="<?php esc_attr_e( 'How Mint courses are built', 'mint-lms' ); ?>">
				<div class="mint-dash-zero__step">
					<div class="mint-dash-zero__step-n">1</div>
					<div class="mint-dash-zero__step-t"><?php esc_html_e( 'Create freely', 'mint-lms' ); ?></div>
					<div class="mint-dash-zero__step-s"><?php esc_html_e( 'Lessons, quizzes and questions, in any order', 'mint-lms' ); ?></div>
				</div>
				<div class="mint-dash-zero__step">
					<div class="mint-dash-zero__step-n">2</div>
					<div class="mint-dash-zero__step-t"><?php esc_html_e( 'Attach each piece', 'mint-lms' ); ?></div>
					<div class="mint-dash-zero__step-s"><?php esc_html_e( 'Place it inside the course structure', 'mint-lms' ); ?></div>
				</div>
				<div class="mint-dash-zero__step">
					<div class="mint-dash-zero__step-n">3</div>
					<div class="mint-dash-zero__step-t"><?php esc_html_e( 'Open for students', 'mint-lms' ); ?></div>
					<div class="mint-dash-zero__step-s"><?php esc_html_e( 'Publish when the course is ready', 'mint-lms' ); ?></div>
				</div>
			</div>
		</section>

		<section
			class="mint-dash-zero__panel"
			id="mint-dash-structure"
			x-ref="structure"
			:class="{ 'is-flash': flash }"
			aria-label="<?php esc_attr_e( 'Course building blocks', 'mint-lms' ); ?>"
		>
			<div class="mint-dash-zero__panel-t"><?php esc_html_e( 'Build order', 'mint-lms' ); ?></div>
			<div class="mint-dash-zero__panel-s">
				<?php esc_html_e( 'Build each piece in order. Each one attaches to the one before it.', 'mint-lms' ); ?>
			</div>

			<div class="mint-dash-zero__canvas">
				<div class="mint-dash-zero__course-bar">
					<?php esc_html_e( 'Your course', 'mint-lms' ); ?>
					<span class="mint-dash-zero__course-sub"><?php esc_html_e( 'Draft', 'mint-lms' ); ?></span>
				</div>
				<div class="mint-dash-zero__grid4">
					<div class="mint-dash-zero__slot is-first" tabindex="0">
						<div class="mint-dash-zero__slot-no">1</div>
						<div class="mint-dash-zero__slot-t"><?php esc_html_e( 'Lesson Group', 'mint-lms' ); ?></div>
						<div class="mint-dash-zero__slot-add" aria-hidden="true">
							<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.6" stroke-linecap="round"><path d="M10 4v12M4 10h12" /></svg>
						</div>
					</div>
					<div class="mint-dash-zero__slot" tabindex="0">
						<div class="mint-dash-zero__slot-no">2</div>
						<div class="mint-dash-zero__slot-t"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></div>
						<div class="mint-dash-zero__slot-add" aria-hidden="true">
							<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.6" stroke-linecap="round"><path d="M10 4v12M4 10h12" /></svg>
						</div>
					</div>
					<div class="mint-dash-zero__slot" tabindex="0">
						<div class="mint-dash-zero__slot-no">3</div>
						<div class="mint-dash-zero__slot-t"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></div>
						<div class="mint-dash-zero__slot-add" aria-hidden="true">
							<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.6" stroke-linecap="round"><path d="M10 4v12M4 10h12" /></svg>
						</div>
					</div>
					<div class="mint-dash-zero__slot" tabindex="0">
						<div class="mint-dash-zero__slot-no">4</div>
						<div class="mint-dash-zero__slot-t"><?php esc_html_e( 'Question', 'mint-lms' ); ?></div>
						<div class="mint-dash-zero__slot-add" aria-hidden="true">
							<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.6" stroke-linecap="round"><path d="M10 4v12M4 10h12" /></svg>
						</div>
					</div>
				</div>
			</div>

			<div class="mint-dash-zero__order">
				<div class="mint-dash-zero__order-t"><?php esc_html_e( 'Build order', 'mint-lms' ); ?></div>
				<div class="mint-dash-zero__order-hint">
					<?php esc_html_e( 'Click the arrow on each step to open the next one.', 'mint-lms' ); ?>
				</div>
				<div class="mint-dash-zero__bo" role="list">
					<div class="mint-dash-zero__bo-step is-lvl-1" role="listitem">
						<button
							type="button"
							class="mint-dash-zero__bo-row"
							:class="{ 'is-open': isBoOpen(1) }"
							@click="toggleBo(1)"
							:aria-expanded="isBoOpen(1) ? 'true' : 'false'"
						>
							<span class="mint-dash-zero__bo-num">1</span>
							<?php esc_html_e( 'Group', 'mint-lms' ); ?>
							<span class="mint-dash-zero__bo-go" aria-hidden="true">
								<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m8 4.5 6 5.5-6 5.5" /></svg>
							</span>
						</button>
					</div>
					<div
						class="mint-dash-zero__bo-step is-lvl-2"
						role="listitem"
						x-show="isBoVisible(2)"
						x-transition.opacity.duration.200ms
						x-cloak
					>
						<button
							type="button"
							class="mint-dash-zero__bo-row"
							:class="{ 'is-open': isBoOpen(2) }"
							@click="toggleBo(2)"
							:aria-expanded="isBoOpen(2) ? 'true' : 'false'"
						>
							<span class="mint-dash-zero__bo-num">2</span>
							<?php esc_html_e( 'Lesson', 'mint-lms' ); ?>
							<span class="mint-dash-zero__bo-go" aria-hidden="true">
								<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m8 4.5 6 5.5-6 5.5" /></svg>
							</span>
						</button>
					</div>
					<div
						class="mint-dash-zero__bo-step is-lvl-3"
						role="listitem"
						x-show="isBoVisible(3)"
						x-transition.opacity.duration.200ms
						x-cloak
					>
						<button
							type="button"
							class="mint-dash-zero__bo-row"
							:class="{ 'is-open': isBoOpen(3) }"
							@click="toggleBo(3)"
							:aria-expanded="isBoOpen(3) ? 'true' : 'false'"
						>
							<span class="mint-dash-zero__bo-num">3</span>
							<?php esc_html_e( 'Quiz', 'mint-lms' ); ?>
							<span class="mint-dash-zero__bo-go" aria-hidden="true">
								<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m8 4.5 6 5.5-6 5.5" /></svg>
							</span>
						</button>
					</div>
					<div
						class="mint-dash-zero__bo-step is-lvl-4"
						role="listitem"
						x-show="isBoVisible(4)"
						x-transition.opacity.duration.200ms
						x-cloak
					>
						<button type="button" class="mint-dash-zero__bo-row" disabled>
							<span class="mint-dash-zero__bo-num">4</span>
							<?php esc_html_e( 'Question', 'mint-lms' ); ?>
							<span class="mint-dash-zero__bo-end"><?php esc_html_e( 'Last step', 'mint-lms' ); ?></span>
						</button>
					</div>
				</div>
			</div>
		</section>
	</div>
</div>
