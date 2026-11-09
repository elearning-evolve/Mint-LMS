<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$lines   = max( 1, (int) ( $lines ?? 3 ) );
$variant = $variant ?? 'text';
$attrs   = $attrs ?? '';
?>
<div class="mint-animate-pulse mint-space-y-3" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( 'card' === $variant ) : ?>
		<div class="mint-h-32 mint-w-full mint-rounded-xl mint-bg-bg-subtle"></div>
	<?php elseif ( 'avatar' === $variant ) : ?>
		<div class="mint-flex mint-items-center mint-gap-3">
			<div class="mint-h-10 mint-w-10 mint-rounded-full mint-bg-bg-subtle"></div>
			<div class="mint-flex-1 mint-space-y-2">
				<div class="mint-h-3 mint-w-1/3 mint-rounded mint-bg-bg-subtle"></div>
				<div class="mint-h-3 mint-w-1/2 mint-rounded mint-bg-bg-subtle"></div>
			</div>
		</div>
	<?php else : ?>
		<?php for ( $i = 0; $i < $lines; $i++ ) : ?>
			<div class="mint-h-3 mint-rounded mint-bg-bg-subtle <?php echo 0 === $i % 3 ? 'mint-w-full' : ( 1 === $i % 3 ? 'mint-w-5/6' : 'mint-w-4/6' ); ?>"></div>
		<?php endfor; ?>
	<?php endif; ?>
</div>
