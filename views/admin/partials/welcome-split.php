<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\FirstRunRedirect;
use MintLMS\Infrastructure\Capability\ContentOwnership;
use MintLMS\Infrastructure\Capability\Roles;

/**
 * Shared first-run welcome for Admin, Manager, and Instructor.
 *
 * Step 1 (Structure) is shared for every role.
 * Admin:      1 Structure → 2 Create/Attach → 3 Publish + students learn
 * Manager:    1 Structure → 2 Manage flow (no step 3)
 * Instructor: 1 Structure → 2 Teach flow (no step 3)
 */

$createUrl = $createUrl ?? FirstRunRedirect::startGuidedUrl();
$skipUrl   = $skipUrl ?? FirstRunRedirect::skipFirstRunUrl();
$showSkip  = $showSkip ?? true;
$wpVersion = get_bloginfo( 'version' );

$current_roles = is_user_logged_in() ? (array) wp_get_current_user()->roles : array();

// Instructors (own content only) get a 2-step classroom welcome.
$is_instructor_welcome = ContentOwnership::currentUserMustOwnContent();
// Mint LMS Manager (not WP Administrator) gets a 2-step manage welcome — same pattern as Instructor.
$is_manager_welcome    = ! $is_instructor_welcome
	&& in_array( Roles::MANAGER, $current_roles, true )
	&& ! in_array( Roles::ADMINISTRATOR, $current_roles, true );
$is_two_step_welcome   = $is_instructor_welcome || $is_manager_welcome;
$max_steps             = $is_two_step_welcome ? 2 : 3;

$welcome_mod_class = '';
if ( $is_instructor_welcome ) {
	$welcome_mod_class = ' mint-welcome--instructor';
} elseif ( $is_manager_welcome ) {
	$welcome_mod_class = ' mint-welcome--manager';
}

$features_step1 = array(
	array(
		'label' => __( 'Add lessons as text or video', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 5h12M4 9h12M4 13h7"/></svg>',
	),
	array(
		'label' => __( 'Students see their progress as they go', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 14l4-4 3 3 7-7"/></svg>',
	),
	array(
		'label' => __( 'Works with the theme you already have', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M10 3l6 3.5v7L10 17l-6-3.5v-7z"/></svg>',
	),
);

$features_step2_admin = array(
	array(
		'label' => __( 'Create lessons, quizzes, and questions freely', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>',
	),
	array(
		'label' => __( 'Attach each piece to its parent', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 7H5.5A2.5 2.5 0 003 9.5v1A2.5 2.5 0 005.5 13H8M12 7h2.5A2.5 2.5 0 0117 9.5v1A2.5 2.5 0 0114.5 13H12M7 10h6"/></svg>',
	),
	array(
		'label' => __( 'Detach anytime without losing your work', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 10h5M11 10h5M9 7l-3 3 3 3M11 7l3 3-3 3"/></svg>',
	),
);

$features_step2_manager = array(
	array(
		'label' => __( 'Create and attach content across every course on the site', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>',
	),
	array(
		'label' => __( 'Enroll students and open courses when they are ready', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 10a3 3 0 100-6 3 3 0 000 6z"/><path d="M4 16a6 6 0 0112 0"/></svg>',
	),
	array(
		'label' => __( 'Follow reports and progress across the whole LMS', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 14l4-4 3 3 7-7"/></svg>',
	),
);

$features_step2_instructor = array(
	array(
		'label' => __( 'Create freely — lesson, quiz, or question first; finish later', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>',
	),
	array(
		'label' => __( 'Attach where it belongs — Lesson → Lesson Group, Quiz → Lesson, Question → Quiz', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 7H5.5A2.5 2.5 0 003 9.5v1A2.5 2.5 0 005.5 13H8M12 7h2.5A2.5 2.5 0 0117 9.5v1A2.5 2.5 0 0114.5 13H12M7 10h6"/></svg>',
	),
	array(
		'label' => __( 'Your students, your progress — enroll and follow reports on your own courses', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 10a3 3 0 100-6 3 3 0 000 6z"/><path d="M4 16a6 6 0 0112 0"/></svg>',
	),
);

if ( $is_instructor_welcome ) {
	$features_step2 = $features_step2_instructor;
} elseif ( $is_manager_welcome ) {
	$features_step2 = $features_step2_manager;
} else {
	$features_step2 = $features_step2_admin;
}

$features_step3 = array(
	array(
		'label' => __( 'Publish when ready — or keep a draft', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 10l3 3 7-7"/></svg>',
	),
	array(
		'label' => __( 'Enroll students so they can start', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 10a3 3 0 100-6 3 3 0 000 6z"/><path d="M4 16a6 6 0 0112 0"/></svg>',
	),
	array(
		'label' => __( 'Watch progress as they learn', 'mint-lms' ),
		'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 14l4-4 3 3 7-7"/></svg>',
	),
);

$tree = array(
	__( 'Course', 'mint-lms' ),
	__( 'Lesson Group', 'mint-lms' ),
	__( 'Lesson', 'mint-lms' ),
	__( 'Quiz', 'mint-lms' ),
	__( 'Question', 'mint-lms' ),
);

$attach_steps = array(
	array(
		'label' => __( '1. Create a Lesson', 'mint-lms' ),
		'hint'  => __( 'Starts on its own — not linked yet', 'mint-lms' ),
		'mod'   => '',
	),
	array(
		'label' => __( '2. Attach to a Lesson Group', 'mint-lms' ),
		'hint'  => __( 'Connect it where it belongs', 'mint-lms' ),
		'mod'   => ' is-mid',
	),
	array(
		'label' => __( '3. It lives in your Course', 'mint-lms' ),
		'hint'  => __( 'Students will see it in order', 'mint-lms' ),
		'mod'   => ' is-root',
	),
);

$manage_steps = array(
	array(
		'label' => __( '1. Create a Lesson / Quiz', 'mint-lms' ),
		'hint'  => __( 'Starts on its own — not linked yet', 'mint-lms' ),
		'mod'   => '',
	),
	array(
		'label' => __( '2. Attach into any Course', 'mint-lms' ),
		'hint'  => __( 'Lesson Group → Lesson → Quiz → Question', 'mint-lms' ),
		'mod'   => ' is-mid',
	),
	array(
		'label' => __( '3. Enroll & oversee', 'mint-lms' ),
		'hint'  => __( 'Open access site-wide; follow reports', 'mint-lms' ),
		'mod'   => ' is-root',
	),
);

$teach_steps = array(
	array(
		'label' => __( '1. Create a Lesson / Quiz', 'mint-lms' ),
		'hint'  => __( 'Starts on its own — not linked yet', 'mint-lms' ),
		'mod'   => '',
	),
	array(
		'label' => __( '2. Attach into your Course', 'mint-lms' ),
		'hint'  => __( 'Lesson Group → Lesson → Quiz → Question', 'mint-lms' ),
		'mod'   => ' is-mid',
	),
	array(
		'label' => __( '3. Enroll & teach', 'mint-lms' ),
		'hint'  => __( 'Invite students; watch their progress', 'mint-lms' ),
		'mod'   => ' is-root',
	),
);

if ( $is_instructor_welcome ) {
	$step2_flow       = $teach_steps;
	$step2_flow_class = ' mint-welcome-flow--teach';
	$step2_flow_label = __( 'Teach flow', 'mint-lms' );
} elseif ( $is_manager_welcome ) {
	$step2_flow       = $manage_steps;
	$step2_flow_class = ' mint-welcome-flow--manage';
	$step2_flow_label = __( 'Manage flow', 'mint-lms' );
} else {
	$step2_flow       = $attach_steps;
	$step2_flow_class = '';
	$step2_flow_label = __( 'Create then attach', 'mint-lms' );
}

$outcome_steps = array(
	array(
		'label' => __( 'Draft course', 'mint-lms' ),
		'hint'  => __( 'Build and edit privately', 'mint-lms' ),
		'mod'   => '',
	),
	array(
		'label' => __( 'Publish', 'mint-lms' ),
		'hint'  => __( 'Make it available to students', 'mint-lms' ),
		'mod'   => ' is-mid',
	),
	array(
		'label' => __( 'Students learn + progress', 'mint-lms' ),
		'hint'  => __( 'They follow lessons at their own pace', 'mint-lms' ),
		'mod'   => ' is-root',
	),
);

/**
 * @param array<int, array{label:string, icon:string}> $features Features list.
 */
$render_features = static function ( array $features ): void {
	echo '<ul class="mint-welcome__features">';
	foreach ( $features as $feature ) {
		echo '<li class="mint-welcome__feature">';
		echo '<span class="mint-welcome__feature-icon">';
		echo $feature['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup.
		echo '</span>';
		echo '<span>' . esc_html( $feature['label'] ) . '</span>';
		echo '</li>';
	}
	echo '</ul>';
};
?>
<div
	class="mint-welcome<?php echo esc_attr( $welcome_mod_class ); ?>"
	x-data="mintWelcomeOnboarding({ maxStep: <?php echo (int) $max_steps; ?> })"
	x-cloak
>
	<section class="mint-welcome__left">
		<div class="mint-welcome__brand-row">
			<div class="mint-welcome__brand">
				<div class="mint-welcome__brand-mark" aria-hidden="true">
					<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0F0E1A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h12M4 10h8M4 14.5h5"/></svg>
				</div>
				<span class="mint-welcome__brand-name"><?php esc_html_e( 'Mint LMS', 'mint-lms' ); ?></span>
			</div>
			<nav class="mint-welcome__step-nav" aria-label="<?php esc_attr_e( 'Welcome steps', 'mint-lms' ); ?>">
				<button
					type="button"
					class="mint-welcome__step-nav-btn"
					:class="{ 'is-active': step === 1 }"
					:aria-current="step === 1 ? 'step' : null"
					@click="go(1)"
				>
					<?php esc_html_e( 'Step 1', 'mint-lms' ); ?>
				</button>
				<button
					type="button"
					class="mint-welcome__step-nav-btn"
					:class="{ 'is-active': step === 2 }"
					:aria-current="step === 2 ? 'step' : null"
					@click="go(2)"
				>
					<?php esc_html_e( 'Step 2', 'mint-lms' ); ?>
				</button>
				<?php if ( ! $is_two_step_welcome ) : ?>
					<button
						type="button"
						class="mint-welcome__step-nav-btn"
						:class="{ 'is-active': step === 3 }"
						:aria-current="step === 3 ? 'step' : null"
						@click="go(3)"
					>
						<?php esc_html_e( 'Step 3', 'mint-lms' ); ?>
					</button>
				<?php endif; ?>
			</nav>
		</div>

		<template x-if="step === 1">
			<div class="mint-welcome__step-panel">
				<div class="mint-welcome__hero">
					<h1 class="mint-welcome__title"><?php esc_html_e( 'Teach what you already know.', 'mint-lms' ); ?></h1>
					<p class="mint-welcome__lead"><?php esc_html_e( 'Turn what you teach into a course your students can follow at their own pace. No theme edits, no plugins to wire up.', 'mint-lms' ); ?></p>
				</div>
				<?php $render_features( $features_step1 ); ?>
			</div>
		</template>

		<template x-if="step === 2">
			<div class="mint-welcome__step-panel">
				<div class="mint-welcome__hero">
					<?php if ( $is_instructor_welcome ) : ?>
						<h1 class="mint-welcome__title"><?php esc_html_e( 'Your course, your classroom.', 'mint-lms' ); ?></h1>
						<p class="mint-welcome__lead"><?php esc_html_e( 'Build lessons and quizzes at your pace, attach them into your course, then open it for your students.', 'mint-lms' ); ?></p>
					<?php elseif ( $is_manager_welcome ) : ?>
						<h1 class="mint-welcome__title"><?php esc_html_e( 'Manage learning across your site.', 'mint-lms' ); ?></h1>
						<p class="mint-welcome__lead"><?php esc_html_e( 'Build and attach course content anywhere, enroll students when ready, and follow progress across the whole LMS.', 'mint-lms' ); ?></p>
					<?php else : ?>
						<h1 class="mint-welcome__title"><?php esc_html_e( 'Build one piece at a time.', 'mint-lms' ); ?></h1>
						<p class="mint-welcome__lead"><?php esc_html_e( 'Create a lesson, quiz, or question first — then attach it where it belongs. Nothing has to be finished in one sitting.', 'mint-lms' ); ?></p>
					<?php endif; ?>
				</div>
				<?php $render_features( $features_step2 ); ?>
			</div>
		</template>

		<?php if ( ! $is_two_step_welcome ) : ?>
			<template x-if="step === 3">
				<div class="mint-welcome__step-panel">
					<div class="mint-welcome__hero">
						<h1 class="mint-welcome__title"><?php esc_html_e( 'Students learn when you publish.', 'mint-lms' ); ?></h1>
						<p class="mint-welcome__lead"><?php esc_html_e( 'When the course is ready, publish it and enroll students. They follow lessons at their own pace and see their progress as they go.', 'mint-lms' ); ?></p>
					</div>
					<?php $render_features( $features_step3 ); ?>
				</div>
			</template>
		<?php endif; ?>

		<p class="mint-welcome__thanks">
			<?php esc_html_e( 'Thank you for creating with WordPress.', 'mint-lms' ); ?>
		</p>
	</section>

	<section class="mint-welcome__right">
		<template x-if="step === 1">
			<div class="mint-welcome__step-panel mint-welcome__step-panel--right">
				<div class="mint-welcome__eyebrow"><?php esc_html_e( 'Step 1 · Structure', 'mint-lms' ); ?></div>
				<h2 class="mint-welcome__heading"><?php esc_html_e( 'What are you teaching?', 'mint-lms' ); ?></h2>
				<p class="mint-welcome__sub"><?php esc_html_e( 'Every course is built the same way, from the top down. Start with the course, then add the parts inside it.', 'mint-lms' ); ?></p>

				<div class="mint-welcome-tree" aria-label="<?php esc_attr_e( 'Course structure', 'mint-lms' ); ?>">
					<?php
					$depth = count( $tree );
					for ( $i = 0; $i < $depth; $i++ ) :
						$is_root = 0 === $i;
						?>
						<?php if ( $i > 0 ) : ?>
							<div class="mint-welcome-tree__branch">
						<?php endif; ?>
						<div class="mint-welcome-tree__node<?php echo $is_root ? ' is-root' : ''; ?>">
							<?php echo esc_html( $tree[ $i ] ); ?>
						</div>
					<?php endfor; ?>
					<?php for ( $i = 1; $i < $depth; $i++ ) : ?>
						</div>
					<?php endfor; ?>
				</div>
			</div>
		</template>

		<template x-if="step === 2">
			<div class="mint-welcome__step-panel mint-welcome__step-panel--right">
				<?php if ( $is_instructor_welcome ) : ?>
					<div class="mint-welcome__eyebrow"><?php esc_html_e( 'Step 2 · Teach', 'mint-lms' ); ?></div>
					<h2 class="mint-welcome__heading"><?php esc_html_e( 'Create → Attach → Open for students', 'mint-lms' ); ?></h2>
					<p class="mint-welcome__sub"><?php esc_html_e( 'Create content first, attach it into your course, then enroll students and follow their progress.', 'mint-lms' ); ?></p>
				<?php elseif ( $is_manager_welcome ) : ?>
					<div class="mint-welcome__eyebrow"><?php esc_html_e( 'Step 2 · Manage', 'mint-lms' ); ?></div>
					<h2 class="mint-welcome__heading"><?php esc_html_e( 'Create → Attach → Enroll & oversee', 'mint-lms' ); ?></h2>
					<p class="mint-welcome__sub"><?php esc_html_e( 'Create content first, attach it into any course, then enroll students and follow reports across the site.', 'mint-lms' ); ?></p>
				<?php else : ?>
					<div class="mint-welcome__eyebrow"><?php esc_html_e( 'Step 2 · Build', 'mint-lms' ); ?></div>
					<h2 class="mint-welcome__heading"><?php esc_html_e( 'Create → Attach', 'mint-lms' ); ?></h2>
					<p class="mint-welcome__sub"><?php esc_html_e( 'In Mint, new items start on their own. You connect them with Attach — Lesson to Lesson Group, Quiz to Lesson, Question to Quiz.', 'mint-lms' ); ?></p>
				<?php endif; ?>

				<div
					class="mint-welcome-flow<?php echo esc_attr( $step2_flow_class ); ?>"
					aria-label="<?php echo esc_attr( $step2_flow_label ); ?>"
				>
					<?php foreach ( $step2_flow as $index => $item ) : ?>
						<div class="mint-welcome-flow__card<?php echo esc_attr( $item['mod'] ); ?>">
							<div class="mint-welcome-flow__label"><?php echo esc_html( $item['label'] ); ?></div>
							<div class="mint-welcome-flow__hint"><?php echo esc_html( $item['hint'] ); ?></div>
						</div>
						<?php if ( $index < count( $step2_flow ) - 1 ) : ?>
							<div class="mint-welcome-flow__arrow" aria-hidden="true">↓</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</template>

		<?php if ( ! $is_two_step_welcome ) : ?>
			<template x-if="step === 3">
				<div class="mint-welcome__step-panel mint-welcome__step-panel--right">
					<div class="mint-welcome__eyebrow"><?php esc_html_e( 'Step 3 · Go live', 'mint-lms' ); ?></div>
					<h2 class="mint-welcome__heading"><?php esc_html_e( 'Publish + students learn', 'mint-lms' ); ?></h2>
					<p class="mint-welcome__sub"><?php esc_html_e( 'Your course goes from draft to live. Students get access, open lessons, and their progress shows up as they learn.', 'mint-lms' ); ?></p>

					<div class="mint-welcome-flow" aria-label="<?php esc_attr_e( 'Publish and learn', 'mint-lms' ); ?>">
						<?php foreach ( $outcome_steps as $index => $item ) : ?>
							<div class="mint-welcome-flow__card<?php echo esc_attr( $item['mod'] ); ?>">
								<div class="mint-welcome-flow__label"><?php echo esc_html( $item['label'] ); ?></div>
								<div class="mint-welcome-flow__hint"><?php echo esc_html( $item['hint'] ); ?></div>
							</div>
							<?php if ( $index < count( $outcome_steps ) - 1 ) : ?>
								<div class="mint-welcome-flow__arrow" aria-hidden="true">↓</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			</template>
		<?php endif; ?>

		<div class="mint-welcome__actions">
			<a href="<?php echo esc_url( $createUrl ); ?>" class="mint-welcome-cta">
				<?php esc_html_e( 'Create your first course', 'mint-lms' ); ?>
			</a>
			<?php if ( $showSkip ) : ?>
				<a href="<?php echo esc_url( $skipUrl ); ?>" class="mint-welcome-skip">
					<?php esc_html_e( 'Skip for now', 'mint-lms' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<p class="mint-welcome__version">
			<?php
			printf(
				/* translators: %s: WordPress version */
				esc_html__( 'Version %s', 'mint-lms' ),
				esc_html( $wpVersion )
			);
			?>
		</p>
	</section>
</div>
