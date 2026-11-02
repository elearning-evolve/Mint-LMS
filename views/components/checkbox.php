<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$name     = $name ?? '';
$id       = $id ?? $name;
$label    = $label ?? '';
$checked  = $checked ?? false;
$value    = $value ?? '1';
$attrs    = $attrs ?? '';
?>
<label for="<?php echo esc_attr( $id ); ?>" class="mint-inline-flex mint-items-center mint-gap-2 mint-cursor-pointer">
	<input
		type="checkbox"
		name="<?php echo esc_attr( $name ); ?>"
		id="<?php echo esc_attr( $id ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		class="mint-h-4 mint-w-4 mint-rounded-sm mint-border-neutral-300 mint-text-accent focus:mint-ring-accent focus:mint-ring-offset-0"
		<?php checked( $checked ); ?>
		<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller supplies validated attributes. ?>
	/>
	<?php if ( '' !== $label ) : ?>
		<span class="mint-text-sm mint-text-neutral-700"><?php echo esc_html( $label ); ?></span>
	<?php endif; ?>
</label>
