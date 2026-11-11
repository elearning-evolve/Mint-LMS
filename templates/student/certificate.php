<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$certificateHtml = $certificateHtml ?? '';
$downloadUrl     = $downloadUrl ?? '';
?>
<div class="mint-max-w-content mint-mx-auto mint-px-4 sm:mint-px-6 mint-py-8 print:mint-max-w-none print:mint-p-0">
	<div class="mint-mb-6 mint-flex mint-flex-wrap mint-gap-3 print:mint-hidden">
		<?php if ( '' !== $downloadUrl ) : ?>
			<a href="<?php echo esc_url( $downloadUrl ); ?>" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover mint-transition-colors mint-duration-hover mint-no-underline" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Open printable certificate', 'mint-lms' ); ?>
			</a>
		<?php endif; ?>
		<button type="button" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-border mint-border-control mint-bg-bg mint-text-ink hover:mint-bg-bg-hover mint-transition-colors mint-duration-hover" onclick="window.print()">
			<?php esc_html_e( 'Print', 'mint-lms' ); ?>
		</button>
	</div>

	<div class="mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-p-8 sm:mint-p-12 mint-text-center print:mint-mx-auto print:mint-max-w-[800px] print:mint-rounded-none print:mint-border-2 print:mint-border-ink print:mint-p-12">
		<?php echo $certificateHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin-controlled template with escaped placeholders. ?>
	</div>
</div>
