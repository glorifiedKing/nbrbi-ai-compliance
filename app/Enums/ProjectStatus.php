<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case DRAFT = 'draft';
    case IN_PROGRESS = 'in_progress';
    case COMPLIANT = 'compliant';
    case NON_COMPLIANT = 'non_compliant';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLIANT => 'Compliant',
            self::NON_COMPLIANT => 'Non-Compliant',
        };
    }
}
