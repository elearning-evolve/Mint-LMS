<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\FirstRunRedirect;
?>
<div class="wrap mint-lms-admin-wrap mint-m-0 mint-p-0">
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::open( 'admin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		include MINTLMS_PATH . 'views/admin/partials/welcome-split.php';
		?>
	<?php echo MintLMS\Infrastructure\Ui\UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
