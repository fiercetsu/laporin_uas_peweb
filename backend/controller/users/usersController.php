<?php
declare(strict_types=1);

namespace App\Controllers\Users;

use App\Services\Users\LaporanService;
use App\Services\Users\LoginService;
use App\Services\Users\LogoutService;
use App\Services\Users\NotificationService;
use App\Services\Users\ProfileService;
use App\Services\Users\PublicDataService;
use App\Services\Users\RefreshService;
use App\Services\Users\RegisterService;

class UsersController
{
    public function register(): void { (new RegisterService())->handle($_POST); }
    public function login(): void { (new LoginService())->handle($_POST); }
    public function refresh(): void { (new RefreshService())->handle($_POST); }
    public function logout(): void { (new LogoutService())->handle($_POST); }

    public function me(): void { (new ProfileService())->me(); }
    public function updateMe(): void { (new ProfileService())->update($_POST); }

    public function categories(): void { (new PublicDataService())->categories(); }
    public function announcements(): void { (new PublicDataService())->announcements(); }

    public function laporanIndex(): void { (new LaporanService())->index(); }
    public function laporanShow(int $id): void { (new LaporanService())->show($id); }
    public function laporanCreate(): void { (new LaporanService())->create($_POST, $_FILES); }
    public function laporanCancel(int $id): void { (new LaporanService())->cancel($id); }
    public function laporanRating(int $id): void { (new LaporanService())->rating($id, $_POST); }

    public function notifications(): void { (new NotificationService())->list(); }
    public function readNotification(int $id): void { (new NotificationService())->markRead($id); }
}
