<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Logger;

/**
 * Streams files out of storage/uploads, which sits outside the web root.
 *
 * These are identity documents — NIC scans, registration certificates, blood
 * bank licences. They are never public. A file is readable only by the account
 * that uploaded it, or by an administrator reviewing that account.
 */
class FileController extends Controller
{
    private const MIME = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'pdf' => 'application/pdf',
    ];

    public function serve(string $path): void
    {
        $path = urldecode($path);

        if (!$this->mayRead($path)) {
            $this->abort(403, 'You do not have permission to view this file.');
        }

        $this->stream($path);
    }

    /**
     * Send a stored upload to the browser.
     *
     * Public so a controller that has already established the caller may see
     * the file — the admin review screen, for one — can reuse it. It performs
     * no permission check of its own: the caller must have done that.
     */
    public function stream(string $path): void
    {
        // Only ever a folder and a generated filename. Anything with a slash,
        // a backslash or a dot segment is a traversal attempt, not a real path.
        if (!preg_match('#^[a-z0-9\-]+/[a-z0-9]+\.[a-z0-9]+$#i', $path)) {
            Logger::error("Rejected upload path: {$path}");
            $this->abort(404);
        }

        $full = STORAGE_PATH . '/uploads/' . $path;
        if (!is_file($full)) {
            $this->abort(404);
        }

        $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = self::MIME[$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($full));
        header('Content-Disposition: inline; filename="' . basename($full) . '"');
        // Private: these must not be cached by a proxy shared between users.
        header('Cache-Control: private, max-age=600');
        header('X-Content-Type-Options: nosniff');

        readfile($full);
        exit;
    }

    /** An admin may read any upload; everyone else only their own. */
    private function mayRead(string $path): bool
    {
        if (role_is(ROLE_ADMIN)) {
            return true;
        }

        $userId = (int) auth()['id'];

        $owner = Database::value(
            "SELECT 1 FROM users WHERE id = ? AND (nic_document_path = ? OR avatar_path = ?)
             UNION SELECT 1 FROM organizations    WHERE user_id = ? AND (document_path = ? OR logo_path = ?)
             UNION SELECT 1 FROM grama_niladharis WHERE user_id = ? AND document_path = ?
             UNION SELECT 1 FROM blood_banks      WHERE user_id = ? AND document_path = ?
             LIMIT 1",
            [$userId, $path, $path, $userId, $path, $path, $userId, $path, $userId, $path]
        );

        return (bool) $owner;
    }
}
