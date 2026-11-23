<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;

$renderer = new ViewRenderer();
$status   = isset( $_GET['status'] ) ? sanitize_key( (string) wp_unslash( $_GET['status'] ) ) : 'pending'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! in_array( $status, array( 'pending', 'graded', 'all' ), true ) ) {
	$status = 'pending';
}

$content = $renderer->render(
	'admin/essays/_grading-content',
	array(
		'initialStatus' => $status,
	)
);

$renderer->echo(
	'admin/layout',
	array(
		'pageLabel'     => __( 'Essay grading', 'mint-lms' ),
		'headerActions' => '',
		'maxWidthClass' => 'mint-max-w-wide',
		'content'       => $content,
	)
);
