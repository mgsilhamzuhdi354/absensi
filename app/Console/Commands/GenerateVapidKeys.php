<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'pwa:generate-vapid {--write : Tulis key ke file .env}';

    protected $description = 'Generate VAPID keys untuk PWA Web Push notifications.';

    public function handle()
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            $this->error('Gagal membuat VAPID keys: ' . $e->getMessage());
            $this->warn('Pastikan ekstensi OpenSSL PHP di server mendukung kurva EC P-256.');
            $this->warn('Jika server lokal gagal, jalankan command ini di cPanel/server production atau generate key VAPID dari environment Linux.');

            return 1;
        }

        $this->info('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->info('VAPID_PRIVATE_KEY=' . $keys['privateKey']);

        if ($this->option('write')) {
            $this->writeEnvValue('VAPID_PUBLIC_KEY', $keys['publicKey']);
            $this->writeEnvValue('VAPID_PRIVATE_KEY', $keys['privateKey']);
            $this->writeEnvValue('VAPID_SUBJECT', env('APP_URL', config('app.url')));
            $this->info('VAPID keys berhasil ditulis ke .env');
        }

        return 0;
    }

    private function writeEnvValue(string $key, string $value): void
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            file_put_contents($path, '');
        }

        $content = file_get_contents($path) ?: '';
        $line = $key . '=' . $this->quoteEnvValue($value);

        if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $content)) {
            $content = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $line, $content);
        } else {
            $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
        }

        file_put_contents($path, $content);
    }

    private function quoteEnvValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|"/', $value)) {
            return '"' . str_replace('"', '\"', $value) . '"';
        }

        return $value;
    }
}
