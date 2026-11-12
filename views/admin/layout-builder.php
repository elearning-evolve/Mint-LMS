<?php
declare(strict_types=1);
defined( 'ABSPATH' ) || exit;

$content = $content ?? '';
?>
<div class="wrap mint-lms-admin-wrap mint-m-0 mint-p-0 mint-overflow-hidden">
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::open( 'admin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
