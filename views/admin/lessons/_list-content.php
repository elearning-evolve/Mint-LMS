<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
$addNewUrl = $addNewUrl ?? admin_url( 'post-new.php?post_type=mint-lesson' );
?>
<div
	x-data="lessonsList()"
	x-init="init()"
>
	<div class="mint-flex mint-flex-wrap mint-items-end mint-justify-between mint-gap-6 mint-pb-8 mint-pt-14">
		<div>
			<h1 class="mint-m-0 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink"><?php esc_html_e( 'Lessons', 'mint-lms' ); ?></h1>
			<p class="mint-m-0 mint-mt-3 mint-text-[19px] mint-leading-[30px] mint-text-ink-2" x-cloak x-show="!loading && !error" x-text="summaryLine()"></p>
		</div>
		<div class="mint-flex mint-flex-nowrap mint-items-center mint-gap-1 mint-overflow-x-auto" role="tablist">
			<template x-for="tab in filterTabs()" :key="tab.value">
				<button
					type="button"
					role="tab"
					class="mint-status-filter"
					@click="setFilter(tab.value)"
					:aria-selected="statusFilter === tab.value"
					:class="statusFilter === tab.value ? 'is-active' : ''"
					x-text="tab.label"
				></button>
			</template>
		</div>
	</div>

	<div x-show="loading" x-cloak class="mint-mt-1">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'skeleton',
			array( 'lines' => 5 )
		);
		?>
	</div>

	<div x-show="error && !loading" x-cloak class="mint-mt-1">
		<?php
		$mintlms_retry_button = $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'button',
			array(
				'variant' => 'secondary',
				'label'   => esc_html__( 'Try again', 'mint-lms' ),
				'type'    => 'button',
				'attrs'   => '@click="loadItems(page)"',
			)
		);
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'error-state',
			array(
				'title'        => esc_html__( 'Could not load lessons', 'mint-lms' ),
				'messageAttrs' => 'x-text="error"',
				'retry'        => $mintlms_retry_button, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			)
		);
		?>
	</div>

	<div x-show="!loading && !error" x-cloak>
		<div
			class="mint-content-grid mint-mt-1 mint-rounded-[10px] mint-bg-[#F6F4FD] mint-px-4 mint-py-[13px]"
			style="display:grid;grid-template-columns:minmax(0,1fr) 160px 200px;align-items:center;gap:16px"
		>
			<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Title', 'mint-lms' ); ?></div>
			<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Author', 'mint-lms' ); ?></div>
			<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Date', 'mint-lms' ); ?></div>
		</div>

		<template x-for="item in items" :key="item.id">
			<div
				class="mint-content-row mint-content-grid mint-cursor-pointer mint-rounded-md mint-border-t mint-border-[#EDEBF7] mint-px-4 mint-py-[14px] mint-outline-none mint-transition-colors mint-duration-hover"
				style="display:grid;grid-template-columns:minmax(0,1fr) 160px 200px;align-items:center;gap:16px"
				tabindex="0"
				@mouseenter="hoverRow = item.id"
				@mouseleave="hoverRow = null"
				@click="window.location.href = editUrl(item)"
			>
				<div class="mint-min-w-0">
					<div class="mint-truncate mint-whitespace-nowrap mint-text-[17px] mint-font-semibold mint-tracking-[-0.01em] mint-text-ink" x-text="item.title"></div>
					<div
						x-show="hoverRow === item.id && item.status !== 'trashed'"
						x-cloak
						class="mint-course-actions"
						@click.stop
					>
						<a :href="editUrl(item)" class="mint-course-action"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></a>
						<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
						<a :href="mintBuilderUrl(item)" class="mint-course-action" x-text="mintBuilderLabel()"></a>
						<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
						<button type="button" class="mint-course-action mint-course-action--danger" @click="trashItem(item)"><?php esc_html_e( 'Trash', 'mint-lms' ); ?></button>
						<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
						<a :href="viewUrl(item)" class="mint-course-action"><?php esc_html_e( 'View', 'mint-lms' ); ?></a>
					</div>
					<div
						x-show="hoverRow === item.id && item.status === 'trashed'"
						x-cloak
						class="mint-course-actions"
						@click.stop
					>
						<button type="button" class="mint-course-action" @click="restoreItem(item)"><?php esc_html_e( 'Restore', 'mint-lms' ); ?></button>
						<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
						<button type="button" class="mint-course-action mint-course-action--danger" @click="deleteItem(item)"><?php esc_html_e( 'Delete Permanently', 'mint-lms' ); ?></button>
					</div>
					<div
						x-show="hoverRow !== item.id"
						class="mint-mt-[3px] mint-truncate mint-text-[15px] mint-text-ink-2"
						x-text="itemMeta(item)"
					></div>
				</div>
				<div class="mint-truncate mint-whitespace-nowrap mint-text-base mint-text-ink-2" x-text="item.authorName"></div>
				<div class="mint-truncate mint-whitespace-nowrap mint-text-[15px] mint-text-ink-2" x-text="item.date"></div>
			</div>
		</template>

		<div class="mint-border-t mint-border-[#E0DDEB] mint-pb-16 mint-pt-[18px] mint-text-base mint-text-ink-2">
			<?php esc_html_e( 'Showing all', 'mint-lms' ); ?> <span x-text="total"></span> <?php esc_html_e( 'lessons', 'mint-lms' ); ?>
		</div>

		<template x-if="isEmpty()">
			<div class="mint-px-6 mint-py-[72px] mint-text-center">
				<div class="mint-text-[20px] mint-font-bold mint-leading-tight mint-text-ink"><?php esc_html_e( 'No lessons yet', 'mint-lms' ); ?></div>
				<div class="mint-mt-2.5 mint-text-[17px] mint-leading-snug mint-text-ink-2"><?php esc_html_e( 'Add your first lesson to start building content.', 'mint-lms' ); ?></div>
				<a
					href="<?php echo esc_url( $addNewUrl ); ?>"
					class="mint-cta-lime mint-mt-6 mint-inline-flex mint-h-12 mint-items-center mint-justify-center mint-rounded-[10px] mint-border-0 mint-px-[22px] mint-text-base mint-font-bold mint-no-underline"
				><?php esc_html_e( 'Add Lesson', 'mint-lms' ); ?></a>
			</div>
		</template>

		<template x-if="isEmptyTrash()">
			<div class="mint-rounded-[10px] mint-bg-[#F6F4FD] mint-px-4 mint-py-[14px] mint-text-[15px] mint-text-ink-2">
				<?php esc_html_e( 'No Lessons found in trash', 'mint-lms' ); ?>
			</div>
		</template>

		<div x-show="totalPages > 1 && items.length > 0" class="mint-flex mint-items-center mint-justify-between mint-pb-16">
			<p class="mint-m-0 mint-text-sm mint-text-ink-2">
				<?php esc_html_e( 'Page', 'mint-lms' ); ?> <span x-text="page"></span> <?php esc_html_e( 'of', 'mint-lms' ); ?> <span x-text="totalPages"></span>
			</p>
			<div class="mint-flex mint-gap-2">
				<?php
				echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'button',
					array(
						'variant' => 'secondary',
						'size'    => 'sm',
						'label'   => esc_html__( 'Previous', 'mint-lms' ),
						'type'    => 'button',
						'attrs'   => '@click.prevent="loadItems(page - 1)" :disabled="page <= 1"',
					)
				);
				echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'button',
					array(
						'variant' => 'secondary',
						'size'    => 'sm',
						'label'   => esc_html__( 'Next', 'mint-lms' ),
						'type'    => 'button',
						'attrs'   => '@click.prevent="loadItems(page + 1)" :disabled="page >= totalPages"',
					)
				);
				?>
			</div>
		</div>
	</div>
</div>
