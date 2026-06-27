<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Db\Database;
use App\Utils\Response;
use App\Utils\Validator;

class RegisterService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function handle(array $input): void
    {
        $v = new Validator($input);
        $v->required('nik', 'NIK')->nik()
            ->required('nama_lengkap', 'Nama lengkap')->minLength('nama_lengkap', 3)->maxLength('nama_lengkap', 150)
            ->required('password', 'Password')->password()
            ->required('no_rt', 'No RT')
            ->required('no_rw', 'No RW')
            ->required('alamat_lengkap', 'Alamat')
            ->email('email')->noHp('no_hp');

        if ($v->fails()) {
            Response::validationError($v->getErrors());
        }

        if ($this->db->query("SELECT id FROM users WHERE nik = ? LIMIT 1", [$v->get('nik')])->fetch()) {
            Response::error('NIK sudah terdaftar.', 409);
        }

        $this->db->beginTransaction();
        try {
            $hash = password_hash((string)($input['password'] ?? ''), PASSWORD_BCRYPT, ['cost' => (int)($_ENV['BCRYPT_COST'] ?? 10)]);
            $this->db->query(
                "INSERT INTO users (nik, nama_lengkap, email, no_hp, password_hash, role, status_akun)
                 VALUES (?, ?, ?, ?, ?, 'warga', 'pending')",
                [$v->get('nik'), $v->get('nama_lengkap'), $v->get('email') ?: null, $v->get('no_hp') ?: null, $hash]
            );

            $userId = (int)$this->db->lastInsertId();
            $this->db->query(
                "INSERT INTO profil_warga
                 (user_id, no_kk, no_rt, no_rw, alamat_lengkap, kelurahan, kecamatan, kota_kabupaten,
                  tempat_lahir, tanggal_lahir, jenis_kelamin, agama, status_perkawinan, pekerjaan, status_tinggal, tanggal_pindah_masuk)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId, $v->get('no_kk') ?: null, $v->get('no_rt'), $v->get('no_rw'), $v->get('alamat_lengkap'),
                    $v->get('kelurahan') ?: null, $v->get('kecamatan') ?: null, $v->get('kota_kabupaten') ?: null,
                    $v->get('tempat_lahir') ?: null, $v->get('tanggal_lahir') ?: null, $v->get('jenis_kelamin') ?: null,
                    $v->get('agama') ?: null, $v->get('status_perkawinan') ?: null, $v->get('pekerjaan') ?: null,
                    $v->get('status_tinggal') ?: 'tetap', $v->get('tanggal_pindah_masuk') ?: null,
                ]
            );

            $this->db->commit();
            Response::created(['user_id' => $userId], 'Registrasi berhasil. Akun menunggu verifikasi admin.');
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
