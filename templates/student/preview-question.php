<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Quiz\QuizQuestion;

/** @var array{title: string, meta: string, type: string, options: list<array{text: string, correct: bool}>, courseTitle: string} $preview */
$preview      = $preview;
$options      = is_array( $preview['options'] ?? null ) ? $preview['options'] : array();
$type         = (string) ( $preview['type'] ?? QuizQuestion::TYPE_MCQ );
$courseTitle  = trim( (string) ( $preview['courseTitle'] ?? '' ) );
$previewKind  = 'question';
$itemTitle    = trim( (string) ( $preview['title'] ?? '' ) );
$sectionTitle = '';
require MINTLMS_PATH . 'views/student/partials/preview-header.php';
?>

<div class="mint-mx-auto mint-max-w-[700px] mint-px-4 mint-pb-14 sm:mint-px-6">
	<article class="mint-rounded-[18px] mint-border mint-border-[#E4E1F0] mint-bg-white mint-px-7 mint-py-11 mint-shadow-[0_8px_28px_rgba(28,20,80,0.07)] sm:mint-px-12">
		<div class="mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.06em] mint-text-cta-ink">
			<?php echo esc_html( (string) $preview['meta'] ); ?>
		</div>
		<h1 class="mint-m-0 mint-mt-2.5 mint-text-[26px] mint-font-semibold mint-leading-[33px] mint-tracking-[-0.02em] mint-text-ink">
			<?php echo esc_html( (string) $preview['title'] ); ?>
		</h1>

		<?php if ( QuizQuestion::TYPE_ESSAY === $type ) : ?>
			<div class="mint-mt-[22px]">
				<textarea
					rows="5"
					class="mint-w-full mint-rounded-[10px] mint-border-[1.5px] mint-border-[#E4E1F0] mint-bg-white mint-px-4 mint-py-3 mint-text-[15px] mint-text-[#17222B] mint-outline-none focus:mint-border-cta-ink"
					placeholder="<?php esc_attr_e( 'Write your answer…', 'mint-lms' ); ?>"
					readonly
				></textarea>
			</div>
		<?php else : ?>
			<div class="mint-mt-[22px] mint-grid mint-gap-2.5">
				<?php
				foreach ( $options as $option ) :
					$correct   = ! empty( $option['correct'] );
					$border    = $correct ? '#0B4F3F' : '#E4E1F0';
					$bg        = $correct ? '#E8FFF3' : '#FFFFFF';
					$dotBorder = $correct ? '#0B4F3F' : '#C7C4D8';
					$dotBg     = $correct ? '#0B4F3F' : 'transparent';
					?>
					<div
						class="mint-flex mint-items-center mint-gap-3 mint-rounded-[10px] mint-border-[1.5px] mint-px-4 mint-py-3.5"
						style="border-color: <?php echo esc_attr( $border ); ?>; background: <?php echo esc_attr( $bg ); ?>;"
					>
						<div
							class="mint-h-[18px] mint-w-[18px] mint-shrink-0 mint-rounded-full mint-border-2"
							style="border-color: <?php echo esc_attr( $dotBorder ); ?>; background: <?php echo esc_attr( $dotBg ); ?>;"
							aria-hidden="true"
						></div>
						<div class="mint-text-[15px] mint-text-[#17222B]"><?php echo esc_html( (string) ( $option['text'] ?? '' ) ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="mint-mt-[26px] mint-flex mint-justify-end">
			<button
				type="button"
				class="mint-inline-flex mint-h-11 mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-bg-cta mint-px-5 mint-text-[15px] mint-font-bold mint-text-cta-ink hover:mint-bg-cta-hover focus-visible:mint-outline-none focus-visible:mint-shadow-[0_0_0_3px_rgba(11,79,63,0.35)]"
				disabled
			><?php esc_html_e( 'Submit answer', 'mint-lms' ); ?></button>
		</div>
	</article>
</div>
