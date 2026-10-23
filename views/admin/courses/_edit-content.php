<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */

$inputClass = 'mint-cs-input';
?>
<div
	x-data="courseEdit(<?php echo esc_attr( (string) $courseId ); ?>)"
	x-init="init()"
	@mint-save-course.window="save()"
	@mint-publish-course.window="publish()"
>
	<div x-show="loading" x-cloak class="mint-pt-10">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'skeleton',
			array( 'variant' => 'card' )
		);
		?>
	</div>

	<div x-show="error && !loading" x-cloak class="mint-pt-10">
		<?php
		$mintlms_retry_button = $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'button',
			array(
				'variant' => 'secondary',
				'label'   => esc_html__( 'Try again', 'mint-lms' ),
				'type'    => 'button',
				'attrs'   => '@click="init()"',
			)
		);
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'error-state',
			array(
				'title'        => esc_html__( 'Could not load course', 'mint-lms' ),
				'messageAttrs' => 'x-text="error"',
				'retry'        => $mintlms_retry_button, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer component HTML.
			)
		);
		?>
	</div>

	<div x-show="!loading && !error" x-cloak class="mint-cs-page">
		<?php
		$builderUrl = admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId );
		$treeUrl    = admin_url( 'admin.php?page=mint-lms-builder&course_id=' . $courseId . '&view=tree' );
		?>
		<nav class="mint-cs-switcher" aria-label="<?php echo esc_attr__( 'Course navigation', 'mint-lms' ); ?>">
			<a
				class="mint-cs-switcher__back"
				:href="coursesDashboardUrl()"
				@click="rememberCoursesTab(form.status)"
				title="<?php echo esc_attr__( 'Back to courses', 'mint-lms' ); ?>"
				aria-label="<?php echo esc_attr__( 'Back to courses', 'mint-lms' ); ?>"
			>
				<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 4.5-6 5.5 6 5.5" /></svg>
			</a>
			<span class="mint-cs-switcher__link is-active" aria-current="page"><?php esc_html_e( 'Course Settings', 'mint-lms' ); ?></span>
			<a class="mint-cs-switcher__link" href="<?php echo esc_url( $builderUrl ); ?>"><?php esc_html_e( 'Course Builder', 'mint-lms' ); ?></a>
			<a class="mint-cs-switcher__link" href="<?php echo esc_url( $treeUrl ); ?>"><?php esc_html_e( 'Course Hierarchy', 'mint-lms' ); ?></a>
			<span class="mint-cs-switcher__title" x-text="form.title || t('untitledCourseAlt')"></span>
		</nav>

		<nav class="mint-cs-crumb" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'mint-lms' ); ?>">
			<a :href="coursesDashboardUrl()" @click="rememberCoursesTab(form.status)"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></a>
			<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#5C5C77" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
			<span x-text="form.title || t('untitled')"></span>
		</nav>

		<h1 class="mint-cs-title"><?php esc_html_e( 'Course settings', 'mint-lms' ); ?></h1>

		<form class="mint-cs-form" @submit.prevent="save()">

			<!-- How students join -->
			<section class="mint-cs-card">
				<header class="mint-cs-card__head">
					<div class="mint-cs-card__title"><?php esc_html_e( 'How students join', 'mint-lms' ); ?></div>
					<div class="mint-cs-card__sub"><?php esc_html_e( 'Choose how students get into this course', 'mint-lms' ); ?></div>
				</header>
				<div class="mint-cs-card__body mint-cs-card__body--join">
					<div class="mint-cs-field-label"><?php esc_html_e( 'Joining method', 'mint-lms' ); ?></div>
					<div class="mint-cs-choices">
						<button type="button" class="mint-cs-choice" :class="form.enrollmentType === 'open' ? 'is-selected' : ''" @click="form.enrollmentType = 'open'">
							<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
							<span class="mint-cs-choice__copy">
								<span class="mint-cs-choice__label"><?php esc_html_e( 'Open to everyone', 'mint-lms' ); ?></span>
								<span class="mint-cs-choice__desc"><?php esc_html_e( 'Anyone can open this course. No sign-up or login is needed.', 'mint-lms' ); ?></span>
							</span>
						</button>
						<button type="button" class="mint-cs-choice" :class="form.enrollmentType === 'free' ? 'is-selected' : ''" @click="form.enrollmentType = 'free'">
							<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
							<span class="mint-cs-choice__copy">
								<span class="mint-cs-choice__label"><?php esc_html_e( 'Free sign-up', 'mint-lms' ); ?></span>
								<span class="mint-cs-choice__desc"><?php esc_html_e( 'Students create a free account and join with one click. They must log in to see the lessons.', 'mint-lms' ); ?></span>
							</span>
						</button>
						<button type="button" class="mint-cs-choice" :class="form.enrollmentType === 'paid' ? 'is-selected' : ''" @click="form.enrollmentType = 'paid'">
							<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
							<span class="mint-cs-choice__copy">
								<span class="mint-cs-choice__label"><?php esc_html_e( 'One-time payment', 'mint-lms' ); ?></span>
								<span class="mint-cs-choice__desc"><?php esc_html_e( 'Students pay once, through Stripe or PayPal, and keep access.', 'mint-lms' ); ?></span>
							</span>
						</button>
						<div x-show="form.enrollmentType === 'paid'" x-cloak class="mint-cs-nested">
							<div class="mint-cs-nested-row">
								<div class="mint-cs-nested-row__label"><?php esc_html_e( 'Price', 'mint-lms' ); ?></div>
								<div class="mint-cs-nested-row__tip" aria-hidden="true"></div>
								<div class="mint-cs-nested-row__control mint-cs-nested-row__control--row">
									<input
										id="mint-wc-price"
										type="text"
										inputmode="decimal"
										class="<?php echo esc_attr( $inputClass ); ?> mint-cs-input--sm"
										x-model="wcPrice"
										placeholder="29.00"
									/>
									<button
										type="button"
										class="mint-cs-btn-mint"
										:disabled="creatingProduct || !wcPrice || !woocommerceAvailable"
										@click="createWooProduct()"
									>
										<span x-text="commerce.productId ? t('updateProduct') : t('createProduct')"></span>
									</button>
									<template x-if="commerce.editUrl">
										<a :href="commerce.editUrl" target="_blank" rel="noopener noreferrer" class="mint-cs-link"><?php esc_html_e( 'Edit in WooCommerce', 'mint-lms' ); ?></a>
									</template>
								</div>
							</div>
							<p x-show="!woocommerceAvailable" x-cloak class="mint-cs-error"><?php esc_html_e( 'WooCommerce is not active.', 'mint-lms' ); ?></p>
							<p x-show="commerce.productId" x-cloak class="mint-cs-hint">
								<span><?php esc_html_e( 'Linked product #', 'mint-lms' ); ?></span><span x-text="commerce.productId"></span>
							</p>
						</div>
						<button type="button" class="mint-cs-choice" :class="form.enrollmentType === 'manual' ? 'is-selected' : ''" @click="form.enrollmentType = 'manual'">
							<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
							<span class="mint-cs-choice__copy">
								<span class="mint-cs-choice__label"><?php esc_html_e( 'Invite only', 'mint-lms' ); ?></span>
								<span class="mint-cs-choice__desc"><?php esc_html_e( 'Only you can add students, by hand, in a group, or through a shop or membership. No join button is shown unless you add a link.', 'mint-lms' ); ?></span>
							</span>
						</button>
					</div>
				</div>
			</section>

			<!-- Display and content options -->
			<section class="mint-cs-card">
				<header class="mint-cs-card__head">
					<div class="mint-cs-card__title"><?php esc_html_e( 'Display and content options', 'mint-lms' ); ?></div>
					<div class="mint-cs-card__sub"><?php esc_html_e( 'Choose what students see on the course page', 'mint-lms' ); ?></div>
				</header>
				<div class="mint-cs-card__body mint-cs-card__body--rows">
					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Course materials', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Add notes, links or downloads to the course page', 'mint-lms' ); ?>">?</span>
							<div class="mint-cs-row__main">
								<div class="mint-cs-toggle-line">
									<button
										type="button"
										class="mint-cs-toggle"
										:class="display.materialsEnabled ? 'is-on' : ''"
										role="switch"
										:aria-checked="display.materialsEnabled ? 'true' : 'false'"
										@click="toggleMaterialsEnabled()"
									><span class="mint-cs-toggle__knob"></span></button>
									<span class="mint-cs-toggle-hint"><?php esc_html_e( 'Any content added below is displayed on the main Course page', 'mint-lms' ); ?></span>
								</div>
								<div x-show="display.materialsEnabled" x-cloak class="mint-cs-materials">
									<div class="mint-cs-materials__actions">
										<button type="button" class="mint-cs-materials__add-media" @click="addMediaToMaterials()">
											<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="3.5" width="15" height="13" rx="3"/><circle cx="7" cy="8" r="1.4"/><path d="m4 14 4-3.5 3 2 4.5-4"/></svg>
											<?php esc_html_e( 'Add media', 'mint-lms' ); ?>
										</button>
										<div class="mint-cs-materials__view-toggle">
											<button
												type="button"
												class="mint-cs-materials__view-btn"
												:class="materialsEditorView === 'visual' ? 'is-active' : ''"
												@click="setMaterialsEditorView('visual')"
											><?php esc_html_e( 'Visual', 'mint-lms' ); ?></button>
											<button
												type="button"
												class="mint-cs-materials__view-btn"
												:class="materialsEditorView === 'code' ? 'is-active' : ''"
												@click="setMaterialsEditorView('code')"
											><?php esc_html_e( 'Code', 'mint-lms' ); ?></button>
										</div>
									</div>

									<div
										class="mint-cs-materials__frame"
										:class="materialsEditorView === 'code' ? 'is-code' : 'is-visual'"
									>
										<div
											class="mint-cs-materials__toolbar"
											x-show="materialsEditorView === 'visual'"
											x-cloak
											@click.outside="materialsFormatMenuOpen = false"
											@mousedown.prevent
										>
											<div class="mint-cs-materials__format">
												<button
													type="button"
													class="mint-cs-materials__format-trigger"
													@click="materialsFormatMenuOpen = !materialsFormatMenuOpen"
													:aria-expanded="materialsFormatMenuOpen ? 'true' : 'false'"
												>
													<span x-text="materialsFormatLabel"></span>
													<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<div class="mint-cs-materials__format-menu" x-show="materialsFormatMenuOpen" x-cloak>
													<template x-for="option in materialsFormatOptions" :key="'mat-fmt-' + option.value">
														<button
															type="button"
															class="mint-cs-materials__format-option"
															:class="materialsFormatLabel === option.label ? 'is-active' : ''"
															@click="applyMaterialsFormat(option)"
															x-text="option.label"
														></button>
													</template>
												</div>
											</div>
											<span class="mint-cs-materials__toolbar-divider" aria-hidden="true"></span>
											<button type="button" class="mint-cs-materials__tool" style="font-weight:700" @click="runMaterialsCommand('bold')" :title="t('bold')">B</button>
											<button type="button" class="mint-cs-materials__tool" style="font-weight:500;font-style:italic" @click="runMaterialsCommand('italic')" :title="t('italic')">I</button>
											<button type="button" class="mint-cs-materials__tool" @click="runMaterialsCommand('InsertUnorderedList')" :title="t('bulletList')">•</button>
											<button type="button" class="mint-cs-materials__tool" @click="runMaterialsCommand('InsertOrderedList')" :title="t('numberedList')">1.</button>
											<button type="button" class="mint-cs-materials__tool" @click="insertMaterialsLink()" :title="t('link')">🔗</button>
										</div>
										<div
											class="mint-cs-materials__toolbar mint-cs-materials__toolbar--code"
											x-show="materialsEditorView === 'code'"
											x-cloak
											@mousedown.prevent
										>
											<template x-for="tag in materialsCodeTags" :key="'mat-code-' + tag">
												<button type="button" class="mint-cs-materials__code-chip" @click="insertMaterialsCodeTag(tag)" x-text="tag"></button>
											</template>
										</div>
										<div
											id="mint_course_materials_visual"
											class="mint-cs-materials__visual"
											contenteditable="true"
											role="textbox"
											aria-multiline="true"
											x-show="materialsEditorView === 'visual'"
											x-cloak
											@input="onMaterialsVisualInput()"
											@blur="onMaterialsVisualInput()"
											:data-placeholder="t('materialsPlaceholder')"
										></div>
										<textarea
											id="mint_course_materials_code"
											class="mint-cs-materials__code"
											rows="8"
											x-show="materialsEditorView === 'code'"
											x-cloak
											@input="onMaterialsCodeInput($event)"
											:placeholder="t('materialsPlaceholder')"
										></textarea>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="mint-cs-divider"></div>

					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Who can see the lesson list', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Control whether people can see what is inside before they enrol', 'mint-lms' ); ?>">?</span>
							<div class="mint-cs-choices">
								<button type="button" class="mint-cs-choice" :class="display.lessonListVisibility === 'everyone' ? 'is-selected' : ''" @click="display.lessonListVisibility = 'everyone'">
									<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
									<span class="mint-cs-choice__copy">
										<span class="mint-cs-choice__label"><?php esc_html_e( 'Everyone', 'mint-lms' ); ?></span>
										<span class="mint-cs-choice__desc"><?php esc_html_e( 'Anyone who opens the course page can see the list of lessons.', 'mint-lms' ); ?></span>
									</span>
								</button>
								<button type="button" class="mint-cs-choice" :class="display.lessonListVisibility === 'enrolled' ? 'is-selected' : ''" @click="display.lessonListVisibility = 'enrolled'">
									<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
									<span class="mint-cs-choice__copy">
										<span class="mint-cs-choice__label"><?php esc_html_e( 'Only students who enrolled', 'mint-lms' ); ?></span>
										<span class="mint-cs-choice__desc"><?php esc_html_e( 'The lesson list stays hidden until a student enrols.', 'mint-lms' ); ?></span>
									</span>
								</button>
							</div>
						</div>
					</div>

					<div class="mint-cs-divider"></div>

					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Split long lists into pages', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Useful when your course has many lessons', 'mint-lms' ); ?>">?</span>
							<div class="mint-cs-row__main">
								<div class="mint-cs-toggle-line">
									<button
										type="button"
										class="mint-cs-toggle"
										:class="display.paginationEnabled ? 'is-on' : ''"
										role="switch"
										:aria-checked="display.paginationEnabled ? 'true' : 'false'"
										@click="display.paginationEnabled = !display.paginationEnabled"
									><span class="mint-cs-toggle__knob"></span></button>
									<span class="mint-cs-toggle-hint" x-text="display.paginationEnabled ? t('paginationOn') : t('paginationOff')"></span>
								</div>
								<div x-show="display.paginationEnabled" x-cloak class="mint-cs-nested mint-cs-nested--tight">
									<div class="mint-cs-inline-field">
										<span class="mint-cs-inline-field__label"><?php esc_html_e( 'Lessons', 'mint-lms' ); ?></span>
										<input type="number" min="1" max="100" class="<?php echo esc_attr( $inputClass ); ?> mint-cs-input--xs" x-model.number="display.lessonsPerPage" />
										<span class="mint-cs-toggle-hint"><?php esc_html_e( 'per page', 'mint-lms' ); ?></span>
									</div>
									<div class="mint-cs-inline-field">
										<span class="mint-cs-inline-field__label"><?php esc_html_e( 'Lesson groups', 'mint-lms' ); ?></span>
										<input type="number" min="1" max="100" class="<?php echo esc_attr( $inputClass ); ?> mint-cs-input--xs" x-model.number="display.groupsPerPage" />
										<span class="mint-cs-toggle-hint"><?php esc_html_e( 'per page', 'mint-lms' ); ?></span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="mint-cs-divider"></div>

					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'When a student finishes', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Choose where students go after completing the course', 'mint-lms' ); ?>">?</span>
							<div class="mint-cs-row__main">
								<input
									type="url"
									class="<?php echo esc_attr( $inputClass ); ?> mint-cs-input--md"
									x-model="display.completionRedirect"
									:placeholder="t('completionRedirectPh')"
								/>
								<p class="mint-cs-field-hint"><?php esc_html_e( 'Pick a page, for example a “Congratulations” page. Leave it empty to stay on the course.', 'mint-lms' ); ?></p>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- Access settings -->
			<section class="mint-cs-card">
				<header class="mint-cs-card__head">
					<div class="mint-cs-card__title"><?php esc_html_e( 'Access settings', 'mint-lms' ); ?></div>
					<div class="mint-cs-card__sub"><?php esc_html_e( 'Set what students must do before joining, and how long they keep access', 'mint-lms' ); ?></div>
				</header>
				<div class="mint-cs-card__body mint-cs-card__body--rows">
					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'What students need before joining', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Choose if students must finish something before they can join', 'mint-lms' ); ?>">?</span>
							<div class="mint-cs-choices">
								<button type="button" class="mint-cs-choice" :class="!form.prerequisitesEnabled ? 'is-selected' : ''" @click="form.prerequisitesEnabled = false">
									<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
									<span class="mint-cs-choice__copy">
										<span class="mint-cs-choice__label"><?php esc_html_e( 'Nothing — anyone can join', 'mint-lms' ); ?></span>
										<span class="mint-cs-choice__desc"><?php esc_html_e( 'Students can join this course without finishing anything first.', 'mint-lms' ); ?></span>
									</span>
								</button>
								<button type="button" class="mint-cs-choice" :class="form.prerequisitesEnabled ? 'is-selected' : ''" @click="form.prerequisitesEnabled = true">
									<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
									<span class="mint-cs-choice__copy">
										<span class="mint-cs-choice__label"><?php esc_html_e( 'Finish other courses first', 'mint-lms' ); ?></span>
										<span class="mint-cs-choice__desc"><?php esc_html_e( 'Students must complete the courses you pick below before they can join.', 'mint-lms' ); ?></span>
									</span>
								</button>
								<div x-show="form.prerequisitesEnabled" x-cloak class="mint-cs-nested">
									<div class="mint-cs-nested-block">
										<div class="mint-cs-nested-block__label"><?php esc_html_e( 'How many must they finish?', 'mint-lms' ); ?></div>
										<div class="mint-cs-choices">
											<button type="button" class="mint-cs-choice" :class="form.prerequisiteCompare === 'ANY' ? 'is-selected' : ''" @click="form.prerequisiteCompare = 'ANY'">
												<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
												<span class="mint-cs-choice__copy">
													<span class="mint-cs-choice__label"><?php esc_html_e( 'Any one of them', 'mint-lms' ); ?></span>
													<span class="mint-cs-choice__desc"><?php esc_html_e( 'Finishing just one of the selected courses is enough.', 'mint-lms' ); ?></span>
												</span>
											</button>
											<button type="button" class="mint-cs-choice" :class="form.prerequisiteCompare === 'ALL' ? 'is-selected' : ''" @click="form.prerequisiteCompare = 'ALL'">
												<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
												<span class="mint-cs-choice__copy">
													<span class="mint-cs-choice__label"><?php esc_html_e( 'All of them', 'mint-lms' ); ?></span>
													<span class="mint-cs-choice__desc"><?php esc_html_e( 'Students must finish every selected course.', 'mint-lms' ); ?></span>
												</span>
											</button>
										</div>
									</div>
									<div class="mint-cs-nested-block">
										<div class="mint-cs-nested-block__label"><?php esc_html_e( 'Courses to finish', 'mint-lms' ); ?></div>
										<div class="mint-cs-prereq-list">
											<template x-for="course in prerequisiteChoices" :key="course.id">
												<button
													type="button"
													class="mint-cs-prereq"
													:class="isPrerequisiteSelected(course.id) ? 'is-selected' : ''"
													@click="togglePrerequisite(course.id)"
												>
													<span class="mint-cs-prereq__check" aria-hidden="true">
														<svg x-show="isPrerequisiteSelected(course.id)" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5"/></svg>
													</span>
													<span x-text="course.title"></span>
												</button>
											</template>
											<p x-show="prerequisiteChoices.length === 0" class="mint-cs-hint"><?php esc_html_e( 'No other courses yet.', 'mint-lms' ); ?></p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="mint-cs-divider"></div>

					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Limit how long students keep access', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Access can end automatically after a number of days', 'mint-lms' ); ?>">?</span>
							<div class="mint-cs-row__main">
								<div class="mint-cs-toggle-line">
									<button
										type="button"
										class="mint-cs-toggle"
										:class="form.expireAccess ? 'is-on' : ''"
										role="switch"
										:aria-checked="form.expireAccess ? 'true' : 'false'"
										@click="form.expireAccess = !form.expireAccess"
									><span class="mint-cs-toggle__knob"></span></button>
									<span class="mint-cs-toggle-hint" x-text="form.expireAccess ? t('accessEndsAfterDays') : t('accessNoTimeLimit')"></span>
								</div>
								<div x-show="form.expireAccess" x-cloak class="mint-cs-nested mint-cs-nested--tight">
									<div class="mint-cs-inline-field">
										<span class="mint-cs-inline-field__label"><?php esc_html_e( 'Access lasts', 'mint-lms' ); ?></span>
										<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Counted from the day the student joins', 'mint-lms' ); ?>">?</span>
										<input
											id="mint-expire-days"
											type="number"
											min="1"
											max="3650"
											class="<?php echo esc_attr( $inputClass ); ?> mint-cs-input--xs"
											x-model.number="form.expireAccessDays"
										/>
										<span class="mint-cs-toggle-hint"><?php esc_html_e( 'days', 'mint-lms' ); ?></span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="mint-cs-divider"></div>

					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Course opens on', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Students can’t join before this date. Leave empty to open right away', 'mint-lms' ); ?>">?</span>
							<input id="mint-access-start" type="date" class="<?php echo esc_attr( $inputClass ); ?> mint-cs-input--date" x-model="form.accessStartAt" />
						</div>
					</div>

					<div class="mint-cs-divider"></div>

					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Course closes on', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Students can’t join after this date. Leave empty to never close', 'mint-lms' ); ?>">?</span>
							<input id="mint-access-end" type="date" class="<?php echo esc_attr( $inputClass ); ?> mint-cs-input--date" x-model="form.accessEndAt" />
						</div>
					</div>

					<div class="mint-cs-divider"></div>

					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Maximum students', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'The most students who can join. Use 0 for no limit', 'mint-lms' ); ?>">?</span>
							<input
								type="number"
								min="0"
								class="<?php echo esc_attr( $inputClass ); ?> mint-cs-input--xs"
								x-model.number="form.seatLimit"
							/>
						</div>
					</div>
				</div>
			</section>

			<!-- Rewards -->
			<section class="mint-cs-card">
				<header class="mint-cs-card__head">
					<div class="mint-cs-card__title"><?php esc_html_e( 'Rewards for finishing', 'mint-lms' ); ?></div>
					<div class="mint-cs-card__sub"><?php esc_html_e( 'Choose what students receive when they complete this course', 'mint-lms' ); ?></div>
				</header>
				<div class="mint-cs-card__body mint-cs-card__body--rows">
					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Certificate', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Students can download this certificate once they finish the course', 'mint-lms' ); ?>">?</span>
							<div class="mint-cs-row__main">
								<div class="mint-cs-select" @click.outside="certMenuOpen = false">
									<button type="button" class="mint-cs-select__btn" @click="certMenuOpen = !certMenuOpen" :class="certMenuOpen ? 'is-open' : ''">
										<span x-text="certificateLabel()"></span>
										<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + (certMenuOpen ? 180 : 0) + 'deg)' }"><path d="m5 8 5 5 5-5"/></svg>
									</button>
									<div class="mint-cs-select__menu" x-show="certMenuOpen" x-cloak>
										<button type="button" class="mint-cs-select__option" :class="!optionOn('certificate') ? 'is-selected' : ''" @click="setCertificate(false); certMenuOpen = false"><?php esc_html_e( 'No certificate', 'mint-lms' ); ?></button>
										<button type="button" class="mint-cs-select__option" :class="optionOn('certificate') ? 'is-selected' : ''" @click="setCertificate(true); certMenuOpen = false"><?php esc_html_e( 'Course completion certificate', 'mint-lms' ); ?></button>
									</div>
								</div>
								<p class="mint-cs-field-hint"><?php esc_html_e( 'Pick a certificate to give students when they finish. Leave it empty to give none.', 'mint-lms' ); ?></p>
							</div>
						</div>
					</div>

					<div class="mint-cs-divider"></div>

					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Points for finishing', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Points are added to the student’s total when they complete the course', 'mint-lms' ); ?>">?</span>
							<div class="mint-cs-row__main">
								<div class="mint-cs-inline-field">
									<input type="number" min="0" class="<?php echo esc_attr( $inputClass ); ?> mint-cs-input--xs" x-model.number="display.pointsOnComplete" placeholder="0" />
									<span class="mint-cs-toggle-hint"><?php esc_html_e( 'point(s)', 'mint-lms' ); ?></span>
								</div>
								<p class="mint-cs-field-hint"><?php esc_html_e( 'Students earn this many points when they finish. Leave empty for none.', 'mint-lms' ); ?></p>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- Progression -->
			<section class="mint-cs-card">
				<header class="mint-cs-card__head">
					<div class="mint-cs-card__title"><?php esc_html_e( 'How students move through the course', 'mint-lms' ); ?></div>
					<div class="mint-cs-card__sub"><?php esc_html_e( 'Choose if students follow your lesson order or explore on their own', 'mint-lms' ); ?></div>
				</header>
				<div class="mint-cs-card__body mint-cs-card__body--rows">
					<div class="mint-cs-row">
						<div class="mint-cs-row__label"><?php esc_html_e( 'Lesson order', 'mint-lms' ); ?></div>
						<div class="mint-cs-row__right">
							<span class="mint-cs-tip" title="<?php echo esc_attr__( 'Choose how students move through your lessons', 'mint-lms' ); ?>">?</span>
							<div class="mint-cs-choices">
								<button type="button" class="mint-cs-choice" :class="form.progression === 'linear' ? 'is-selected' : ''" @click="form.progression = 'linear'">
									<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
									<span class="mint-cs-choice__copy">
										<span class="mint-cs-choice__label"><?php esc_html_e( 'Step by step', 'mint-lms' ); ?></span>
										<span class="mint-cs-choice__desc"><?php esc_html_e( 'Students must finish each lesson before the next one opens, in the order you set. Doesn’t work with Open courses.', 'mint-lms' ); ?></span>
									</span>
								</button>
								<button type="button" class="mint-cs-choice" :class="form.progression === 'freeform' ? 'is-selected' : ''" @click="form.progression = 'freeform'">
									<span class="mint-cs-choice__radio" aria-hidden="true"><span></span></span>
									<span class="mint-cs-choice__copy">
										<span class="mint-cs-choice__label"><?php esc_html_e( 'Any order', 'mint-lms' ); ?></span>
										<span class="mint-cs-choice__desc"><?php esc_html_e( 'Students can open any lesson they like, in any order. Nothing is locked.', 'mint-lms' ); ?></span>
									</span>
								</button>
							</div>
						</div>
					</div>
				</div>
			</section>

		</form>
	</div>
</div>
