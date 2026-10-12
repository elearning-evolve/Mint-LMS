<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

$name        = $name ?? '';
$id          = $id ?? $name;
$label       = $label ?? '';
$value       = $value ?? '';
$placeholder = $placeholder ?? '';
$rows        = $rows ?? 4;
$required    = $required ?? false;
$error       = $error ?? '';
$attrs       = $attrs ?? '';

$textareaClasses = 'mint-block mint-w-full mint-min-h-[120px] mint-resize-y mint-rounded-md mint-border mint-border-control mint-bg-bg mint-px-3 mint-py-2 mint-text-base mint-text-ink mint-outline-none hover:mint-border-mint-blue focus:mint-border-mint-blue focus:mint-shadow-focus disabled:mint-opacity-50';

if ( '' !== $error ) {
	$textareaClasses .= ' mint-border-danger';
}
?>
<div class="mint-space-y-2">
	<?php if ( '' !== $label ) : ?>
		<label for="<?php echo esc_attr( $id ); ?>" class="mint-block mint-text-sm mint-font-semibold mint-text-ink">
			<?php echo esc_html( $label ); ?>
			<?php if ( $required ) : ?>
				<span class="mint-text-danger" aria-hidden="true">*</span>
			<?php endif; ?>
		</label>
	<?php endif; ?>
	<textarea
		name="<?php echo esc_attr( $name ); ?>"
		id="<?php echo esc_attr( $id ); ?>"
		rows="<?php echo esc_attr( (string) $rows ); ?>"
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
		class="<?php echo esc_attr( $textareaClasses ); ?>"
		<?php echo $required ? 'required' : ''; ?>
		<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller supplies validated attributes. ?>
	><?php echo esc_textarea( $value ); ?></textarea>
	<?php if ( '' !== $error ) : ?>
		<p class="mint-text-xs mint-text-danger" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>
</div>
