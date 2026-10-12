<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

$name     = $name ?? '';
$id       = $id ?? $name;
$label    = $label ?? '';
$value    = $value ?? '';
$options  = $options ?? array();
$required = $required ?? false;
$error    = $error ?? '';
$attrs    = $attrs ?? '';

$selectClasses = 'mint-block mint-h-control-md mint-w-full mint-rounded-md mint-border mint-border-control mint-bg-bg mint-px-3 mint-text-base mint-text-ink mint-outline-none hover:mint-border-mint-blue focus:mint-border-mint-blue focus:mint-shadow-focus disabled:mint-opacity-50';

if ( '' !== $error ) {
	$selectClasses .= ' mint-border-danger';
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
	<select
		name="<?php echo esc_attr( $name ); ?>"
		id="<?php echo esc_attr( $id ); ?>"
		class="<?php echo esc_attr( $selectClasses ); ?>"
		<?php echo $required ? 'required' : ''; ?>
		<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller supplies validated attributes. ?>
	>
		<?php foreach ( $options as $optionValue => $optionLabel ) : ?>
			<option value="<?php echo esc_attr( (string) $optionValue ); ?>" <?php selected( (string) $value, (string) $optionValue ); ?>>
				<?php echo esc_html( (string) $optionLabel ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php if ( '' !== $error ) : ?>
		<p class="mint-text-xs mint-text-danger" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>
</div>
