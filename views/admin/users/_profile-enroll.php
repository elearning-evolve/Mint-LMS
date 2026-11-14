<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * @var string $bootJson
 */
$bootJson = is_string( $bootJson ?? null ) ? $bootJson : '{}';
?>
<div
	class="mint-user-courses"
	x-data="mintUserCourseEnroll(<?php echo esc_attr( $bootJson ); ?>)"
	x-init="init()"
>
	<h2 class="mint-user-courses__title"><?php esc_html_e( 'User Enrolled in Courses', 'mint-lms' ); ?></h2>

	<table class="mint-user-courses__table" :class="busy ? 'is-busy' : ''">
		<tbody>
			<tr>
				<td class="mint-user-courses__pane">
					<input
						type="search"
						class="mint-user-courses__search"
						placeholder="<?php echo esc_attr__( 'Search All Courses', 'mint-lms' ); ?>"
						autocomplete="off"
						x-model="leftQuery"
					/>
					<select
						class="mint-user-courses__select"
						multiple
						size="12"
						x-ref="leftSelect"
						x-html="leftOptionsHtml()"
						@change="captureLeft()"
						@dblclick="assignSelected()"
					></select>
				</td>
				<td class="mint-user-courses__arrows">
					<button
						type="button"
						class="mint-user-courses__arrow"
						:title="selectedLeft.length ? '<?php echo esc_js( __( 'Enroll in selected courses', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'First select a course from the table', 'mint-lms' ) ); ?>'"
						:aria-label="selectedLeft.length ? '<?php echo esc_js( __( 'Enroll in selected courses', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'First select a course from the table', 'mint-lms' ) ); ?>'"
						@click="assignSelected()"
					>
						<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
					</button>
					<button
						type="button"
						class="mint-user-courses__arrow"
						:title="selectedRight.length ? '<?php echo esc_js( __( 'Remove from selected courses', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'First select an enrolled course from the table', 'mint-lms' ) ); ?>'"
						:aria-label="selectedRight.length ? '<?php echo esc_js( __( 'Remove from selected courses', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'First select an enrolled course from the table', 'mint-lms' ) ); ?>'"
						@click="unassignSelected()"
					>
						<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
					</button>
				</td>
				<td class="mint-user-courses__pane">
					<input
						type="search"
						class="mint-user-courses__search"
						placeholder="<?php echo esc_attr__( 'Search Enrolled Courses', 'mint-lms' ); ?>"
						autocomplete="off"
						x-model="rightQuery"
					/>
					<select
						class="mint-user-courses__select"
						multiple
						size="12"
						x-ref="rightSelect"
						x-html="rightOptionsHtml()"
						@change="captureRight()"
						@dblclick="unassignSelected()"
					></select>
				</td>
			</tr>
		</tbody>
	</table>
</div>
