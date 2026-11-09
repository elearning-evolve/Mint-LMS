<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$src      = $src ?? '';
$alt      = $alt ?? '';
$initials = $initials ?? '';
$size     = $size ?? 'md';
$attrs    = $attrs ?? '';

$sizeClasses = array(
	'sm' => 'mint-h-8 mint-w-8 mint-text-xs',
	'md' => 'mint-h-10 mint-w-10 mint-text-sm',
	'lg' => 'mint-h-12 mint-w-12 mint-text-base',
);

$sizeClass = $sizeClasses[ $size ] ?? $sizeClasses['md'];
?>
<div class="mint-inline-flex mint-shrink-0 <?php echo esc_attr( $sizeClass ); ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $src ) : ?>
		<img
			src="<?php echo esc_url( $src ); ?>"
			alt="<?php echo esc_attr( $alt ); ?>"
			class="mint-h-full mint-w-full mint-rounded-full mint-object-cover mint-border mint-border-neutral-200"
		/>
	<?php else : ?>
		<span class="mint-flex mint-h-full mint-w-full mint-items-center mint-justify-center mint-rounded-full mint-bg-neutral-200 mint-text-neutral-700 mint-font-medium mint-border mint-border-neutral-300" aria-hidden="true">
			<?php echo esc_html( $initials ); ?>
		</span>
	<?php endif; ?>
</div>
