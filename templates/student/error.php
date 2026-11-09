<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$message = $message ?? __( 'We couldn\'t load this course. Please try again.', 'mint-lms' );
?>
<div class="mint-flex mint-min-h-[50vh] mint-items-center mint-justify-center mint-px-4 mint-py-16">
	<div class="mint-w-full mint-max-w-md mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-p-8 mint-text-center mint-shadow-card">
		<div class="mint-mx-auto mint-mb-5 mint-flex mint-h-14 mint-w-14 mint-items-center mint-justify-center mint-rounded-xl mint-bg-danger-wash">
			<svg class="mint-h-6 mint-w-6 mint-text-danger" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
		</div>
		<h1 class="mint-text-h2 mint-font-semibold mint-text-ink mint-tracking-tight"><?php esc_html_e( 'Something went wrong', 'mint-lms' ); ?></h1>
		<p class="mint-mt-3 mint-text-sm mint-leading-relaxed mint-text-ink-2" role="alert"><?php echo esc_html( $message ); ?></p>
		<button type="button" class="mint-mt-6 mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-border mint-border-control mint-bg-bg mint-text-ink hover:mint-bg-bg-hover mint-transition-colors mint-duration-hover" onclick="window.location.reload()">
			<?php esc_html_e( 'Try again', 'mint-lms' ); ?>
		</button>
	</div>
</div>
