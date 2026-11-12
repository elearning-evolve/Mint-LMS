<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
?>
<div
	x-data="coursesList()"
	x-init="init()"
>
	<div class="mint-flex mint-flex-wrap mint-items-end mint-justify-between mint-gap-6 mint-pb-8 mint-pt-14">
		<div>
			<h1 class="mint-m-0 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></h1>
			<p class="mint-m-0 mint-mt-3 mint-text-[19px] mint-leading-[30px] mint-text-ink-2" x-cloak x-show="!loading && !error" x-text="summaryLine()"></p>
		</div>
		<div class="mint-flex mint-flex-nowrap mint-items-center mint-gap-1 mint-overflow-x-auto" role="tablist">
			<template x-for="tab in filterTabs()" :key="tab.value">
				<button
					type="button"
					role="tab"
					class="mint-h-9 mint-shrink-0 mint-cursor-pointer mint-rounded-md mint-border-[1.5px] mint-border-transparent mint-bg-transparent mint-px-3 mint-text-[15px] mint-font-medium mint-text-ink-2 mint-transition-all mint-duration-hover hover:mint-bg-[#F4F3F8] hover:mint-text-ink"
					@click="setFilter(tab.value)"
					:aria-selected="statusFilter === tab.value"
					:class="{ 'mint-border-ink mint-bg-white mint-font-semibold mint-text-ink': statusFilter === tab.value }"
					x-text="tab.label"
				></button>
			</template>
		</div>
	</div>

	<div x-show="loading" x-cloak class="mint-mt-1">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'skeleton',
			array( 'lines' => 5 )
		);
		?>
	</div>

	<div x-show="error && !loading" x-cloak class="mint-mt-1">
		<?php
		$mintlms_retry_button = $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'button',
			array(
				'variant' => 'secondary',
				'label'   => esc_html__( 'Try again', 'mint-lms' ),
				'type'    => 'button',
				'attrs'   => '@click="loadCourses(page)"',
			)
		);
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'error-state',
			array(
				'title'        => esc_html__( 'Could not load courses', 'mint-lms' ),
				'messageAttrs' => 'x-text="error"',
				'retry'        => $mintlms_retry_button, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer component HTML.
			)
		);
		?>
	</div>

	<div x-show="!loading && !error" x-cloak>
		<div>
			<div class="mint-overflow-x-auto">
				<div class="mint-courses-grid mint-mt-1 mint-rounded-[10px] mint-bg-[#F6F4FD] mint-px-[14px] mint-py-[13px]">
					<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Course', 'mint-lms' ); ?></div>
					<div class="mint-text-right mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Students', 'mint-lms' ); ?></div>
					<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Finished', 'mint-lms' ); ?></div>
					<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Status', 'mint-lms' ); ?></div>
					<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Instructor', 'mint-lms' ); ?></div>
					<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Author', 'mint-lms' ); ?></div>
					<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Course categories', 'mint-lms' ); ?></div>
					<div class="mint-text-[15px] mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Last Modified', 'mint-lms' ); ?></div>
				</div>

				<template x-for="(course, idx) in courses" :key="course.id">
					<div
						class="mint-courses-row mint-courses-grid mint-cursor-pointer mint-rounded-md mint-border-t mint-border-[#EDEBF7] mint-px-[14px] mint-py-[14px] mint-outline-none mint-transition-colors mint-duration-hover"
						tabindex="0"
						@mouseenter="hoverRow = course.id"
						@mouseleave="hoverRow = null"
					>
						<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
							<a
								:href="wpEditUrl(course.id)"
								class="mint-flex mint-h-[46px] mint-w-[46px] mint-shrink-0 mint-items-center mint-justify-center mint-rounded-xl mint-text-[19px] mint-font-bold mint-tracking-[-0.02em] mint-no-underline"
								:class="hueClass(idx)"
								x-text="course.title ? course.title.charAt(0).toUpperCase() : '?'"
							></a>
							<div class="mint-min-w-[340px] mint-flex-1">
								<div class="mint-flex mint-items-center mint-gap-2">
									<a
										:href="wpEditUrl(course.id)"
										class="mint-whitespace-nowrap mint-text-h3 mint-font-semibold mint-tracking-[-0.01em] mint-text-ink mint-no-underline"
										x-text="course.title"
									></a>
									<svg
										x-show="hoverRow === course.id"
										x-cloak
										width="15"
										height="15"
										viewBox="0 0 20 20"
										fill="none"
										stroke="#0B4F3F"
										stroke-width="1.8"
										stroke-linecap="round"
										stroke-linejoin="round"
										class="mint-shrink-0"
										aria-hidden="true"
									><path d="M13.5 3.5 16.5 6.5 6.5 16.5 3 17l0.5-3.5Z"/></svg>
								</div>

								<div
									x-show="hoverRow === course.id && course.status !== 'trashed'"
									x-cloak
									class="mint-course-actions"
								>
									<a :href="wpEditUrl(course.id)" class="mint-course-action" @click.stop><?php esc_html_e( 'Edit', 'mint-lms' ); ?></a>
									<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
									<a :href="builderUrl(course.id)" class="mint-course-action" @click.stop><?php esc_html_e( 'Open in builder', 'mint-lms' ); ?></a>
									<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
									<button type="button" class="mint-course-action mint-course-action--danger" @click.stop="trashCourse(course.id)"><?php esc_html_e( 'Trash', 'mint-lms' ); ?></button>
									<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
									<a :href="viewUrl(course.id)" class="mint-course-action" target="_blank" rel="noopener noreferrer" @click.stop><?php esc_html_e( 'View', 'mint-lms' ); ?></a>
									<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
									<a :href="previewUrl(course.id)" class="mint-course-action" target="_blank" rel="noopener noreferrer" @click.stop><?php esc_html_e( 'Preview', 'mint-lms' ); ?></a>
									<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
									<button type="button" class="mint-course-action" @click.stop="duplicateCourse(course.id)"><?php esc_html_e( 'Clone', 'mint-lms' ); ?></button>
									<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
									<a :href="builderUrl(course.id)" class="mint-course-action" @click.stop><?php esc_html_e( 'Hierarchy', 'mint-lms' ); ?></a>
								</div>

								<div
									x-show="hoverRow === course.id && course.status === 'trashed'"
									x-cloak
									class="mint-course-actions"
								>
									<button type="button" class="mint-course-action" @click.stop="restoreCourse(course.id)"><?php esc_html_e( 'Restore', 'mint-lms' ); ?></button>
									<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
									<button type="button" class="mint-course-action mint-course-action--danger" @click.stop="deleteCourse(course.id)"><?php esc_html_e( 'Delete Permanently', 'mint-lms' ); ?></button>
									<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
									<button type="button" class="mint-course-action" @click.stop="duplicateCourse(course.id)"><?php esc_html_e( 'Clone', 'mint-lms' ); ?></button>
									<span class="mint-text-[#C4C1D6]" aria-hidden="true">|</span>
									<a :href="builderUrl(course.id)" class="mint-course-action" @click.stop><?php esc_html_e( 'Hierarchy', 'mint-lms' ); ?></a>
								</div>

								<div
									x-show="hoverRow !== course.id"
									class="mint-mt-[3px] mint-truncate mint-text-[15px] mint-text-ink-2"
									x-text="courseMeta(course)"
								></div>
							</div>
						</div>

						<div class="mint-tabular-nums mint-text-right mint-text-[17px] mint-font-medium mint-text-ink" x-text="studentsLabel(course)"></div>

						<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-2.5">
							<div class="mint-h-[9px] mint-min-w-[44px] mint-flex-1 mint-overflow-hidden mint-rounded-full mint-bg-[#E7E4F3]">
								<div
									class="mint-h-full mint-rounded-full"
									:style="'width:' + finishedWidth(course) + ';background:' + finishedBar(course)"
								></div>
							</div>
							<div
								class="mint-w-11 mint-shrink-0 mint-tabular-nums mint-text-right mint-text-base mint-font-semibold"
								:style="'color:' + finishedInk(course)"
								x-text="finishedLabel(course)"
							></div>
						</div>

						<div class="mint-min-w-0">
							<span :class="statusBadgeClass(course.status)" x-text="statusLabel(course.status)"></span>
						</div>

						<div class="mint-truncate mint-whitespace-nowrap mint-text-base mint-text-ink-2" x-text="course.instructor || course.authorName || '—'"></div>
						<div class="mint-truncate mint-whitespace-nowrap mint-text-base mint-text-ink-2" x-text="course.authorName || '—'"></div>
						<div class="mint-truncate mint-whitespace-nowrap mint-text-base mint-text-ink-2" x-text="course.category || 'None'"></div>
						<div class="mint-truncate mint-whitespace-nowrap mint-text-sm mint-text-ink-2" x-text="formatRelative(course.updatedAt)"></div>
					</div>
				</template>
			</div>

			<div class="mint-border-t mint-border-[#E0DDEB] mint-pb-16 mint-pt-[18px] mint-text-base mint-text-ink-2">
				<?php esc_html_e( 'Showing all', 'mint-lms' ); ?> <span x-text="total"></span> <?php esc_html_e( 'courses', 'mint-lms' ); ?>
			</div>

			<template x-if="isEmptyCourses()">
				<div class="mint-px-6 mint-py-[72px] mint-text-center">
					<div class="mint-text-[20px] mint-font-bold mint-leading-tight mint-text-ink"><?php esc_html_e( 'No courses yet', 'mint-lms' ); ?></div>
					<div class="mint-mt-2.5 mint-text-[17px] mint-leading-snug mint-text-ink-2"><?php esc_html_e( 'Create your first course to start building your curriculum.', 'mint-lms' ); ?></div>
					<button
						type="button"
						class="mint-cta-lime mint-mt-6 mint-inline-flex mint-h-12 mint-cursor-pointer mint-items-center mint-justify-center mint-rounded-[10px] mint-border-0 mint-px-[22px] mint-text-base mint-font-bold focus:mint-outline-none disabled:mint-cursor-not-allowed disabled:mint-opacity-45"
						:disabled="creating"
						@click="createCourse()"
					><?php esc_html_e( 'Create your first course', 'mint-lms' ); ?></button>
				</div>
			</template>

			<template x-if="isEmptyTrash()">
				<div class="mint-rounded-[10px] mint-bg-[#F6F4FD] mint-px-4 mint-py-[14px] mint-text-[15px] mint-text-ink-2">
					<?php esc_html_e( 'No Courses found in trash', 'mint-lms' ); ?>
				</div>
			</template>

			<div x-show="totalPages > 1 && courses.length > 0" class="mint-flex mint-items-center mint-justify-between mint-pb-16">
				<p class="mint-m-0 mint-text-sm mint-text-ink-2">
					<?php esc_html_e( 'Page', 'mint-lms' ); ?> <span x-text="page"></span> <?php esc_html_e( 'of', 'mint-lms' ); ?> <span x-text="totalPages"></span>
				</p>
				<div class="mint-flex mint-gap-2">
					<?php
					echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
						'button',
						array(
							'variant' => 'secondary',
							'size'    => 'sm',
							'label'   => esc_html__( 'Previous', 'mint-lms' ),
							'type'    => 'button',
							'attrs'   => '@click.prevent="loadCourses(page - 1)" :disabled="page <= 1"',
						)
					);
					echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
						'button',
						array(
							'variant' => 'secondary',
							'size'    => 'sm',
							'label'   => esc_html__( 'Next', 'mint-lms' ),
							'type'    => 'button',
							'attrs'   => '@click.prevent="loadCourses(page + 1)" :disabled="page >= totalPages"',
						)
					);
					?>
				</div>
			</div>
		</div>
	</div>
</div>
