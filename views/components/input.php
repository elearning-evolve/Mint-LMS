<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$name        = $name ?? '';
$id          = $id ?? $name;
$label       = $label ?? '';
$value       = $value ?? '';
$type        = $type ?? 'text';
$placeholder = $placeholder ?? '';
$required    = $required ?? false;
$error       = $error ?? '';
$attrs       = $attrs ?? '';
?>
<div class="mint-field">
	<?php if ( '' !== $label ) : ?>
		<label for="<?php echo esc_attr( $id ); ?>" class="mint-label">
			<?php echo esc_html( $label ); ?>
			<?php if ( $required ) : ?>
				<span class="mint-text-danger" aria-hidden="true"> *</span>
			<?php endif; ?>
		</label>
	<?php endif; ?>
	<input
		type="<?php echo esc_attr( $type ); ?>"
		name="<?php echo esc_attr( $name ); ?>"
		id="<?php echo esc_attr( $id ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
		class="mint-input"
		<?php echo $required ? 'required' : ''; ?>
		<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	/>
	<?php if ( '' !== $error ) : ?>
		<p class="mint-text-xs mint-text-danger" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>
</div>
