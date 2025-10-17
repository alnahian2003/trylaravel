<?php

namespace App\Enums;

enum ReportType: string
{
    case Spam = 'spam';
    case Inappropriate = 'inappropriate';
    case Copyright = 'copyright';
    case Misinformation = 'misinformation';
    case Broken = 'broken';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Inappropriate => 'Inappropriate Content',
            self::Copyright => 'Copyright Violation',
            self::Misinformation => 'Misinformation',
            self::Broken => 'Broken/Not Working',
            self::Other => 'Other',
        };
    }
}
