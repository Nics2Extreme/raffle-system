<?php

namespace App\Enums;

enum ParticipantSource: string
{
    case Import = 'import';
    case Manual = 'manual';
}
