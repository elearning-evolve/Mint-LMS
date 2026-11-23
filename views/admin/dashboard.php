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

$dashboardService = new CreatorDashboardService(
	new WpPostCourseRepository(),
	new WpdbAdminDashboardRepository( $wpdb ),
	new MintUiDashboardPresentation(),
	new WpUserLookup(),
);

$dashboard    = $dashboardService->getDashboard( $userId );
$newCourseUrl = \MintLMS\Infrastructure\Admin\CourseBuilderPage::newCourseUrl();

// Empty dashboard: brand-only header — create CTAs live in the page body.
if ( $dashboard->isEmpty ) {
	$content = $renderer->render(
		'admin/dashboard/_empty-content',
		array(
			'createUrl' => \MintLMS\Infrastructure\Admin\CourseBuilderPage::titledCourseUrl(),
			'blankUrl'  => $newCourseUrl,
		)
	);

	$renderer->echo(
		'admin/layout',
		array(
			'content'           => $content,
			'showHeaderActions' => false,
			'maxWidthClass'     => 'mint-dash-zero__shell',
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
		'firstName'   => '' !== $user->first_name ? $user->first_name : $user->display_name,
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
