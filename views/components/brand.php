<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$compact = $compact ?? false;
?>
<div class="mint-flex mint-items-center mint-gap-3">
	<div class="mint-flex mint-h-9 mint-w-9 mint-flex-none mint-items-center mint-justify-center mint-rounded-lg mint-bg-accent-wash mint-text-accent" aria-hidden="true">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M12 3L4 8v8l8 5 8-5V8l-8-5z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
			<path d="M12 12l8-5M12 12v9M12 12L4 7" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
		</svg>
	</div>
	<?php if ( ! $compact ) : ?>
		<div>
			<div class="mint-text-sm mint-font-semibold mint-text-ink"><?php echo esc_html__( 'Mint LMS', 'mint-lms' ); ?></div>
			<div class="mint-text-xs mint-text-ink-3"><?php echo esc_html__( 'Learning platform', 'mint-lms' ); ?></div>
		</div>
	<?php endif; ?>
</div>
