<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Plugin;

/** @var ViewRenderer $renderer */
/** @var int $courseId */
/** @var string $courseTitle */
/** @var array{students?: int, finished?: int, lesson_count?: int} $courseStats */
/** @var array{students_enrolled: int, finished: int, active_week: int, courses: int} $overviewStats */
/** @var list<array{name: string, initials: string, course: string, when: string, bg: string, ink: string}> $recentJoined */
/** @var list<array{id: int, title: string, letter: string, studentCount: int, finishedCount: int, bg: string, ink: string}> $pickerCourses */

$courseId      = isset( $courseId ) ? absint( $courseId ) : 0;
$courseTitle   = $courseTitle ?? '';
$courseStats   = $courseStats ?? array();
$overviewStats = $overviewStats ?? array(
	'students_enrolled' => 0,
	'finished'          => 0,
	'active_week'       => 0,
	'courses'           => 0,
);
$recentJoined  = $recentJoined ?? array();
$pickerCourses = $pickerCourses ?? array();

$students      = array();
$pagination    = array(
	'current'  => 1,
	'total'    => 1,
	'base_url' => admin_url( 'admin.php?page=mint-lms-course-students&course_id=' . $courseId ),
);
$error         = '';
$totalStudents = (int) ( $courseStats['students'] ?? 0 );
$totalFinished = (int) ( $courseStats['finished'] ?? 0 );
$lessonCount   = (int) ( $courseStats['lesson_count'] ?? 0 );

if ( $courseId > 0 ) {
	try {
		$userId = get_current_user_id();
		$page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = Plugin::enrollmentService()->listStudentsForCourse( $courseId, $page, 20, $userId );

		if ( $totalStudents <= 0 ) {
			$totalStudents = $result->total;
		}

		foreach ( $result->students as $index => $student ) {
			$user = get_userdata( $student->userId );
			$name = $user instanceof \WP_User ? $user->display_name : sprintf(
				/* translators: %d: user ID */
				__( 'User #%d', 'mint-lms' ),
				$student->userId
			);
			$email = $user instanceof \WP_User ? $user->user_email : '';

			$progressPct = (int) round( $student->progressPct );
			$hueIdx      = ( $index % 6 ) + 1;
			$parts       = preg_split( '/\s+/', trim( $name ) ) ?: array();
			$initials    = '';
			if ( isset( $parts[0][0] ) ) {
				$initials .= mb_strtoupper( mb_substr( $parts[0], 0, 1 ) );
			}
			if ( isset( $parts[1][0] ) ) {
				$initials .= mb_strtoupper( mb_substr( $parts[1], 0, 1 ) );
			}
			if ( '' === $initials ) {
				$initials = '?';
			}

			if ( 100 === $progressPct ) {
				$whereLabel = __( 'Finished', 'mint-lms' );
				$whereInk   = '#0A6B4C';
			} elseif ( 0 === $progressPct ) {
				$whereLabel = __( 'Not started', 'mint-lms' );
				$whereInk   = '#33334A';
			} elseif ( $lessonCount > 0 ) {
				$done       = max( 1, min( $lessonCount, (int) round( ( $progressPct / 100 ) * $lessonCount ) ) );
				$whereLabel = sprintf(
					/* translators: 1: current lesson number, 2: total lessons */
					__( 'Lesson %1$d of %2$d', 'mint-lms' ),
					$done,
					$lessonCount
				);
				$whereInk = '#33334A';
			} else {
				$whereLabel = __( 'In progress', 'mint-lms' );
				$whereInk   = '#33334A';
			}

			$students[] = array(
				'name'         => $name,
				'email'        => $email,
				'initials'     => $initials,
				'hueIdx'       => $hueIdx,
				'progressPct'  => $progressPct,
				'whereLabel'   => $whereLabel,
				'whereInk'     => $whereInk,
				'enrolled'     => wp_date( 'j M', strtotime( $student->enrolledAt ) ),
				'enrollmentId' => $student->enrollmentId,
			);
		}

		$perPage    = max( 1, $result->perPage );
		$totalPages = max( 1, (int) ceil( $result->total / $perPage ) );
		$pagination = array(
			'current'  => $result->page,
			'total'    => $totalPages,
			'base_url' => admin_url( 'admin.php?page=mint-lms-course-students&course_id=' . $courseId ),
		);

	} catch ( NotFoundException $exception ) {
		$error = $exception->getMessage();
	} catch ( ForbiddenException $exception ) {
		$error = $exception->getMessage();
	}
}

$studentsPageUrl = admin_url( 'admin.php?page=mint-lms-course-students' );
?>

<?php if ( $courseId <= 0 ) : ?>
<div class="mint-students-picker" x-data="{ search: '' }">
	<div class="mint-students-picker__intro">
		<h1 class="mint-students-picker__title"><?php echo esc_html__( 'Students', 'mint-lms' ); ?></h1>
		<p class="mint-students-picker__sub">
			<?php echo esc_html__( 'Pick a course to see who’s enrolled and how they’re doing.', 'mint-lms' ); ?>
		</p>
		<label class="mint-students-picker__search">
			<span class="mint-sr-only"><?php esc_html_e( 'Search courses', 'mint-lms' ); ?></span>
			<span class="mint-students-picker__search-icon" aria-hidden="true">
				<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3 3"/></svg>
			</span>
			<input
				type="search"
				class="mint-students-picker__search-input"
				placeholder="<?php echo esc_attr__( 'Search courses…', 'mint-lms' ); ?>"
				x-model="search"
				autocomplete="off"
			/>
		</label>
	</div>

	<div class="mint-students-picker__layout">
		<div class="mint-min-w-0">
			<?php if ( empty( $pickerCourses ) ) : ?>
				<div class="mint-students-picker__empty"><?php echo esc_html__( 'No courses found.', 'mint-lms' ); ?></div>
			<?php else : ?>
				<div class="mint-students-picker__courses">
					<?php foreach ( $pickerCourses as $course ) : ?>
						<?php
						$student_count  = (int) $course['studentCount'];
						$finished_count = (int) $course['finishedCount'];
						$finished_pct   = $student_count > 0 ? (int) round( ( $finished_count / $student_count ) * 100 ) : 0;
						$title_lower    = mb_strtolower( (string) $course['title'] );
						if ( $student_count <= 0 ) {
							$count_label = __( 'No students yet', 'mint-lms' );
						} elseif ( 1 === $student_count ) {
							$count_label = __( '1 student', 'mint-lms' );
						} else {
							$count_label = sprintf(
								/* translators: %d: student count */
								__( '%d students', 'mint-lms' ),
								$student_count
							);
						}
						$finished_label = sprintf(
							/* translators: %d: finished student count */
							__( '%d finished', 'mint-lms' ),
							$finished_count
						);
						?>
					<a
						href="<?php echo esc_url( add_query_arg( 'course_id', (int) $course['id'], $studentsPageUrl ) ); ?>"
						class="mint-students-course-card"
						data-mint-course-card
						data-mint-course-title="<?php echo esc_attr( $title_lower ); ?>"
						x-show="!search.trim() || $el.dataset.mintCourseTitle.includes(search.trim().toLowerCase())"
					>
						<div class="mint-students-course-card__row">
							<span
								class="mint-students-course-card__tile"
								style="background: <?php echo esc_attr( (string) $course['bg'] ); ?>; color: <?php echo esc_attr( (string) $course['ink'] ); ?>"
							>
								<?php echo esc_html( (string) $course['letter'] ); ?>
							</span>
							<div class="mint-students-course-card__body">
								<div class="mint-students-course-card__title"><?php echo esc_html( (string) $course['title'] ); ?></div>
								<div class="mint-students-course-card__meta"><?php echo esc_html( $count_label ); ?></div>
							</div>
							<svg class="mint-students-course-card__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#17222B" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
						</div>
						<div class="mint-students-course-card__progress">
							<div class="mint-students-course-card__track">
								<div class="mint-students-course-card__fill" style="width: <?php echo (int) $finished_pct; ?>%;"></div>
							</div>
							<div class="mint-students-course-card__finished"><?php echo esc_html( $finished_label ); ?></div>
						</div>
					</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<aside class="mint-students-picker__aside">
			<section class="mint-students-panel">
				<div class="mint-students-panel__head"><?php echo esc_html__( 'Across all courses', 'mint-lms' ); ?></div>
				<div class="mint-students-panel__stats">
					<div class="mint-students-stat" tabindex="0">
						<div class="mint-students-stat__value"><?php echo esc_html( (string) (int) $overviewStats['students_enrolled'] ); ?></div>
						<div class="mint-students-stat__label"><?php echo esc_html__( 'Students enrolled', 'mint-lms' ); ?></div>
					</div>
					<div class="mint-students-stat" tabindex="0">
						<div class="mint-students-stat__value"><?php echo esc_html( (string) (int) $overviewStats['finished'] ); ?></div>
						<div class="mint-students-stat__label"><?php echo esc_html__( 'Finished a course', 'mint-lms' ); ?></div>
					</div>
					<div class="mint-students-stat" tabindex="0">
						<div class="mint-students-stat__value"><?php echo esc_html( (string) (int) $overviewStats['active_week'] ); ?></div>
						<div class="mint-students-stat__label"><?php echo esc_html__( 'Active this week', 'mint-lms' ); ?></div>
					</div>
					<div class="mint-students-stat" tabindex="0">
						<div class="mint-students-stat__value"><?php echo esc_html( (string) (int) $overviewStats['courses'] ); ?></div>
						<div class="mint-students-stat__label"><?php echo esc_html__( 'Courses', 'mint-lms' ); ?></div>
					</div>
				</div>
			</section>

			<section class="mint-students-panel">
				<div class="mint-students-panel__head"><?php echo esc_html__( 'Recently joined', 'mint-lms' ); ?></div>
				<div class="mint-students-panel__recent">
					<?php if ( empty( $recentJoined ) ) : ?>
						<p class="mint-students-picker__empty"><?php echo esc_html__( 'No recent enrollments yet.', 'mint-lms' ); ?></p>
					<?php else : ?>
						<?php foreach ( $recentJoined as $recent ) : ?>
							<div class="mint-students-recent" tabindex="0">
								<span
									class="mint-students-recent__avatar"
									style="background: <?php echo esc_attr( $recent['bg'] ); ?>; color: <?php echo esc_attr( $recent['ink'] ); ?>"
								>
									<?php echo esc_html( $recent['initials'] ); ?>
								</span>
								<div class="mint-students-recent__body">
									<div class="mint-students-recent__name"><?php echo esc_html( $recent['name'] ); ?></div>
									<div class="mint-students-recent__course"><?php echo esc_html( $recent['course'] ); ?></div>
								</div>
								<div class="mint-students-recent__when"><?php echo esc_html( $recent['when'] ); ?></div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</section>
		</aside>
	</div>
</div>

<?php elseif ( '' !== $error ) : ?>
<div class="mint-py-12">
	<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'error-state',
			array(
				'title'   => esc_html__( 'Unable to load students', 'mint-lms' ),
				'message' => esc_html( $error ),
			)
		);
	?>
</div>

<?php else : ?>
<div
	class="mint-students-course"
	x-data="{
		search: '',
		matches(row) {
			if (!this.search) return true;
			const q = this.search.toLowerCase();
			const name = (row.dataset.mintStudentName || '').toLowerCase();
			const email = (row.dataset.mintStudentEmail || '').toLowerCase();
			return name.includes(q) || email.includes(q);
		}
	}"
>
	<a href="<?php echo esc_url( $studentsPageUrl ); ?>" class="mint-students-course__back">
		<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 4.5-6 5.5 6 5.5"/></svg>
		<?php echo esc_html__( 'All courses', 'mint-lms' ); ?>
	</a>

	<div class="mint-students-course__hero">
		<div>
			<h1 class="mint-students-course__title">
				<?php echo esc_html( '' !== $courseTitle ? $courseTitle : __( 'Students', 'mint-lms' ) ); ?>
			</h1>
			<p class="mint-students-course__sub">
				<?php echo esc_html__( 'Everyone enrolled in this course', 'mint-lms' ); ?>
			</p>
		</div>
		<div class="mint-students-course__pills">
			<div class="mint-students-course__pill">
				<div class="mint-students-course__pill-value"><?php echo esc_html( (string) $totalStudents ); ?></div>
				<div class="mint-students-course__pill-label"><?php echo esc_html__( 'Enrolled', 'mint-lms' ); ?></div>
			</div>
			<div class="mint-students-course__pill">
				<div class="mint-students-course__pill-value"><?php echo esc_html( (string) $totalFinished ); ?></div>
				<div class="mint-students-course__pill-label"><?php echo esc_html__( 'Finished', 'mint-lms' ); ?></div>
			</div>
		</div>
	</div>

	<div class="mint-students-course__toolbar">
		<label class="mint-students-course__search">
			<span class="mint-sr-only"><?php esc_html_e( 'Search students', 'mint-lms' ); ?></span>
			<span class="mint-students-course__search-icon" aria-hidden="true">
				<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="5.5"/><path d="m13.2 13.2 3 3"/></svg>
			</span>
			<input
				type="search"
				class="mint-students-course__search-input"
				placeholder="<?php echo esc_attr__( 'Search students', 'mint-lms' ); ?>"
				x-model="search"
				autocomplete="off"
			/>
		</label>
	</div>

	<div class="mint-students-course__add">
		<div class="mint-students-course__add-title"><?php echo esc_html__( 'Add a student', 'mint-lms' ); ?></div>
		<div class="mint-students-course__add-row">
			<input
				id="mint-enroll-email"
				type="text"
				class="mint-students-course__add-input"
				placeholder="<?php echo esc_attr__( 'Email address or user ID', 'mint-lms' ); ?>"
			/>
			<button
				type="button"
				id="mint-enroll-student"
				class="mint-cta-lime mint-inline-flex mint-h-[50px] mint-shrink-0 mint-items-center mint-justify-center mint-rounded-[10px] mint-border-0 mint-px-6 mint-text-[17px] mint-font-bold"
				data-mint-course="<?php echo esc_attr( (string) $courseId ); ?>"
			>
				<?php echo esc_html__( 'Add to course', 'mint-lms' ); ?>
			</button>
		</div>
		<p class="mint-students-course__add-hint">
			<?php echo esc_html__( 'They’ll get an email with a link to start this course.', 'mint-lms' ); ?>
		</p>
	</div>

	<?php if ( empty( $students ) ) : ?>
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ViewRenderer returns escaped component HTML.
			'empty-state',
			array(
				'title'       => esc_html__( 'No students enrolled', 'mint-lms' ),
				'description' => esc_html__( 'Enroll students manually or share an open enrollment link.', 'mint-lms' ),
			)
		);
		?>
	<?php else : ?>
		<div class="mint-students-table">
			<div class="mint-students-table__head">
				<div class="mint-students-table__head-cell"><?php echo esc_html__( 'Student', 'mint-lms' ); ?></div>
				<div class="mint-students-table__head-cell"><?php echo esc_html__( 'Where they are', 'mint-lms' ); ?></div>
				<div class="mint-students-table__head-cell"><?php echo esc_html__( 'Progress', 'mint-lms' ); ?></div>
				<div class="mint-students-table__head-cell mint-students-table__head-cell--right"><?php echo esc_html__( 'Joined', 'mint-lms' ); ?></div>
				<div></div>
			</div>

			<?php foreach ( $students as $s ) : ?>
				<?php $hue = \MintLMS\Infrastructure\Ui\MintUi::hueByIndex( ( (int) $s['hueIdx'] ) - 1 ); ?>
			<div
				class="mint-students-table__row"
				data-mint-student-row
				data-mint-student-name="<?php echo esc_attr( $s['name'] ); ?>"
				data-mint-student-email="<?php echo esc_attr( $s['email'] ); ?>"
				data-mint-student-progress="<?php echo (int) $s['progressPct']; ?>%"
				data-mint-student-joined="<?php echo esc_attr( $s['enrolled'] ); ?>"
				x-show="matches($el)"
			>
				<div class="mint-students-table__person">
					<span
						class="mint-students-table__avatar"
						style="background: <?php echo esc_attr( $hue['bg'] ); ?>; color: <?php echo esc_attr( $hue['ink'] ); ?>"
					>
						<?php echo esc_html( $s['initials'] ); ?>
					</span>
					<div class="mint-min-w-0">
						<div class="mint-students-table__name"><?php echo esc_html( $s['name'] ); ?></div>
						<div class="mint-students-table__email"><?php echo esc_html( $s['email'] ); ?></div>
					</div>
				</div>

				<div class="mint-students-table__where" style="color: <?php echo esc_attr( $s['whereInk'] ); ?>">
					<?php echo esc_html( $s['whereLabel'] ); ?>
				</div>

				<div class="mint-students-table__progress">
					<div class="mint-students-table__track">
						<div class="mint-students-table__fill" style="width: <?php echo (int) $s['progressPct']; ?>%;"></div>
					</div>
					<span class="mint-students-table__pct"><?php echo (int) $s['progressPct']; ?>%</span>
				</div>

				<div class="mint-students-table__joined"><?php echo esc_html( $s['enrolled'] ); ?></div>

				<button
					type="button"
					class="mint-students-table__remove"
					data-mint-unenroll="<?php echo esc_attr( (string) $s['enrollmentId'] ); ?>"
					data-mint-course="<?php echo esc_attr( (string) $courseId ); ?>"
				>
					<?php echo esc_html__( 'Remove', 'mint-lms' ); ?>
				</button>
			</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $pagination['total'] > 1 ) : ?>
		<div class="mint-mt-7 mint-flex mint-items-center mint-justify-center mint-gap-2">
			<?php for ( $p = 1; $p <= $pagination['total']; $p++ ) : ?>
				<?php if ( $p === $pagination['current'] ) : ?>
					<span class="mint-pointer-events-none mint-inline-flex mint-h-control-md mint-w-control-md mint-items-center mint-justify-center mint-rounded-md mint-bg-cta mint-text-sm mint-font-semibold mint-text-cta-ink">
						<?php echo (int) $p; ?>
					</span>
				<?php else : ?>
					<a
						href="<?php echo esc_url( add_query_arg( 'paged', $p, $pagination['base_url'] ) ); ?>"
						class="mint-inline-flex mint-h-control-md mint-min-w-control-md mint-items-center mint-justify-center mint-rounded-md mint-bg-transparent mint-px-[14px] mint-text-sm mint-font-semibold mint-text-ink-2 mint-no-underline hover:mint-bg-bg-subtle"
					>
						<?php echo (int) $p; ?>
					</a>
				<?php endif; ?>
			<?php endfor; ?>
		</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php endif; ?>
