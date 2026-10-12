<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

use MintLMS\Infrastructure\Admin\ViewRenderer;

$renderer  = new ViewRenderer();
$addNewUrl = admin_url( 'post-new.php?post_type=mint-quiz' );

$content = $renderer->render(
	'admin/quizzes/_list-content',
	array(
		'renderer'  => $renderer,
		'addNewUrl' => $addNewUrl,
	)
);

ob_start();
?>
<label class="mint-relative mint-block">
	<span class="mint-sr-only"><?php esc_html_e( 'Search quizzes', 'mint-lms' ); ?></span>
	<span class="mint-pointer-events-none mint-absolute mint-left-[13px] mint-top-1/2 mint--translate-y-1/2 mint-text-ink-2" aria-hidden="true">
		<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3 3"/></svg>
	</span>
	<input
		type="search"
		class="mint-search-input mint-h-[38px] mint-min-w-[220px] mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-white mint-py-0 mint-pl-10 mint-pr-[13px] mint-text-[15px] mint-text-ink-2 placeholder:mint-text-ink-2 focus:mint-outline-none"
		placeholder="<?php echo esc_attr__( 'Search quizzes', 'mint-lms' ); ?>"
		autocomplete="off"
	/>
</label>
<a
	href="<?php echo esc_url( $addNewUrl ); ?>"
	class="mint-cta-lime mint-inline-flex mint-h-[38px] mint-items-center mint-justify-center mint-gap-2 mint-rounded-md mint-border-0 mint-px-[15px] mint-text-[15px] mint-font-semibold mint-no-underline focus-visible:mint-outline-none"
>
	<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11"/></svg>
	<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
</a>
<?php
$headerActions = ob_get_clean();

$renderer->echo(
	'admin/layout',
	array(
		'pageLabel'     => __( 'Quizzes', 'mint-lms' ),
		'headerActions' => $headerActions,
		'maxWidthClass' => 'mint-max-w-content',
		'content'       => $content,
	)
);
