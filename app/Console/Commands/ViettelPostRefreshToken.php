<?php
// app/Console/Commands/ViettelPostRefreshToken.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ViettelPostService;

class ViettelPostRefreshToken extends Command
{
    protected $signature = 'viettelpost:refresh-token';
    protected $description = 'Làm mới token Viettel Post';

    public function handle(ViettelPostService $service)
    {
        $username = config('viettelpost.username');
        $password = config('viettelpost.password');

        if (!$username || !$password) {
            $this->error('Vui lòng cấu hình VIETTELPOST_USERNAME và VIETTELPOST_PASSWORD trong .env');
            return 1;
        }

        $this->info('Đang lấy token...');

        // Step 1: Get short-term token
        $this->line('1. Lấy token ngắn hạn...');
        $shortToken = $service->getShortTermToken($username, $password);

        if (!$shortToken) {
            $this->error('✗ Không thể lấy token ngắn hạn. Kiểm tra lại username/password');
            return 1;
        }
        $this->info('✓ Đã lấy token ngắn hạn');

        // Step 2: Get long-term token
        $this->line('2. Lấy token dài hạn...');
        $longToken = $service->getLongTermToken($shortToken, $username, $password);

        if (!$longToken) {
            $this->error('✗ Không thể lấy token dài hạn');
            return 1;
        }
        $this->info('✓ Đã lấy token dài hạn');

        // Step 3: Save to .env
        $this->line('3. Lưu token vào .env...');
        $service->updateToken($longToken);
        $this->info('✓ Đã lưu token');

        $this->newLine();
        $this->info('🎉 Hoàn thành! Token đã được cập nhật.');
        $this->line('Token: ' . substr($longToken, 0, 50) . '...');

        return 0;
    }
}
