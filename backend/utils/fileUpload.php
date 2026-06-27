<?php
declare(strict_types=1);

namespace App\Utils;

class FileUpload
{
    private string $root;

    public function __construct()
    {
        $this->root = dirname(__DIR__) . '/uploads';
    }

    public function fotoLaporan(array $file, string $folder = 'laporan'): array
    {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload file gagal.');
        }

        $mime = $this->detectMime($file);
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('File harus berupa JPG, PNG, atau WEBP.');
        }

        $maxMb = (int)($_ENV['MAX_UPLOAD_MB'] ?? 5);
        if ((int)$file['size'] > $maxMb * 1024 * 1024) {
            throw new \RuntimeException("Ukuran file maksimal {$maxMb} MB.");
        }

        $dir = $this->root . '/' . trim($folder, '/');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Folder upload tidak bisa dibuat.');
        }

        $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('File gagal disimpan.');
        }

        return [
            'nama_file' => $name,
            'path_file' => 'uploads/' . trim($folder, '/') . '/' . $name,
            'ukuran_file' => (int)$file['size'],
            'tipe_mime' => $mime,
        ];
    }

    private function detectMime(array $file): string
    {
        $tmpName = (string)($file['tmp_name'] ?? '');

        if ($tmpName !== '' && \function_exists('mime_content_type')) {
            $mime = \mime_content_type($tmpName);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        if ($tmpName !== '' && \function_exists('finfo_open')) {
            $finfo = \finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = \finfo_file($finfo, $tmpName);
                \finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        return (string)($file['type'] ?? '');
    }
}
