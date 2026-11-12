<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$title        = $title ?? __( 'Something went wrong', 'mint-lms' );
$message      = $message ?? '';
$description  = $description ?? '';
$messageAttrs = $messageAttrs ?? '';
$retry        = $retry ?? '';
$attrs        = $attrs ?? '';

if ( '' === $message && '' !== $description ) {
	$message = $description;
}

if ( '' === $message && '' === $messageAttrs ) {
	$message = __( 'An unexpected error occurred. Please try again.', 'mint-lms' );
}
?>
<div class="mint-flex mint-flex-col mint-items-center mint-text-center" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="mint-mb-4 mint-flex mint-h-12 mint-w-12 mint-items-center mint-justify-center mint-rounded-xl mint-bg-danger-wash mint-text-danger" aria-hidden="true">
		<svg class="mint-h-6 mint-w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
		</svg>
	</div>
	<h3 class="mint-text-h3 mint-font-semibold mint-text-ink"><?php echo esc_html( $title ); ?></h3>
	<p class="mint-mt-2 mint-max-w-md mint-text-sm mint-text-ink-2" <?php echo $messageAttrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( '' === $messageAttrs ) : ?>
			<?php echo esc_html( $message ); ?>
		<?php endif; ?>
	</p>
	<?php if ( '' !== $retry ) : ?>
		<div class="mint-mt-6">
			<?php echo $retry; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>
</div>
