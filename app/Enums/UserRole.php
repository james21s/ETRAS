<?php

namespace App\Enums;

enum UserRole: string
{
    case Executive = 'executive';   // Executive Committee Head
    case Editor = 'editor';         // Managing Editor
    case Reviewer = 'reviewer';     // Industry Expert Reviewer
    case Author = 'author';         // Academic/Technical Author
    case Subscriber = 'subscriber'; // Corporate Subscriber Member

    public function label(): string
    {
        return match ($this) {
            self::Executive => 'Executive Committee Head',
            self::Editor => 'Managing Editor',
            self::Reviewer => 'Industry Expert Reviewer',
            self::Author => 'Author',
            self::Subscriber => 'Corporate Subscriber',
        };
    }

    /** Roles a visitor may pick for themselves at registration. */
    public static function selfRegistrable(): array
    {
        return [self::Author, self::Subscriber];
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
