<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Infrastructure\Admin;

use MintLMS\Infrastructure\Admin\SettingsSurface;
use PHPUnit\Framework\TestCase;

final class SettingsSurfaceTest extends TestCase
{
	public function test_enforced_surfaces_stay_enabled(): void
	{
		$this->assertTrue(SettingsSurface::enabled('quiz.time_limit'));
		$this->assertTrue(SettingsSurface::enabled('lesson.video_progression'));
		$this->assertTrue(SettingsSurface::enabled('course.student_complete'));
	}

	public function test_unfinished_surfaces_are_hidden(): void
	{
		$this->assertFalse(SettingsSurface::enabled('quiz.access_card'));
		$this->assertFalse(SettingsSurface::enabled('lesson.assignment_uploads'));
		$this->assertFalse(SettingsSurface::enabled('course.points_on_complete'));
		$this->assertFalse(SettingsSurface::enabled('question.allow_html'));
	}
}
