<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$id = $id ?? 'mint-toast-container';
?>
<div
	id="<?php echo esc_attr( $id ); ?>"
	x-data="mintToastContainer()"
	x-on:mint-toast.window="add($event.detail)"
	class="mint-toast-stack"
	aria-live="polite"
	aria-atomic="true"
>
	<template x-for="toast in toasts" :key="toast.id">
		<div
			x-show="toast.visible"
			x-transition
			class="mint-toast"
			:class="{ 'mint-toast--error': toast.type === 'error' }"
			role="status"
		>
			<span class="mint-toast__message" x-text="toast.message"></span>
			<button
				type="button"
				class="mint-toast__dismiss"
				@click="dismiss(toast.id)"
				aria-label="<?php echo esc_attr__( 'Dismiss', 'mint-lms' ); ?>"
			>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	</template>
</div>
