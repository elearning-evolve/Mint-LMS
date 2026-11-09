<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$name    = $name ?? '';
$id      = $id ?? $name;
$label   = $label ?? '';
$checked = $checked ?? false;
$attrs   = $attrs ?? '';
?>
<div class="mint-flex mint-items-center mint-gap-3" x-data="{ on: <?php echo $checked ? 'true' : 'false'; ?> }">
	<button
		type="button"
		role="switch"
		:id="'<?php echo esc_js( $id ); ?>-switch'"
		:aria-checked="on.toString()"
		@click="on = !on"
		class="mint-relative mint-inline-flex mint-h-6 mint-w-11 mint-shrink-0 mint-cursor-pointer mint-rounded-full mint-border-2 mint-border-transparent mint-transition-colors focus:mint-outline-none focus:mint-ring-2 focus:mint-ring-accent focus:mint-ring-offset-2"
		:class="on ? 'mint-bg-accent' : 'mint-bg-neutral-300'"
		<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller supplies validated attributes. ?>
	>
		<span
			class="mint-pointer-events-none mint-inline-block mint-h-5 mint-w-5 mint-transform mint-rounded-full mint-bg-neutral-50 mint-shadow-sm mint-transition-transform"
			:class="on ? 'mint-translate-x-5' : 'mint-translate-x-0'"
		></span>
	</button>
	<input type="hidden" name="<?php echo esc_attr( $name ); ?>" :value="on ? '1' : '0'" />
	<?php if ( '' !== $label ) : ?>
		<label :for="'<?php echo esc_js( $id ); ?>-switch'" class="mint-text-sm mint-text-neutral-700 mint-cursor-pointer" @click="on = !on">
			<?php echo esc_html( $label ); ?>
		</label>
	<?php endif; ?>
</div>
