<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$value = max( 0, (int) ( $value ?? 0 ) );
$max   = max( 1, (int) ( $max ?? 100 ) );
$label = $label ?? '';
$attrs = $attrs ?? '';

$percent = min( 100, (int) round( ( $value / $max ) * 100 ) );
?>
<div class="mint-w-full" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $label ) : ?>
		<div class="mint-mb-1 mint-flex mint-justify-between">
			<span class="mint-text-sm mint-font-medium mint-text-ink-2"><?php echo esc_html( $label ); ?></span>
			<span class="mint-text-sm mint-text-ink-3"><?php echo esc_html( (string) $percent ); ?>%</span>
		</div>
	<?php endif; ?>
	<progress
		class="mint-progress-bar mint-h-[9px] mint-w-full mint-appearance-none mint-overflow-hidden mint-rounded-full mint-bg-bg-track [&::-webkit-progress-bar]:mint-rounded-full [&::-webkit-progress-bar]:mint-bg-bg-track [&::-webkit-progress-value]:mint-rounded-full [&::-webkit-progress-value]:mint-bg-accent [&::-moz-progress-bar]:mint-rounded-full [&::-moz-progress-bar]:mint-bg-accent"
		value="<?php echo esc_attr( (string) $percent ); ?>"
		max="100"
		aria-valuenow="<?php echo esc_attr( (string) $value ); ?>"
		aria-valuemin="0"
		aria-valuemax="<?php echo esc_attr( (string) $max ); ?>"
	></progress>
</div>
