<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$variant = $variant ?? 'primary';
$label   = $label ?? '';
$type    = $type ?? 'button';
$size    = $size ?? '';
$attrs   = $attrs ?? '';

$classes = 'mint-inline-flex mint-items-center mint-justify-center mint-gap-2 mint-whitespace-nowrap mint-rounded-lg mint-border-0 mint-font-semibold mint-leading-none mint-no-underline mint-cursor-pointer mint-transition-colors mint-duration-hover mint-ease-mint focus:mint-outline-none disabled:mint-opacity-[0.45] disabled:mint-cursor-not-allowed disabled:mint-pointer-events-none';

if ( 'primary' === $variant ) {
	$classes .= ' mint-h-control-lg mint-px-[18px] mint-text-base mint-bg-cta mint-text-cta-ink hover:mint-bg-cta-hover focus:mint-shadow-focus';
} elseif ( 'secondary' === $variant ) {
	$classes .= ' mint-h-control-lg mint-px-[18px] mint-text-base mint-bg-bg mint-text-ink mint-border mint-border-control hover:mint-bg-bg-subtle hover:mint-border-ink-3 focus:mint-shadow-focus';
} elseif ( 'ghost' === $variant ) {
	$classes .= ' mint-h-control-lg mint-px-[18px] mint-text-base mint-bg-transparent mint-text-ink-2 hover:mint-bg-bg-subtle hover:mint-text-ink focus:mint-shadow-focus';
} elseif ( 'danger' === $variant ) {
	$classes .= ' mint-h-control-lg mint-px-[18px] mint-text-base mint-bg-danger mint-text-neutral-50 hover:mint-bg-danger-hover focus:mint-shadow-focus-danger';
} elseif ( 'dark' === $variant ) {
	$classes .= ' mint-h-control-lg mint-px-[18px] mint-text-base mint-bg-bg-ink mint-text-neutral-50 hover:mint-opacity-[0.86] focus:mint-shadow-focus';
} elseif ( 'danger-outline' === $variant ) {
	$classes .= ' mint-h-control-lg mint-px-[18px] mint-text-base mint-bg-bg mint-text-danger mint-border-[1.5px] mint-border-[#D98B84] hover:mint-bg-danger-wash focus:mint-shadow-focus-danger';
}

if ( 'sm' === $size ) {
	$classes .= ' !mint-h-control-md mint-px-[14px] mint-text-sm mint-rounded-md';
} elseif ( 'lg' === $size ) {
	$classes .= ' !mint-h-[52px] mint-px-6 mint-text-body mint-rounded-xl';
} elseif ( 'xs' === $size ) {
	$classes .= ' !mint-h-control mint-px-[13px] mint-text-sm mint-rounded-md';
}
?>
<button type="<?php echo esc_attr( $type ); ?>" class="<?php echo esc_attr( $classes ); ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo esc_html( $label ); ?>
</button>
