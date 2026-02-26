<?php

namespace App\Containers\AppSection\File\Enums;

enum FileType: string
{
    public const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public const VIDEO_MIME_TYPES = [
        'video/mp4',
        'video/mpeg',
        'video/x-flv',
        'video/quicktime', //mov
    ];

    public const ARCHIVE_MIME_TYPES = [
        'application/zip',
        'application/x-7z-compressed',
        'application/x-rar-compressed',
        'application/x-tar',
        'application/x-gzip',
        'application/x-bzip2',
        'application/x-compressed',
        'application/x-compressed-tar',
        'application/x-compressed-zip',
        'application/x-compressed-rar',
    ];

    public const PDF_MIME_TYPES = [
        'application/pdf',
    ];

    public const DOCUMENT_MIME_TYPES = [
        'text/csv',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel.addin.macroEnabled.12',
        'application/vnd.ms-excel.sheet.macroEnabled.12',
        'application/vnd.ms-excel.template.macroEnabled.12',
        'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'application/vnd.ms-powerpoint.template.macroEnabled.12',
        'application/vnd.ms-word.document.macroEnabled.12',
        'application/vnd.ms-word.template.macroEnabled.12',
    ];

    case IMAGE = 'image';
    case VIDEO = 'video';
    case ARCHIVE = 'archive';
    case PDF = 'pdf';
    case DOCUMENT = 'document';


    public static function getAllowedMimeTypes(): array
    {
        return array_merge(
            self::IMAGE_MIME_TYPES,
            self::VIDEO_MIME_TYPES,
            self::ARCHIVE_MIME_TYPES,
            self::PDF_MIME_TYPES,
            self::DOCUMENT_MIME_TYPES,
        );
    }

    public static function getMimeTypes(string|self $type): array
    {
        if ($type instanceof self) {
            $type = $type->value;
        }
        return match ($type) {
            self::IMAGE->value => self::IMAGE_MIME_TYPES,
            self::VIDEO->value => self::VIDEO_MIME_TYPES,
            self::ARCHIVE->value => self::ARCHIVE_MIME_TYPES,
            self::PDF->value => self::PDF_MIME_TYPES,
            self::DOCUMENT->value => self::DOCUMENT_MIME_TYPES,
            default => [],
        };
    }
}
