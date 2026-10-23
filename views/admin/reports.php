<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

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

$repo    = new WpdbAdminDashboardRepository( $wpdb );
$summary = $repo->getReportsSummary( $authorId );

$content = $renderer->render(
	'admin/reports/_reports-content',
	array(
		'summary' => $summary,
	)
);

$renderer->echo(
	'admin/layout',
	array(
		'pageLabel' => __( 'Reports', 'mint-lms' ),
		'content'   => $content,
	)
);
