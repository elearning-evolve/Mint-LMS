<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$studentName    = $studentName ?? '';
$courseTitle    = $courseTitle ?? '';
$certificateUrl = $certificateUrl ?? '';
?>
<p>
<?php
echo esc_html(
	sprintf(
	/* translators: 1: student name */
		__( 'Congratulations, %1$s!', 'mint-lms' ),
		$studentName
	)
);
?>
</p>
<p>
<?php
echo esc_html(
	sprintf(
	/* translators: %s: course title */
		__( 'You have completed %s.', 'mint-lms' ),
		$courseTitle
	)
);
?>
</p>
<?php if ( '' !== $certificateUrl ) : ?>
	<p>
		<a href="<?php echo esc_url( $certificateUrl ); ?>">
			<?php esc_html_e( 'Download your certificate', 'mint-lms' ); ?>
		</a>
	</p>
<?php endif; ?>
