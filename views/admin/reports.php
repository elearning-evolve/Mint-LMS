<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Infrastructure\Database\Repository\WpdbAdminDashboardRepository;
use MintLMS\Plugin;

$wpdb = mintlms_wpdb();
if ( null === $wpdb ) {
	wp_die( esc_html__( 'Database unavailable.', 'mint-lms' ) );
}

$renderer = new ViewRenderer();
$userId   = get_current_user_id();
$canAll   = Plugin::authorization()->canViewAllCourses( $userId );
$authorId = $canAll ? null : $userId;

$repo      = new WpdbAdminDashboardRepository( $wpdb );
$dashboard = $repo->getReportsDashboard( $authorId );

$headerActions = '<button type="button" class="mint-cta-lime mint-inline-flex mint-h-[38px] mint-items-center mint-justify-center mint-gap-2 mint-rounded-lg mint-border-0 mint-px-[15px] mint-text-[15px] mint-font-semibold" onclick="window.dispatchEvent(new CustomEvent(\'mint-export-report\'))">'
	. '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 3.5v9M6.5 9.5 10 13l3.5-3.5M4 16h12"/></svg>'
	. esc_html__( 'Export report', 'mint-lms' )
	. '</button>';

$content = $renderer->render(
	'admin/reports/_reports-content',
	array(
		'dashboard' => $dashboard,
	)
);

$renderer->echo(
	'admin/layout',
	array(
		'pageLabel'     => __( 'Reports', 'mint-lms' ),
		'headerActions' => $headerActions,
		'maxWidthClass' => 'mint-max-w-wide mint-reports-main',
		'content'       => $content,
	)
);
