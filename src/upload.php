<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

class UploadValidationException extends RuntimeException
{
}

function contract_upload_max_size_label(): string
{
    $megabytes = (int)(CONTRACT_MAX_UPLOAD_BYTES / 1024 / 1024);
    return $megabytes . ' MB';
}

function contract_upload_safe_supplier(string $supplier): string
{
    $safeSupplier = preg_replace('/[^A-Za-z0-9_-]/', '_', $supplier);
    if (!is_string($safeSupplier) || $safeSupplier === '') {
        return 'vertrag';
    }

    return $safeSupplier;
}

function contract_upload_folder(int $contractId, string $supplier): string
{
    return rtrim(CONTRACT_UPLOAD_BASE_PATH, '/\\') . '/' . $contractId . '_' . contract_upload_safe_supplier($supplier);
}

function contract_upload_ensure_folder(string $contractFolder): void
{
    if (!is_dir($contractFolder) && !mkdir($contractFolder, 0775, true) && !is_dir($contractFolder)) {
        app_log('upload_folder_create', $contractFolder);
        throw new RuntimeException('Upload folder create failed');
    }
}

function contract_upload_allowed_mime_types(string $extension): array
{
    $allowedMimeByExt = [
        'pdf' => ['application/pdf', 'application/x-pdf', 'application/octet-stream'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];

    return $allowedMimeByExt[$extension] ?? [];
}

function contract_upload_detect_mime(string $tmpName): string
{
    $mime = '';
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo !== false) {
        $mime = (string)finfo_file($finfo, $tmpName);
        finfo_close($finfo);
    }

    if ($mime === '' && function_exists('mime_content_type')) {
        $mime = (string)(mime_content_type($tmpName) ?: '');
    }

    return $mime;
}

function contract_upload_validate_file(
    int $fileError,
    string $tmpName,
    string $originalName,
    int $fileSize,
    array $allowedExtensions,
    string $allowedExtensionsLabel,
    string $logContext
): array {
    if ($fileError === UPLOAD_ERR_NO_FILE) {
        throw new UploadValidationException('Bitte eine Datei auswählen.');
    }

    if ($fileError !== UPLOAD_ERR_OK) {
        throw new UploadValidationException('Fehler beim Datei-Upload.');
    }

    if ($fileSize <= 0 || $fileSize > CONTRACT_MAX_UPLOAD_BYTES) {
        throw new UploadValidationException('Datei fehlt oder ist größer als ' . contract_upload_max_size_label() . '.');
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new UploadValidationException('Erlaubt sind nur ' . $allowedExtensionsLabel . '.');
    }

    $mime = contract_upload_detect_mime($tmpName);
    if ($mime === '') {
        app_log($logContext . '_mime_empty', 'file=' . $originalName . ';ext=' . $extension);
    } elseif (!in_array($mime, contract_upload_allowed_mime_types($extension), true)) {
        app_log($logContext . '_mime_invalid', 'file=' . $originalName . ';ext=' . $extension . ';mime=' . $mime);
        throw new UploadValidationException('Ungültiger Dateityp erkannt.');
    }

    return [
        'extension' => $extension,
        'mime' => $mime,
        'original_name' => $originalName,
        'size' => $fileSize,
        'tmp_name' => $tmpName,
    ];
}

function contract_upload_target_file_name(string $supplier, string $extension, ?int $index = null): string
{
    $fileName = contract_upload_safe_supplier($supplier) . '_' . date('Ymd_His');
    if ($index !== null) {
        $fileName .= '_' . $index;
    }

    return $fileName . '.' . $extension;
}

function contract_upload_move_file(string $tmpName, string $targetPath): void
{
    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Upload move failed');
    }

    if (!is_file($targetPath)) {
        throw new RuntimeException('Upload target missing after move');
    }
}
