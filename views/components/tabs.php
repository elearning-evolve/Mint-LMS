<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$tabs   = $tabs ?? array();
$active = $active ?? ( array_key_first( $tabs ) ?? '' );
$id     = $id ?? 'mint-tabs';
?>
<div x-data="{ active: '<?php echo esc_js( (string) $active ); ?>' }" id="<?php echo esc_attr( $id ); ?>">
	<div role="tablist" class="mint-flex mint-gap-1 mint-border-b mint-border-neutral-200">
		<?php foreach ( $tabs as $tabKey => $tab ) : ?>
			<?php
			$tabLabel = is_array( $tab ) ? ( $tab['label'] ?? '' ) : (string) $tab;
			$tabKey   = (string) $tabKey;
			?>
			<button
				type="button"
				role="tab"
				:id="'<?php echo esc_js( $id ); ?>-tab-<?php echo esc_js( $tabKey ); ?>'"
				:aria-selected="active === '<?php echo esc_js( $tabKey ); ?>'"
				@click="active = '<?php echo esc_js( $tabKey ); ?>'"
				class="mint-px-4 mint-py-2 mint-text-sm mint-font-medium mint-border-b-2 mint-transition-colors -mint-mb-px"
				:class="active === '<?php echo esc_js( $tabKey ); ?>' ? 'mint-border-accent mint-text-accent' : 'mint-border-transparent mint-text-neutral-500 hover:mint-text-neutral-700'"
			>
				<?php echo esc_html( $tabLabel ); ?>
			</button>
		<?php endforeach; ?>
	</div>
	<div class="mint-mt-4">
		<?php foreach ( $tabs as $tabKey => $tab ) : ?>
			<?php
			$tabContent = is_array( $tab ) ? ( $tab['content'] ?? '' ) : '';
			$tabKey     = (string) $tabKey;
			?>
			<div
				role="tabpanel"
				x-show="active === '<?php echo esc_js( $tabKey ); ?>'"
				x-cloak
				:id="'<?php echo esc_js( $id ); ?>-panel-<?php echo esc_js( $tabKey ); ?>'"
				:aria-labelledby="'<?php echo esc_js( $id ); ?>-tab-<?php echo esc_js( $tabKey ); ?>'"
			>
				<?php echo $tabContent; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
