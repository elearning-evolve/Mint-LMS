<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit;

use MintLMS\Infrastructure\Database\Schema;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    public function test_table_names_use_mintlms_prefix(): void
    {
        $prefix = 'wp_';

        $this->assertSame('wp_mintlms_sections', Schema::sectionsTable($prefix));
        $this->assertSame('wp_mintlms_enrollments', Schema::enrollmentsTable($prefix));
        $this->assertSame('wp_mintlms_progress', Schema::progressTable($prefix));
        $this->assertSame('wp_mintlms_progress_summary', Schema::progressSummaryTable($prefix));
        $this->assertSame('wp_mintlms_quiz_questions', Schema::quizQuestionsTable($prefix));
        $this->assertSame('wp_mintlms_quiz_attempts', Schema::quizAttemptsTable($prefix));
        $this->assertSame('wp_mintlms_question_data', Schema::questionDataTable($prefix));
        $this->assertSame('wp_mintlms_id_map', Schema::idMapTable($prefix));
        $this->assertSame('wp_mintlms_courses', Schema::coursesTable($prefix));
        $this->assertSame('wp_mintlms_lessons', Schema::lessonsTable($prefix));
        $this->assertSame('wp_mintlms_quizzes', Schema::quizzesTable($prefix));
        $this->assertSame('wp_mintlms_quiz_questions_legacy', Schema::quizQuestionsLegacyTable($prefix));
    }

    public function test_all_tables_includes_active_and_legacy(): void
    {
        $tables = Schema::allTables('wp_');

        $this->assertCount(12, $tables);
        $this->assertContains('wp_mintlms_sections', $tables);
        $this->assertContains('wp_mintlms_question_data', $tables);
        $this->assertContains('wp_mintlms_quiz_questions', $tables);
        $this->assertContains('wp_mintlms_courses', $tables);
        $this->assertContains('wp_mintlms_quiz_questions_legacy', $tables);
    }
}
