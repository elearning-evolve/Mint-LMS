<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$id      = $id ?? 'mint-modal';
$title   = $title ?? '';
$body    = $body ?? '';
$footer  = $footer ?? '';
$open    = $open ?? false;
?>
<div
	x-data="{ open: <?php echo $open ? 'true' : 'false'; ?> }"
	x-on:mint-open-modal.window="if ($event.detail.id === '<?php echo esc_js( $id ); ?>') open = true"
	x-on:mint-close-modal.window="if ($event.detail.id === '<?php echo esc_js( $id ); ?>') open = false"
	x-on:keydown.escape.window="open = false"
>
	<div
		x-show="open"
		x-cloak
		class="mint-fixed mint-inset-0 mint-z-50 mint-flex mint-items-center mint-justify-center mint-p-4"
		role="dialog"
		aria-modal="true"
		:aria-labelledby="'<?php echo esc_js( $id ); ?>-title'"
	>
		<div
			x-show="open"
			x-transition:enter="mint-transition-opacity mint-duration-200"
			x-transition:enter-start="mint-opacity-0"
			x-transition:enter-end="mint-opacity-100"
			x-transition:leave="mint-transition-opacity mint-duration-150"
			x-transition:leave-start="mint-opacity-100"
			x-transition:leave-end="mint-opacity-0"
			class="mint-fixed mint-inset-0 mint-bg-neutral-900/50"
			@click="open = false"
		></div>
		<div
			x-show="open"
			x-transition:enter="mint-transition-all mint-duration-200"
			x-transition:enter-start="mint-opacity-0 mint-scale-95"
			x-transition:enter-end="mint-opacity-100 mint-scale-100"
			x-transition:leave="mint-transition-all mint-duration-150"
			x-transition:leave-start="mint-opacity-100 mint-scale-100"
			x-transition:leave-end="mint-opacity-0 mint-scale-95"
			class="mint-relative mint-w-full mint-max-w-lg mint-bg-neutral-50 mint-rounded-lg mint-shadow-md mint-border mint-border-neutral-200"
		>
			<?php if ( '' !== $title ) : ?>
				<div class="mint-flex mint-items-center mint-justify-between mint-px-6 mint-py-4 mint-border-b mint-border-neutral-200">
					<h2 id="<?php echo esc_attr( $id ); ?>-title" class="mint-text-lg mint-font-medium mint-text-neutral-900">
						<?php echo esc_html( $title ); ?>
					</h2>
					<button
						type="button"
						class="mint-p-1 mint-text-neutral-500 hover:mint-text-neutral-700 mint-rounded-md"
						@click="open = false"
						aria-label="<?php echo esc_attr__( 'Close', 'mint-lms' ); ?>"
					>
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $body ) : ?>
				<div class="mint-px-6 mint-py-4 mint-text-sm mint-text-neutral-600">
					<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $footer ) : ?>
				<div class="mint-flex mint-items-center mint-justify-end mint-gap-3 mint-px-6 mint-py-4 mint-border-t mint-border-neutral-200">
					<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
