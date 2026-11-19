<?php

namespace App\Enums;

/**
 * Editorial state machine: Draft -> Under Review -> Approved -> Published.
 * The only backwards edge is "Under Review -> Draft" (returned to the author for revision).
 */
enum PaperStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Published => 'Published',
        };
    }

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::UnderReview],
            self::UnderReview => [self::Approved, self::Draft],
            self::Approved => [self::Published],
            self::Published => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-700',
            self::UnderReview => 'bg-amber-100 text-amber-800',
            self::Approved => 'bg-blue-100 text-blue-800',
            self::Published => 'bg-green-100 text-green-800',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
