<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$label   = $label ?? '';
$variant = $variant ?? 'default';
$attrs   = $attrs ?? '';

$variantClasses = array(
	'default' => 'mint-bg-neutral-100 mint-text-neutral-700',
	'accent'  => 'mint-bg-accent mint-text-neutral-50',
	'muted'   => 'mint-bg-neutral-200 mint-text-neutral-600',
	'danger'  => 'mint-bg-neutral-800 mint-text-neutral-50',
);

$classes = 'mint-inline-flex mint-items-center mint-px-2 mint-py-0.5 mint-text-xs mint-font-medium mint-rounded-sm ' . ( $variantClasses[ $variant ] ?? $variantClasses['default'] );
?>
<span class="<?php echo esc_attr( $classes ); ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo esc_html( $label ); ?>
</span>
