<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Admin\CreatorDashboardService;
use MintLMS\Infrastructure\Admin\MintUiDashboardPresentation;
use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Infrastructure\Database\Repository\WpdbAdminDashboardRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository;
use MintLMS\Infrastructure\Ui\MintUi;
use MintLMS\Infrastructure\User\WpUserLookup;

$wpdb = mintlms_wpdb();
if ( null === $wpdb ) {
	wp_die( esc_html__( 'Database unavailable.', 'mint-lms' ) );
}

$renderer    = new ViewRenderer();
$userId      = get_current_user_id();
$user        = wp_get_current_user();
$showDetails = MintUi::showDetailsPreference( $userId );

// Must use CPT repository (same source as All Courses). Legacy WpdbCourseRepository
// only had a partial mirror and showed stale rows like "Untitled Course".
$dashboardService = new CreatorDashboardService(
	new WpPostCourseRepository(),
	new WpdbAdminDashboardRepository( $wpdb ),
	new MintUiDashboardPresentation(),
	new WpUserLookup(),
);

$dashboard    = $dashboardService->getDashboard( $userId );
$newCourseUrl = \MintLMS\Infrastructure\Admin\CourseBuilderPage::newCourseUrl();

// Empty dashboard: hero CTA + header New course (no search yet — nothing to find).
if ( $dashboard->isEmpty ) {
	ob_start();
	?>
	<a
		href="<?php echo esc_url( $newCourseUrl ); ?>"
		class="mint-dash-new-course mint-inline-flex mint-h-[34px] mint-items-center mint-justify-center mint-gap-[7px] mint-rounded-md mint-border-0 mint-px-[13px] mint-text-sm mint-font-semibold mint-no-underline focus-visible:mint-outline-none"
	>
		<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11"/></svg>
		<?php esc_html_e( 'New course', 'mint-lms' ); ?>
	</a>
	<?php
	$headerActions = (string) ob_get_clean();

	$content = $renderer->render(
		'admin/dashboard/_empty-content',
		array(
			'createUrl' => $newCourseUrl,
		)
	);

	$renderer->echo(
		'admin/layout',
		array(
			'content'       => $content,
			'headerActions' => $headerActions,
		)
	);
	return;
}

// Has courses: search field in header (model) — New course lives under Courses menu / lists.
ob_start();
?>
<form
	class="mint-dash-header-search"
	method="get"
	action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>"
	role="search"
>
	<input type="hidden" name="post_type" value="<?php echo esc_attr( \MintLMS\Infrastructure\PostType\PostTypes::COURSE ); ?>" />
	<label class="mint-relative mint-block">
		<span class="mint-sr-only"><?php esc_html_e( 'Search courses', 'mint-lms' ); ?></span>
		<span class="mint-pointer-events-none mint-absolute mint-left-[13px] mint-top-1/2 mint--translate-y-1/2 mint-text-ink-2" aria-hidden="true">
			<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3 3"/></svg>
		</span>
		<input
			type="search"
			name="s"
			class="mint-search-input mint-h-[38px] mint-min-w-[220px] mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-white mint-py-0 mint-pl-10 mint-pr-[13px] mint-text-[15px] mint-text-ink-2 placeholder:mint-text-ink-2 focus:mint-outline-none"
			placeholder="<?php echo esc_attr__( 'Search courses', 'mint-lms' ); ?>"
			autocomplete="off"
			value=""
		/>
	</label>
</form>
<?php
$headerActions = (string) ob_get_clean();

$content = $renderer->render(
	'admin/dashboard/_dashboard-content',
	array(
		'dashboard'   => $dashboard,
		'firstName'   => $user->first_name ?: $user->display_name,
		'showDetails' => $showDetails,
		'userId'      => $userId,
	)
);

$renderer->echo(
	'admin/layout',
	array(
		'content'       => $content,
		'headerActions' => $headerActions,
	)
);
