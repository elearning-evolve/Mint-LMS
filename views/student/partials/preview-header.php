<?php
declare(strict_types=1);
defined( 'ABSPATH' ) || exit;

$user         = wp_get_current_user();
$userName     = $userName ?? ( '' !== (string) $user->display_name ? (string) $user->display_name : '' );
$userInitials = $userInitials ?? MintLMS\Infrastructure\Ui\MintUi::initials( $userName );
$huePalette   = array(
	array(
		'bg'   => 'mint-bg-hue-2',
		'text' => 'mint-text-hue-2i',
	),
);
$hue          = $huePalette[0];

$previewKind  = $previewKind ?? 'lesson'; // lesson|quiz|question
$courseTitle  = trim( (string) ( $courseTitle ?? '' ) );
$sectionTitle = trim( (string) ( $sectionTitle ?? '' ) );
$itemTitle    = trim( (string) ( $itemTitle ?? '' ) );
$previewLabel = match ( $previewKind ) {
	'quiz' => __( 'Quiz preview', 'mint-lms' ),
	'question' => __( 'Question preview', 'mint-lms' ),
	default => __( 'Lesson preview', 'mint-lms' ),
};
$previewHint = match ( $previewKind ) {
	'quiz' => __( 'How this quiz looks to students', 'mint-lms' ),
	'question' => __( 'How this question looks to students', 'mint-lms' ),
	default => __( 'How this lesson looks to students', 'mint-lms' ),
};
?>
<header class="mint-mb-6 mint-border-b mint-border-[#E4E1F0] mint-bg-[#FAFBFC]">
	<div class="mint-mx-auto mint-flex mint-max-w-[700px] mint-flex-col mint-gap-3 mint-px-4 mint-py-4 sm:mint-flex-row sm:mint-items-center sm:mint-justify-between sm:mint-px-6">
		<div class="mint-min-w-0 mint-flex-1">
			<div class="mint-mb-1.5 mint-flex mint-flex-wrap mint-items-center mint-gap-2">
				<span class="mint-inline-flex mint-items-center mint-rounded-full mint-bg-[#E8FFF3] mint-px-2.5 mint-py-1 mint-text-[10px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink">
					<?php echo esc_html( $previewLabel ); ?>
				</span>
				<span class="mint-text-[12px] mint-text-[#5C5C77]"><?php echo esc_html( $previewHint ); ?></span>
			</div>

			<nav class="mint-flex mint-min-w-0 mint-flex-wrap mint-items-center mint-gap-1.5 mint-text-[14px]" aria-label="<?php esc_attr_e( 'Preview location', 'mint-lms' ); ?>">
				<?php if ( '' !== $courseTitle ) : ?>
					<span class="mint-truncate mint-font-semibold mint-text-ink"><?php echo esc_html( $courseTitle ); ?></span>
					<svg class="mint-h-3 mint-w-3 mint-shrink-0 mint-text-[#9A97B0]" width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5" /></svg>
				<?php endif; ?>
				<?php if ( '' !== $sectionTitle ) : ?>
					<span class="mint-truncate mint-text-[#5C5C77]"><?php echo esc_html( $sectionTitle ); ?></span>
					<svg class="mint-h-3 mint-w-3 mint-shrink-0 mint-text-[#9A97B0]" width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5" /></svg>
				<?php endif; ?>
				<span class="mint-truncate mint-font-semibold mint-text-[#17222B]">
					<?php echo esc_html( '' !== $itemTitle ? $itemTitle : $previewLabel ); ?>
				</span>
			</nav>
		</div>

		<div class="mint-flex mint-shrink-0 mint-items-center mint-gap-3">
			<?php if ( '' !== $userName ) : ?>
				<span class="mint-hidden sm:mint-inline mint-text-sm mint-text-[#5C5C77]"><?php echo esc_html( $userName ); ?></span>
			<?php endif; ?>
			<div class="mint-flex mint-h-10 mint-w-10 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-text-sm mint-font-semibold <?php echo esc_attr( $hue['bg'] . ' ' . $hue['text'] ); ?>" aria-hidden="true">
				<?php echo esc_html( $userInitials ); ?>
			</div>
		</div>
	</div>
</header>
