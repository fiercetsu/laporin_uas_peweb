<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\SecurityMiddleware;
use App\Utils\Response;

class GpsService
{
    private Database $db;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new AuthMiddleware();
        $this->ensureSchema();
    }

    public function update(array $input): void
    {
        $payload = $this->auth->requireAuth();
        $errors = [];

        $latitude = $this->numberInRange($input, 'latitude', -90, 90, 'Latitude', $errors);
        $longitude = $this->numberInRange($input, 'longitude', -180, 180, 'Longitude', $errors);
        $accuracy = $this->optionalNumberInRange($input, 'akurasi_gps_meter', 0, 100000, 'Akurasi GPS', $errors);
        $speed = $this->optionalNumberInRange($input, 'kecepatan_meter_per_detik', 0, 1000, 'Kecepatan', $errors);
        $heading = $this->optionalNumberInRange($input, 'arah_derajat', 0, 360, 'Arah', $errors);
        $source = $this->source((string)($input['sumber'] ?? 'device'), $errors);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $latText = number_format($latitude, 8, '.', '');
        $lngText = number_format($longitude, 8, '.', '');
        $mapsUrl = 'https://www.google.com/maps?q=' . $latText . ',' . $lngText;

        $this->db->query(
            "INSERT INTO lokasi_realtime
             (user_id, latitude, longitude, akurasi_gps_meter, kecepatan_meter_per_detik, arah_derajat,
              sumber, maps_url, ip_address, user_agent, recorded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                akurasi_gps_meter = VALUES(akurasi_gps_meter),
                kecepatan_meter_per_detik = VALUES(kecepatan_meter_per_detik),
                arah_derajat = VALUES(arah_derajat),
                sumber = VALUES(sumber),
                maps_url = VALUES(maps_url),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                recorded_at = VALUES(recorded_at),
                updated_at = NOW()",
            [
                (int)$payload['sub'],
                $latText,
                $lngText,
                $accuracy,
                $speed,
                $heading,
                $source,
                $mapsUrl,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]
        );

        Response::success($this->findByUserId((int)$payload['sub']), 'Lokasi GPS berhasil diperbarui.');
    }

    public function current(): void
    {
        $payload = $this->auth->requireAuth();
        $location = $this->findByUserId((int)$payload['sub']);

        if (!$location) {
            Response::notFound('Lokasi GPS belum tersedia.');
        }

        Response::success($location);
    }

    public function list(): void
    {
        $this->auth->requireAdmin();
        $query = SecurityMiddleware::sanitizeQuery();
        $where = ['1=1'];
        $params = [];

        if (!empty($query['role']) && in_array($query['role'], ['warga', 'petugas', 'admin', 'rt'], true)) {
            $where[] = 'u.role = ?';
            $params[] = $query['role'];
        }

        if (!empty($query['search'])) {
            $search = '%' . str_replace(['%', '_'], ['\%', '\_'], $query['search']) . '%';
            $where[] = '(u.nama_lengkap LIKE ? OR u.nik LIKE ? OR u.kode_user LIKE ?)';
            array_push($params, $search, $search, $search);
        }

        $limit = min(200, max(1, (int)($query['limit'] ?? 100)));
        $rows = $this->db->query(
            "SELECT lr.id, lr.user_id, u.kode_user, u.nik, u.nama_lengkap, u.role, u.status_akun,
                    lr.latitude, lr.longitude, lr.akurasi_gps_meter, lr.kecepatan_meter_per_detik,
                    lr.arah_derajat, lr.sumber, lr.maps_url, lr.recorded_at, lr.updated_at
             FROM lokasi_realtime lr
             JOIN users u ON u.id = lr.user_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY lr.recorded_at DESC
             LIMIT ?",
            array_merge($params, [$limit])
        )->fetchAll();

        Response::success($rows);
    }

    private function findByUserId(int $userId): ?array
    {
        $row = $this->db->query(
            "SELECT lr.id, lr.user_id, u.kode_user, u.nama_lengkap, u.role,
                    lr.latitude, lr.longitude, lr.akurasi_gps_meter, lr.kecepatan_meter_per_detik,
                    lr.arah_derajat, lr.sumber, lr.maps_url, lr.recorded_at, lr.updated_at
             FROM lokasi_realtime lr
             JOIN users u ON u.id = lr.user_id
             WHERE lr.user_id = ?
             LIMIT 1",
            [$userId]
        )->fetch();

        return $row ?: null;
    }

    private function numberInRange(array $input, string $field, float $min, float $max, string $label, array &$errors): float
    {
        $value = trim((string)($input[$field] ?? ''));

        if ($value === '') {
            $errors[$field] = $label . ' wajib diisi.';
            return 0.0;
        }

        if (!is_numeric($value) || (float)$value < $min || (float)$value > $max) {
            $errors[$field] = $label . ' tidak valid.';
            return 0.0;
        }

        return (float)$value;
    }

    private function optionalNumberInRange(array $input, string $field, float $min, float $max, string $label, array &$errors): ?float
    {
        $value = trim((string)($input[$field] ?? ''));

        if ($value === '') {
            return null;
        }

        if (!is_numeric($value) || (float)$value < $min || (float)$value > $max) {
            $errors[$field] = $label . ' tidak valid.';
            return null;
        }

        return (float)$value;
    }

    private function source(string $source, array &$errors): string
    {
        $source = trim($source) ?: 'device';
        $allowed = ['device', 'manual', 'laporan', 'petugas'];

        if (!in_array($source, $allowed, true)) {
            $errors['sumber'] = 'Sumber GPS tidak valid.';
            return 'device';
        }

        return $source;
    }

    private function ensureSchema(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS lokasi_realtime (
                id int UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id int UNSIGNED NOT NULL,
                latitude decimal(10,8) NOT NULL,
                longitude decimal(11,8) NOT NULL,
                akurasi_gps_meter float DEFAULT NULL,
                kecepatan_meter_per_detik float DEFAULT NULL,
                arah_derajat float DEFAULT NULL,
                sumber enum('device','manual','laporan','petugas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'device',
                maps_url varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                ip_address varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                user_agent text COLLATE utf8mb4_unicode_ci,
                recorded_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY user_id (user_id),
                KEY idx_recorded_at (recorded_at),
                KEY idx_koordinat (latitude, longitude),
                CONSTRAINT lokasi_realtime_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}
