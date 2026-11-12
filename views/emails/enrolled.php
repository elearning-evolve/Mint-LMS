<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$studentName = $studentName ?? '';
$courseTitle = $courseTitle ?? '';
?>
<p>
<?php
echo esc_html(
	sprintf(
	/* translators: 1: student name, 2: course title */
		__( 'Hi %1$s,', 'mint-lms' ),
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
		__( 'You are now enrolled in %s. Log in to your dashboard to start learning.', 'mint-lms' ),
		$courseTitle
	)
);
?>
</p>
