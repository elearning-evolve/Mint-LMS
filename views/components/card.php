<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$title   = $title ?? '';
$body    = $body ?? '';
$footer  = $footer ?? '';
$attrs   = $attrs ?? '';
$padding = $padding ?? true;
?>
<div class="mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-shadow-card <?php echo $padding ? 'mint-p-6' : ''; ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $title ) : ?>
		<div class="mint-mb-4 mint-border-b mint-border-rule-soft mint-pb-4">
			<h3 class="mint-text-h3 mint-font-semibold mint-text-ink"><?php echo esc_html( $title ); ?></h3>
		</div>
	<?php endif; ?>
	<?php if ( '' !== $body ) : ?>
		<div class="mint-text-sm mint-text-ink-2">
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller provides escaped HTML fragments. ?>
		</div>
	<?php endif; ?>
	<?php if ( '' !== $footer ) : ?>
		<div class="mint-mt-4 mint-border-t mint-border-rule-soft mint-pt-4">
			<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>
</div>
