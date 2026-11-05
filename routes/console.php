<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:vapid-keys', function () {
    $phpDirectory = dirname(php_ini_loaded_file() ?: PHP_BINARY);
    $configCandidates = [
        $phpDirectory.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
        dirname($phpDirectory).DIRECTORY_SEPARATOR.'apache'.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'openssl.cnf',
        $phpDirectory.DIRECTORY_SEPARATOR.'openssl.cnf',
    ];
    $configFile = collect($configCandidates)->first(fn (string $path) => is_file($path));
    $options = [
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ];
    if ($configFile) {
        $options['config'] = $configFile;
    }

    $key = openssl_pkey_new($options);
    if ($key === false) {
        throw new RuntimeException('Unable to generate VAPID keys. Check the PHP OpenSSL configuration.');
    }
    $details = openssl_pkey_get_details($key);
    if (! is_array($details) || ! isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
        throw new RuntimeException('Unable to read the generated VAPID key.');
    }
    $encode = fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    $publicKey = $encode("\x04".str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT).str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT));
    $privateKey = $encode(str_pad($details['ec']['d'], 32, "\0", STR_PAD_LEFT));
    $this->line('WEBPUSH_PUBLIC_KEY='.$publicKey);
    $this->line('WEBPUSH_PRIVATE_KEY='.$privateKey);
})->purpose('Generate VAPID keys for browser push notifications');

Schedule::command('notifications:send-volunteer-reminders')
    ->everyMinute()
    ->withoutOverlapping(2);
