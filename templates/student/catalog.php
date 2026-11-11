<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Student\Dto\StudentCatalogCourseDto;
use MintLMS\Infrastructure\Ui\MintUi;

/** @var list<StudentCatalogCourseDto> $courses */
$courses       = $courses ?? array();
$courseUrl     = $courseUrl ?? static fn( int $id ): string => home_url( '/' );
$excerptLength = 140;
$pageTitle     = __( 'Courses', 'mint-lms' );
$userName      = '';
$huePalette    = array(
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
		<h1 class="mint-text-h1 mint-font-semibold mint-text-ink mint-tracking-tight"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></h1>
		<p class="mint-mt-2 mint-text-body mint-text-ink-2"><?php esc_html_e( 'Browse available courses and start learning.', 'mint-lms' ); ?></p>
	</div>

	<?php if ( array() === $courses ) : ?>
		<div class="mint-py-12 mint-text-center">
			<p class="mint-text-body mint-text-ink-2"><?php esc_html_e( 'No courses are available yet. Check back soon.', 'mint-lms' ); ?></p>
		</div>
	<?php else : ?>
		<div class="mint-grid mint-grid-cols-1 sm:mint-grid-cols-2 lg:mint-grid-cols-3 mint-gap-5">
			<?php
			foreach ( $courses as $idx => $course ) :
				$hue         = $huePalette[ $idx % count( $huePalette ) ];
				$initial     = MintUi::courseInitial( $course->title );
				$overviewUrl = $courseUrl( $course->courseId );
				$excerpt     = wp_trim_words( wp_strip_all_tags( $course->description ), 20, '…' );
				if ( '' === $excerpt && strlen( $course->description ) > $excerptLength ) {
					$excerpt = substr( wp_strip_all_tags( $course->description ), 0, $excerptLength ) . '…';
				}
				?>
				<article class="mint-flex mint-flex-col mint-overflow-hidden mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-shadow-card hover:mint-border-control mint-transition-colors mint-duration-hover">
					<a href="<?php echo esc_url( $overviewUrl ); ?>" class="mint-block mint-no-underline" aria-label="<?php echo esc_attr( $course->title ); ?>">
						<div class="mint-flex mint-h-36 mint-items-center mint-justify-center <?php echo esc_attr( $hue['bg'] ); ?> mint-transition-opacity mint-duration-hover hover:mint-opacity-95">
							<span class="mint-text-display mint-font-semibold mint-leading-none <?php echo esc_attr( $hue['text'] ); ?>"><?php echo esc_html( $initial ); ?></span>
						</div>
					</a>
					<div class="mint-flex mint-flex-1 mint-flex-col mint-gap-3 mint-p-5">
						<h2 class="mint-text-h3 mint-font-semibold mint-leading-snug">
							<a href="<?php echo esc_url( $overviewUrl ); ?>" class="mint-text-ink hover:mint-text-accent mint-no-underline mint-transition-colors mint-duration-hover"><?php echo esc_html( $course->title ); ?></a>
						</h2>
						<?php if ( '' !== $excerpt ) : ?>
							<p class="mint-text-sm mint-leading-relaxed mint-text-ink-2 mint-line-clamp-3"><?php echo esc_html( $excerpt ); ?></p>
						<?php endif; ?>
						<div class="mint-text-xs mint-font-medium mint-uppercase mint-tracking-wide mint-text-ink-3"><?php echo esc_html( ucfirst( $course->enrollmentType ) ); ?></div>
						<a href="<?php echo esc_url( $overviewUrl ); ?>" class="mint-mt-auto mint-inline-flex mint-w-full mint-items-center mint-justify-center mint-h-control-md mint-px-4 mint-text-sm mint-font-medium mint-rounded-md mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover mint-transition-colors mint-duration-hover mint-no-underline">
							<?php esc_html_e( 'View course', 'mint-lms' ); ?>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
