<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var string $studentName */
/** @var string $courseTitle */

$studentName = $studentName ?? '';
$courseTitle = $courseTitle ?? '';
?>
<p><?php echo esc_html( sprintf( /* translators: %s: student display name */ __( 'Hi %s,', 'mint-lms' ), $studentName ) ); ?></p>
<p>
	<?php
	echo esc_html(
		sprintf(
			/* translators: %s: course title */
			__( 'Good news — %s is now live. You can start learning whenever you are ready.', 'mint-lms' ),
			$courseTitle
		)
	);
	?>
</p>
