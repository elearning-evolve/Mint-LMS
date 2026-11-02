<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$id     = $id ?? 'mint-dropdown';
$label  = $label ?? '';
$items  = $items ?? array();
$align  = $align ?? 'left';
?>
<div
	x-data="{ open: false }"
	x-on:click.outside="open = false"
	x-on:keydown.escape.window="open = false"
	class="mint-relative mint-inline-block"
	id="<?php echo esc_attr( $id ); ?>"
>
	<button
		type="button"
		class="mint-inline-flex mint-items-center mint-gap-2 mint-px-3 mint-py-2 mint-text-sm mint-font-medium mint-text-neutral-700 mint-bg-neutral-50 mint-border mint-border-neutral-300 mint-rounded-md hover:mint-bg-neutral-100"
		@click="open = !open"
		:aria-expanded="open.toString()"
		aria-haspopup="true"
	>
		<?php echo esc_html( $label ); ?>
		<svg class="mint-h-4 mint-w-4 mint-text-neutral-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
			<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
		</svg>
	</button>
	<div
		x-show="open"
		x-cloak
		x-transition
		class="mint-absolute mint-z-10 mint-mt-1 mint-min-w-[10rem] mint-bg-neutral-50 mint-border mint-border-neutral-200 mint-rounded-md mint-shadow-md mint-py-1 <?php echo 'right' === $align ? 'mint-right-0' : 'mint-left-0'; ?>"
		role="menu"
	>
		<?php foreach ( $items as $item ) : ?>
			<?php
			$itemLabel = $item['label'] ?? '';
			$itemHref  = $item['href'] ?? '#';
			$itemAttrs = $item['attrs'] ?? '';
			?>
			<a
				href="<?php echo esc_url( $itemHref ); ?>"
				class="mint-block mint-px-4 mint-py-2 mint-text-sm mint-text-neutral-700 hover:mint-bg-neutral-100"
				role="menuitem"
				@click="open = false"
				<?php echo $itemAttrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
				<?php echo esc_html( $itemLabel ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</div>
