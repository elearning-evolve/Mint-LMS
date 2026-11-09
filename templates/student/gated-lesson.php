<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$message     = $message ?? __( 'This lesson is part of a course that requires enrollment. Enroll to unlock all lessons.', 'mint-lms' );
$overviewUrl = $overviewUrl ?? home_url( '/' );
$isLoggedIn  = $isLoggedIn ?? false;
$loginUrl    = $loginUrl ?? wp_login_url();
$enrollUrl   = $enrollUrl ?? $overviewUrl;
?>
<div class="mint-flex mint-min-h-[50vh] mint-items-center mint-justify-center mint-px-4 mint-py-16">
	<div class="mint-w-full mint-max-w-md mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-p-8 mint-text-center mint-shadow-card">
		<div class="mint-mx-auto mint-mb-5 mint-flex mint-h-14 mint-w-14 mint-items-center mint-justify-center mint-rounded-xl mint-bg-hue-6">
			<svg class="mint-h-6 mint-w-6 mint-text-hue-6i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
		</div>
		<h1 class="mint-text-h2 mint-font-semibold mint-text-ink mint-tracking-tight"><?php esc_html_e( 'This lesson is locked', 'mint-lms' ); ?></h1>
		<p class="mint-mt-3 mint-text-sm mint-leading-relaxed mint-text-ink-2" role="alert"><?php echo esc_html( $message ); ?></p>
		<div class="mint-mt-6 mint-flex mint-flex-col mint-items-center mint-justify-center mint-gap-3 sm:mint-flex-row">
			<a href="<?php echo esc_url( $enrollUrl ); ?>" class="mint-inline-flex mint-w-full sm:mint-w-auto mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover mint-transition-colors mint-duration-hover mint-no-underline">
				<?php esc_html_e( 'Enroll', 'mint-lms' ); ?>
			</a>
			<?php if ( ! $isLoggedIn ) : ?>
				<a href="<?php echo esc_url( $loginUrl ); ?>" class="mint-inline-flex mint-w-full sm:mint-w-auto mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-border mint-border-control mint-bg-bg mint-text-ink hover:mint-bg-bg-hover mint-transition-colors mint-duration-hover mint-no-underline">
					<?php esc_html_e( 'Sign in', 'mint-lms' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
