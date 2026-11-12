<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Student\Dto\StudentCourseItemDto;
use MintLMS\Infrastructure\Ui\MintUi;

/** @var list<StudentCourseItemDto> $courses */
$courses   = $courses ?? array();
$filter    = $filter ?? 'all';
$pageUrl   = $pageUrl ?? home_url( '/' );
$playerUrl = $playerUrl ?? home_url( '/' );

$totalCount    = count( $courses );
$finishedCount = 0;
foreach ( $courses as $c ) {
	if ( $c->isComplete ) {
		++$finishedCount;
	}
}

$pageTitle  = __( 'My courses', 'mint-lms' );
$userName   = '';
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
			<?php esc_html_e( 'My courses', 'mint-lms' ); ?>
		</h1>
		<p class="mint-mt-2 mint-text-body mint-text-ink-2">
			<?php
			printf(
				/* translators: 1: total course count, 2: finished course count */
				esc_html__( '%1$d courses · %2$d finished', 'mint-lms' ),
				absint( $totalCount ),
				absint( $finishedCount )
			);
			?>
		</p>
	</div>

	<?php
	$filters = array(
		'all'         => __( 'All', 'mint-lms' ),
		'in_progress' => __( 'In progress', 'mint-lms' ),
		'completed'   => __( 'Completed', 'mint-lms' ),
	);
	?>
	<div class="mint-flex mint-flex-wrap mint-gap-2 mint-mb-7">
		<?php
		foreach ( $filters as $key => $label ) :
			$isActive = $filter === $key;
			$tabClass = $isActive
				? 'mint-bg-accent mint-text-neutral-50 mint-border-accent'
				: 'mint-bg-bg mint-text-ink-2 mint-border-rule hover:mint-bg-bg-hover hover:mint-text-ink';
			?>
			<a
				href="<?php echo esc_url( add_query_arg( 'filter', $key, $pageUrl ) ); ?>"
				class="mint-inline-flex mint-items-center mint-h-control mint-px-4 mint-text-sm mint-font-medium mint-rounded-full mint-border mint-transition-colors mint-duration-hover mint-no-underline <?php echo esc_attr( $tabClass ); ?>"
				<?php echo $isActive ? 'aria-current="page"' : ''; ?>
			><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<?php if ( array() === $courses ) : ?>
		<div class="mint-py-12 mint-text-center">
			<p class="mint-text-body mint-text-ink-2"><?php esc_html_e( 'No courses found. Enroll in a course to get started.', 'mint-lms' ); ?></p>
		</div>
	<?php else : ?>
		<div class="mint-grid mint-grid-cols-1 sm:mint-grid-cols-2 lg:mint-grid-cols-3 mint-gap-5">
			<?php
			foreach ( $courses as $idx => $course ) :
				$hue     = $huePalette[ $idx % count( $huePalette ) ];
				$initial = MintUi::courseInitial( $course->title );
				$pctVal  = (int) round( $course->progressPct );
				$isDone  = $course->isComplete;

				$targetLesson = $isDone
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

				$ctaLabel = $isDone ? __( 'Review course', 'mint-lms' ) : __( 'Continue', 'mint-lms' );
				$barTrack = $isDone
					? '[&::-webkit-progress-value]:mint-bg-success [&::-moz-progress-bar]:mint-bg-success'
					: '[&::-webkit-progress-value]:mint-bg-accent [&::-moz-progress-bar]:mint-bg-accent';
				$btnClass = $isDone
					? 'mint-border mint-border-control mint-bg-bg mint-text-ink hover:mint-bg-bg-hover'
					: 'mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover';
				?>
				<article class="mint-flex mint-flex-col mint-overflow-hidden mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-shadow-card">
					<div class="mint-relative mint-flex mint-h-36 mint-items-center mint-justify-center <?php echo esc_attr( $hue['bg'] ); ?>">
						<span class="mint-text-display mint-font-semibold mint-leading-none <?php echo esc_attr( $hue['text'] ); ?>"><?php echo esc_html( $initial ); ?></span>
						<?php if ( $isDone ) : ?>
							<div class="mint-absolute mint-top-3 mint-right-3 mint-flex mint-h-7 mint-w-7 mint-items-center mint-justify-center mint-rounded-full mint-bg-success mint-text-neutral-50">
								<svg class="mint-h-3.5 mint-w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
							</div>
						<?php endif; ?>
					</div>
					<div class="mint-flex mint-flex-1 mint-flex-col mint-gap-3 mint-p-5">
						<div class="mint-text-h3 mint-font-semibold mint-text-ink mint-line-clamp-2"><?php echo esc_html( $course->title ); ?></div>
						<div class="mint-text-xs mint-font-medium mint-uppercase mint-tracking-wide mint-text-ink-3"><?php echo esc_html( $course->enrollmentType ); ?></div>
						<progress
							class="mint-block mint-h-2 mint-w-full mint-appearance-none mint-overflow-hidden mint-rounded-full mint-bg-bg-track [&::-webkit-progress-bar]:mint-rounded-full [&::-webkit-progress-bar]:mint-bg-bg-track <?php echo esc_attr( $barTrack ); ?>"
							value="<?php echo esc_attr( (string) absint( $pctVal ) ); ?>"
							max="100"
							aria-label="<?php echo esc_attr( $course->title ); ?>"
						></progress>
						<a href="<?php echo esc_url( $actionUrl ); ?>" class="mint-mt-auto mint-inline-flex mint-w-full mint-items-center mint-justify-center mint-h-control-md mint-px-4 mint-text-sm mint-font-medium mint-rounded-md mint-transition-colors mint-duration-hover mint-no-underline <?php echo esc_attr( $btnClass ); ?>">
							<?php echo esc_html( $ctaLabel ); ?>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
