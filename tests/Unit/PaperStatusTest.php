<?php

namespace Tests\Unit;

use App\Enums\PaperStatus;
use PHPUnit\Framework\TestCase;

class PaperStatusTest extends TestCase
{
    public function test_the_happy_path_is_strictly_sequential(): void
    {
        $this->assertTrue(PaperStatus::Draft->canTransitionTo(PaperStatus::UnderReview));
        $this->assertTrue(PaperStatus::UnderReview->canTransitionTo(PaperStatus::Approved));
        $this->assertTrue(PaperStatus::Approved->canTransitionTo(PaperStatus::Published));
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $this->assertFalse(PaperStatus::Draft->canTransitionTo(PaperStatus::Approved));
        $this->assertFalse(PaperStatus::Draft->canTransitionTo(PaperStatus::Published));
        $this->assertFalse(PaperStatus::UnderReview->canTransitionTo(PaperStatus::Published));
    }

    public function test_published_is_terminal(): void
    {
        $this->assertTrue(PaperStatus::Published->isTerminal());
        $this->assertSame([], PaperStatus::Published->allowedTransitions());
        $this->assertFalse(PaperStatus::Published->canTransitionTo(PaperStatus::Draft));
    }

    public function test_only_under_review_can_be_returned_to_draft(): void
    {
        $this->assertTrue(PaperStatus::UnderReview->canTransitionTo(PaperStatus::Draft));
        $this->assertFalse(PaperStatus::Approved->canTransitionTo(PaperStatus::Draft));
        $this->assertFalse(PaperStatus::Approved->canTransitionTo(PaperStatus::UnderReview));
    }

    public function test_labels_and_values(): void
    {
        $this->assertSame('Under Review', PaperStatus::UnderReview->label());
        $this->assertSame(['draft', 'under_review', 'approved', 'published'], PaperStatus::values());
    }
}
