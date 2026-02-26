<?php

namespace App\Containers\AppSection\File\Enums;

enum FileVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case SHARE_LINK = 'share_link';
}
