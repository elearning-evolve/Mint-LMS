<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Frontend;

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Frontend\StudentCopy;
use PHPUnit\Framework\TestCase;

final class StudentCopyTest extends TestCase
{
    public function test_lock_message_localizes_progression(): void
    {
        $this->assertSame(
            'Finish the previous lesson first.',
            StudentCopy::lockMessage(ForbiddenException::REASON_PROGRESSION, 'Finish the previous lesson first.')
        );
    }

    public function test_lock_message_localizes_drip_days(): void
    {
        $this->assertSame(
            'Available in 3 days',
            StudentCopy::lockMessage(ForbiddenException::REASON_DRIP, 'Available in 3 days')
        );
    }

    public function test_localize_content_outline_rebuilds_meta_labels(): void
    {
        $outline = array(
            array(
                'id' => 1,
                'title' => 'Section',
                'meta' => 'IGNORE ME',
                'lessons' => array(
                    array(
                        'id' => 10,
                        'title' => 'Lesson A',
                        'meta' => 'IGNORE',
                        'accessible' => true,
                        'quizzes' => array(
                            array(
                                'id' => 20,
                                'title' => 'Quiz',
                                'meta' => 'IGNORE',
                                'questions' => array(
                                    array('id' => 30, 'text' => 'Untitled question'),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $localized = StudentCopy::localizeContentOutline($outline);

        $this->assertSame('1 lesson', $localized[0]['meta']);
        $this->assertSame('1 quiz', $localized[0]['lessons'][0]['meta']);
        $this->assertSame('Quiz', $localized[0]['lessons'][0]['quizzes'][0]['title']);
        $this->assertSame('1 question', $localized[0]['lessons'][0]['quizzes'][0]['meta']);
        $this->assertSame('Untitled question', $localized[0]['lessons'][0]['quizzes'][0]['questions'][0]['text']);
    }
}
