<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Student\Dto\StudentCourseItemDto;
use MintLMS\Infrastructure\Ui\MintUi;

/** @var list<StudentCourseItemDto> $courses */
$courses   = $courses ?? array();
$playerUrl = $playerUrl ?? home_url( '/' );

$user      = wp_get_current_user();
$firstName = '' !== (string) $user->first_name ? (string) $user->first_name : (string) $user->display_name;
$greeting  = MintUi::greeting();

$continueCourse = null;
foreach ( $courses as $c ) {
	if ( ! $c->isComplete && $c->progressPct > 0 ) {
		$continueCourse = $c;
		break;
	}
}
if ( null === $continueCourse && array() !== $courses ) {
	foreach ( $courses as $c ) {
		if ( ! $c->isComplete ) {
			$continueCourse = $c;
			break;
		}
	}
}

$totalLessons = $continueCourse ? max( 1, $continueCourse->totalLessons ) : 0;
$doneLessons  = $continueCourse ? min( $continueCourse->completedLessons, $totalLessons ) : 0;

$pageTitle  = __( 'My learning', 'mint-lms' );
$huePalette = array(
	array(
		'bg'   => 'mint-bg-hue-1',
		'text' => 'mint-text-hue-1i',
	),
	array(
		'bg'   => 'mint-bg-hue-2',
		'text' => 'mint-text-hue-2i',
	),
	array(
		'bg'   => 'mint-bg-hue-3',
		'text' => 'mint-text-hue-3i',
	),
	array(
		'bg'   => 'mint-bg-hue-4',
		'text' => 'mint-text-hue-4i',
	),
	array(
		'bg'   => 'mint-bg-hue-5',
		'text' => 'mint-text-hue-5i',
	),
	array(
		'bg'   => 'mint-bg-hue-6',
		'text' => 'mint-text-hue-6i',
	),
);
require MINTLMS_PATH . 'views/student/partials/student-header.php';
?>

<div class="mint-max-w-content mint-mx-auto mint-px-4 sm:mint-px-6 mint-pb-10">

	<div class="mint-mb-8">
		<h1 class="mint-text-h1 mint-font-semibold mint-text-ink mint-tracking-tight">
			<?php echo esc_html( $greeting . ', ' . $firstName ); ?>
		</h1>
		<?php if ( null !== $continueCourse ) : ?>
			<p class="mint-mt-2 mint-text-body mint-text-ink-2">
				<?php
				printf(
					/* translators: 1: progress percentage, 2: course title */
					esc_html__( 'You\'re %1$d%% through %2$s.', 'mint-lms' ),
					(int) round( $continueCourse->progressPct ),
					esc_html( $continueCourse->title )
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<?php
	if ( null !== $continueCourse ) :
		$continueLessonId = $continueCourse->lastLessonId ?? $continueCourse->firstLessonId;
		$continueUrl      = $continueLessonId
			? add_query_arg(
				array(
					'mint_course' => (string) $continueCourse->courseId,
					'mint_lesson' => (string) $continueLessonId,
				),
				$playerUrl
			)
			: $playerUrl;
		$pct              = (int) round( $continueCourse->progressPct );
		?>
		<div class="mint-mb-10 mint-rounded-xl mint-bg-accent mint-p-6 sm:mint-p-7 mint-text-neutral-50 mint-shadow-card">
			<div class="mint-text-over mint-font-semibold mint-uppercase mint-tracking-widest mint-text-neutral-50/80">
				<?php esc_html_e( 'CONTINUE WHERE YOU LEFT OFF', 'mint-lms' ); ?>
			</div>
			<div class="mint-mt-3 mint-text-h2 mint-font-semibold mint-tracking-tight">
				<?php echo esc_html( $continueCourse->title ); ?>
			</div>
			<div class="mint-mt-1 mint-text-sm mint-text-neutral-50/80">
				<?php
				printf(
					/* translators: 1: completed lesson number, 2: total lessons */
					esc_html__( 'Lesson %1$d of %2$d', 'mint-lms' ),
					absint( $doneLessons ),
					absint( $totalLessons )
				);
				?>
			</div>
			<progress
				class="mint-mt-4 mint-block mint-h-1.5 mint-w-full mint-appearance-none mint-overflow-hidden mint-rounded-full mint-bg-neutral-50/25 [&::-moz-progress-bar]:mint-rounded-full [&::-moz-progress-bar]:mint-bg-neutral-50 [&::-webkit-progress-bar]:mint-rounded-full [&::-webkit-progress-bar]:mint-bg-neutral-50/25 [&::-webkit-progress-value]:mint-rounded-full [&::-webkit-progress-value]:mint-bg-neutral-50"
				value="<?php echo esc_attr( (string) absint( $pct ) ); ?>"
				max="100"
				aria-label="<?php esc_attr_e( 'Course progress', 'mint-lms' ); ?>"
			></progress>
			<a href="<?php echo esc_url( $continueUrl ); ?>" class="mint-mt-5 mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-bg-neutral-50 mint-text-accent hover:mint-bg-neutral-100 mint-transition-colors mint-duration-hover mint-no-underline">
				<?php esc_html_e( 'Continue lesson', 'mint-lms' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( array() === $courses ) : ?>
		<div class="mint-py-12 mint-text-center">
			<p class="mint-text-body mint-text-ink-2"><?php esc_html_e( 'No courses in progress. Enroll in a course to start learning.', 'mint-lms' ); ?></p>
		</div>
	<?php else : ?>
		<div class="mint-mb-5 mint-text-over mint-font-semibold mint-uppercase mint-tracking-widest mint-text-ink-3">
			<?php esc_html_e( 'YOUR COURSES', 'mint-lms' ); ?>
		</div>
		<div class="mint-grid mint-grid-cols-1 sm:mint-grid-cols-2 lg:mint-grid-cols-3 mint-gap-5">
			<?php
			foreach ( $courses as $idx => $course ) :
				$hue     = $huePalette[ $idx % count( $huePalette ) ];
				$initial = MintUi::courseInitial( $course->title );
				$pctVal  = (int) round( $course->progressPct );

				$targetLesson = $course->isComplete
					? $course->firstLessonId
					: ( $course->lastLessonId ?? $course->firstLessonId );
				$actionUrl    = $targetLesson
					? add_query_arg(
						array(
							'mint_course' => (string) $course->courseId,
							'mint_lesson' => (string) $targetLesson,
						),
						$playerUrl
					)
					: $playerUrl;
				?>
				<a href="<?php echo esc_url( $actionUrl ); ?>" class="mint-group mint-flex mint-flex-col mint-overflow-hidden mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-shadow-card hover:mint-border-control mint-transition-colors mint-duration-hover mint-no-underline">
					<div class="mint-flex mint-h-28 mint-items-center mint-justify-center <?php echo esc_attr( $hue['bg'] ); ?>">
						<span class="mint-text-display mint-font-semibold mint-leading-none <?php echo esc_attr( $hue['text'] ); ?>"><?php echo esc_html( $initial ); ?></span>
					</div>
					<div class="mint-flex mint-flex-1 mint-flex-col mint-gap-3 mint-p-5">
						<div class="mint-text-h3 mint-font-semibold mint-text-ink mint-line-clamp-2"><?php echo esc_html( $course->title ); ?></div>
						<progress
							class="mint-block mint-h-1.5 mint-w-full mint-appearance-none mint-overflow-hidden mint-rounded-full mint-bg-bg-track [&::-moz-progress-bar]:mint-rounded-full [&::-moz-progress-bar]:mint-bg-accent [&::-webkit-progress-bar]:mint-rounded-full [&::-webkit-progress-bar]:mint-bg-bg-track [&::-webkit-progress-value]:mint-rounded-full [&::-webkit-progress-value]:mint-bg-accent"
							value="<?php echo esc_attr( (string) absint( $pctVal ) ); ?>"
							max="100"
							aria-label="<?php echo esc_attr( $course->title ); ?>"
						></progress>
						<div class="mint-text-xs mint-font-medium mint-tabular-nums mint-text-ink-3"><?php echo esc_html( (string) absint( $pctVal ) ); ?>%</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
