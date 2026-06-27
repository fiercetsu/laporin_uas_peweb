<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Db\Database;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Validator;

class ProfileService
{
    private Database $db;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new AuthMiddleware();
    }

    public function me(): void
    {
        $payload = $this->auth->requireAuth();
        $user = $this->db->query(
            "SELECT u.id, u.kode_user, u.nik, u.nama_lengkap, u.email, u.no_hp, u.role, u.status_akun, u.foto_profil,
                    pw.no_kk, pw.no_rt, pw.no_rw, pw.alamat_lengkap, pw.kelurahan, pw.kecamatan, pw.kota_kabupaten,
                    pw.tempat_lahir, pw.tanggal_lahir, pw.jenis_kelamin, pw.agama, pw.status_perkawinan, pw.pekerjaan,
                    pw.status_tinggal, pw.tanggal_pindah_masuk
             FROM users u LEFT JOIN profil_warga pw ON pw.user_id = u.id WHERE u.id = ? LIMIT 1",
            [$payload['sub']]
        )->fetch();
        Response::success($user);
    }

    public function update(array $input): void
    {
        $payload = $this->auth->requireAuth();
        $v = new Validator($input);
        $v->email('email')->noHp('no_hp')->minLength('nama_lengkap', 3)->maxLength('nama_lengkap', 150);
        if ($v->fails()) {
            Response::validationError($v->getErrors());
        }

        $this->db->query(
            "UPDATE users SET nama_lengkap = COALESCE(NULLIF(?, ''), nama_lengkap), email = ?, no_hp = ?, updated_at = NOW() WHERE id = ?",
            [$v->get('nama_lengkap', ''), $v->get('email') ?: null, $v->get('no_hp') ?: null, $payload['sub']]
        );

        if ($payload['role'] === 'warga') {
            $this->db->query(
                "UPDATE profil_warga SET alamat_lengkap = COALESCE(NULLIF(?, ''), alamat_lengkap), pekerjaan = COALESCE(NULLIF(?, ''), pekerjaan),
                 kelurahan = COALESCE(NULLIF(?, ''), kelurahan), kecamatan = COALESCE(NULLIF(?, ''), kecamatan),
                 kota_kabupaten = COALESCE(NULLIF(?, ''), kota_kabupaten) WHERE user_id = ?",
                [$v->get('alamat_lengkap', ''), $v->get('pekerjaan', ''), $v->get('kelurahan', ''), $v->get('kecamatan', ''), $v->get('kota_kabupaten', ''), $payload['sub']]
            );
        }

        Response::success(null, 'Profil berhasil diperbarui.');
    }
}
