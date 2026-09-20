<?php
namespace Core;

/**
 * Uploads go to storage/uploads (outside the web root) and are served
 * through a controller that checks permissions. Never trust the client
 * filename or the client-supplied MIME type.
 */
class FileUploader
{
    private const MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'audio/webm' => 'webm',
        'audio/mpeg' => 'mp3',
    ];

    /**
     * @param array  $file    entry from $_FILES
     * @param string $folder  e.g. 'items', 'gs-letters'
     * @param array  $allowed allowed extensions
     * @param int    $maxMb
     * @return string relative path stored in the database
     */
    public static function store(array $file, string $folder, array $allowed = ['jpg','png','webp'], int $maxMb = 5): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed. Please try again.');
        }
        if ($file['size'] > $maxMb * 1024 * 1024) {
            throw new \RuntimeException("File must be smaller than {$maxMb} MB.");
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Invalid upload.');
        }

        // detect the real type from file contents, not the browser's claim
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $ext   = self::MIME[$mime] ?? null;

        if ($ext === null || !in_array($ext, $allowed, true)) {
            throw new \RuntimeException('That file type is not allowed.');
        }

        $dir = STORAGE_PATH . '/uploads/' . trim($folder, '/');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create upload folder.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "{$dir}/{$name}")) {
            throw new \RuntimeException('Could not save the file.');
        }

        return trim($folder, '/') . '/' . $name;
    }

    public static function delete(?string $relativePath): void
    {
        if (!$relativePath) return;
        $path = STORAGE_PATH . '/uploads/' . $relativePath;
        $real = realpath($path);
        // guard against ../ traversal
        if ($real && str_starts_with($real, realpath(STORAGE_PATH . '/uploads'))) {
            @unlink($real);
        }
    }
}
