<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$title       = $title ?? '';
$description = $description ?? '';
$action      = $action ?? '';
$icon        = $icon ?? '';
$attrs       = $attrs ?? '';
?>
<div class="mint-flex mint-flex-col mint-items-center mint-text-center" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $icon ) : ?>
		<div class="mint-mb-5 mint-flex mint-h-16 mint-w-16 mint-items-center mint-justify-center mint-rounded-xl mint-bg-accent-wash mint-text-accent" aria-hidden="true"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php else : ?>
		<div class="mint-mb-5 mint-flex mint-h-16 mint-w-16 mint-items-center mint-justify-center mint-rounded-xl mint-bg-accent-wash mint-text-accent" aria-hidden="true">
			<svg class="mint-h-7 mint-w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m6-2.292a8.967 8.967 0 00-6-2.584V3.75a8.967 8.967 0 016 2.584V18z"/>
			</svg>
		</div>
	<?php endif; ?>
	<?php if ( '' !== $title ) : ?>
		<h3 class="mint-text-h3 mint-font-semibold mint-text-ink"><?php echo esc_html( $title ); ?></h3>
	<?php endif; ?>
	<?php if ( '' !== $description ) : ?>
		<p class="mint-mt-2 mint-max-w-md mint-text-sm mint-text-ink-2"><?php echo esc_html( $description ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $action ) : ?>
		<div class="mint-mt-6"><?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php endif; ?>
</div>
