<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\FileException;

final class UploadFile
{
    public static function upload(): ?string
    {
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            throw new FileException('No file was uploaded');
        }

        $file = $_FILES['file'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new FileException(match ($error) {
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by a PHP extension',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in the HTML form',
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_NO_TMP_DIR => 'Temporary folder not found',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
                default => 'Unknown upload error',
            });
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? '');
        $size = (int) ($file['size'] ?? 0);

        if ($tmpName === '' || $originalName === '' || !is_uploaded_file($tmpName)) {
            throw new FileException('Invalid uploaded file');
        }

        if ($size > 5 * 1024 * 1024) {
            throw new FileException('File exceeds upload_max_filesize');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extension, $allowed, true)) {
            throw new FileException('Invalid format file');
        }

        $mime = mime_content_type($tmpName);

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new FileException('Invalid image MIME type');
        }

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $baseName) ?? 'image';
        $fileName = trim($baseName, '-_') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;

        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new FileException('Unable to create upload directory');
        }

        $destination = $directory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            throw new FileException('Failed to write file');
        }

        return $fileName;
    }

    public static function remove(string $fileName): void
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . basename($fileName);

        if (is_file($path) && !unlink($path)) {
            throw new FileException('Unable to delete image');
        }
    }
}
