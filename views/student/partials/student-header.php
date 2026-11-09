<?php
declare(strict_types=1);
defined( 'ABSPATH' ) || exit;

$pageTitle    = $pageTitle ?? '';
$user         = wp_get_current_user();
$userName     = $userName ?? ( '' !== (string) $user->display_name ? (string) $user->display_name : '' );
$userInitials = $userInitials ?? MintLMS\Infrastructure\Ui\MintUi::initials( $userName );
$huePalette   = array(
	array(
		'bg'   => 'mint-bg-hue-2',
		'text' => 'mint-text-hue-2i',
	),
);
$hue          = $huePalette[0];
?>
<header class="mint-flex mint-items-center mint-justify-between mint-py-4 mint-mb-6 mint-border-b mint-border-rule">
	<div class="mint-text-sm mint-font-medium mint-text-ink-2"><?php echo esc_html( $pageTitle ); ?></div>
	<div class="mint-flex mint-items-center mint-gap-3">
		<?php if ( '' !== $userName ) : ?>
			<span class="mint-hidden sm:mint-inline mint-text-sm mint-text-ink-2"><?php echo esc_html( $userName ); ?></span>
		<?php endif; ?>
		<div class="mint-flex mint-h-10 mint-w-10 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-text-sm mint-font-semibold <?php echo esc_attr( $hue['bg'] . ' ' . $hue['text'] ); ?>">
			<?php echo esc_html( $userInitials ); ?>
		</div>
	</div>
</header>
