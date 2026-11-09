<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$createUrl  = $createUrl ?? \MintLMS\Infrastructure\Admin\CourseBuilderPage::newCourseUrl();
$exampleUrl = $exampleUrl ?? apply_filters( 'mint_lms_see_example_url', '' );
?>
<div class="mint-dash-empty">
	<div class="mint-dash-empty__icon" aria-hidden="true">
		<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="4.5" width="17" height="15" rx="3"/><path d="M9 4.5v15M13 9.5h4M13 13.5h4"/></svg>
	</div>
	<div class="mint-text-display mint-font-semibold mint-tracking-tight mint-text-ink">
		<?php
		echo wp_kses(
			__( "Let's build your<br />first course", 'mint-lms' ),
			array( 'br' => array() )
		);
		?>
	</div>
	<p class="mint-mt-4 mint-text-body mint-text-ink-3"><?php esc_html_e( 'Most teachers finish theirs in under 30 minutes.', 'mint-lms' ); ?></p>
	<div class="mint-dash-empty__actions">
		<a href="<?php echo esc_url( $createUrl ); ?>" class="mint-dash-empty__cta"><?php esc_html_e( 'Create your first course', 'mint-lms' ); ?></a>
		<?php if ( is_string( $exampleUrl ) && '' !== $exampleUrl ) : ?>
			<a href="<?php echo esc_url( $exampleUrl ); ?>" class="mint-dash-empty__secondary" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'See an example', 'mint-lms' ); ?></a>
		<?php else : ?>
			<a href="<?php echo esc_url( $createUrl ); ?>" class="mint-dash-empty__secondary"><?php esc_html_e( 'See an example', 'mint-lms' ); ?></a>
		<?php endif; ?>
	</div>
</div>
