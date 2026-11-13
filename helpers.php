<?php

/**
 * helpers.php
 *
 * Fungsi & konstanta bersama untuk semua script Laravel:
 *   setup.php  · update.php  · serve.php
 *
 * Cara pakai — tambahkan di baris pertama setiap script:
 *   require __DIR__ . '/helpers.php';
 *
 * @author    Akmal
 *
 * @instagram @lukheeman
 *
 * @phone     082250212121
 *
 * @portfolio https://lukheman.github.io/portfolio/
 */

// ============================================================
//  Guard – cegah di-load lebih dari sekali
// ============================================================

if (defined('HELPERS_LOADED')) {
    return;
}
define('HELPERS_LOADED', true);

// ============================================================
//  Deteksi OS & Warna
// ============================================================

define('IS_WIN', PHP_OS_FAMILY === 'Windows');
define('IS_MAC', PHP_OS_FAMILY === 'Darwin');

define('USE_COLOR', IS_WIN
    ? (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT, true))
    : true
);

/** Kode ANSI. Akses via C['cyan'] dll. */
const C = [
    'reset' => "\033[0m",
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
    'bold' => "\033[1m",
    'dim' => "\033[2m",
];

// ============================================================
//  Output
// ============================================================

/**
 * Cetak teks ke stdout, opsional dengan warna ANSI.
 *
 * Contoh:
 *   out('Halo!');
 *   out('Sukses', 'green', 'bold');
 */
function out(string $text, string ...$colors): void
{
    if (USE_COLOR && $colors) {
        $open = implode('', array_map(fn ($c) => C[$c] ?? '', $colors));
        echo $open.$text.C['reset'].PHP_EOL;
    } else {
        echo $text.PHP_EOL;
    }
}

/** Kembalikan prefix ikon berdasarkan tipe (ok|err|warn|info). */
function icon(string $type): string
{
    return '  '.match ($type) {
        'ok' => IS_WIN ? '[OK]   ' : '✔  ',
        'err' => IS_WIN ? '[ERR]  ' : '✖  ',
        'warn' => IS_WIN ? '[WARN] ' : '⚠  ',
        'info' => IS_WIN ? '[-]    ' : '➜  ',
        default => '   ',
    };
}

function ok(string $msg): void
{
    out(icon('ok').$msg, 'green');
}
function err(string $msg): void
{
    out(icon('err').$msg, 'red');
}
function warn(string $msg): void
{
    out(icon('warn').$msg, 'yellow');
}
function info(string $msg): void
{
    out(icon('info').$msg, 'blue');
}

/**
 * Cetak header langkah dengan garis pemisah.
 *
 * Contoh: step(1, 'Composer Install');
 */
function step(int $n, string $title): void
{
    out('');
    out(str_repeat('═', 60), 'cyan');
    out("  Langkah {$n}: {$title}", 'bold', 'cyan');
    out(str_repeat('═', 60), 'cyan');
    out('');
}

/**
 * Cetak box header bertema hijau (dipakai di awal setiap script).
 *
 * Contoh: header('SIMKA - SCRIPT SETUP PROJECT LARAVEL');
 */
function printHeader(string $title): void
{
    out('');
    out('+'.str_repeat('-', 63).'+', 'bold', 'green');
    out('|'.str_pad("  {$title}", 63).'|', 'bold', 'green');
    out('+'.str_repeat('-', 63).'+', 'bold', 'green');
    out('');
}

/**
 * Cetak baris ringkasan dengan simbol tree (├─ / └─).
 *
 * Contoh:
 *   summary('Git Pull',   'Selesai', false);
 *   summary('Optimasi',   'Selesai', true);   // baris terakhir → └─
 */
function summary(string $label, string $status, bool $last = false): void
{
    $prefix = $last ? '  └─' : '  ├─';
    out("{$prefix} ".str_pad($label, 22).": {$status}", 'cyan');
}

// ============================================================
//  Eksekusi Command
// ============================================================

/**
 * Jalankan shell command dengan output live ke terminal.
 * Mengembalikan true jika exit code = 0.
 */
function run(string $cmd): bool
{
    info("Menjalankan: {$cmd}");
    out('');
    $proc = proc_open($cmd, [STDIN, STDOUT, STDERR], $pipes);
    if (! is_resource($proc)) {
        return false;
    }
    $code = proc_close($proc);
    out('');

    return $code === 0;
}

/**
 * Jalankan shell command dan kembalikan output sebagai string.
 * Stderr diabaikan. Berguna untuk git rev-parse, git log, dsb.
 */
function shell(string $cmd): string
{
    $redirect = IS_WIN ? '2>NUL' : '2>/dev/null';

    return trim(shell_exec("{$cmd} {$redirect}") ?? '');
}

/**
 * Cek apakah sebuah command tersedia di PATH.
 *
 * Contoh: if (!hasCmd('git')) { ... }
 */
function hasCmd(string $cmd): bool
{
    return ! empty(shell(IS_WIN ? "where {$cmd}" : "which {$cmd}"));
}

/**
 * Ambil versi sebuah command (baris pertama dari --version).
 *
 * Contoh: getVer('php')  →  "PHP 8.2.12 ..."
 */
function getVer(string $cmd): string
{
    $redirect = IS_WIN ? '2>NUL' : '2>/dev/null';
    $out = shell_exec("{$cmd} --version {$redirect}");

    return explode("\n", trim($out ?? ''))[0] ?: 'Tidak diketahui';
}

// ============================================================
//  Input Interaktif
// ============================================================

/**
 * Baca input dari user dengan prompt berwarna.
 * Jika user tidak mengetik apa-apa, kembalikan $default.
 *
 * Contoh: $host = input('Host MySQL', '127.0.0.1');
 */
function input(string $prompt, string $default = ''): string
{
    $hint = $default !== '' ? " [{$default}]" : '';
    echo (USE_COLOR ? C['yellow'] : '')."  {$prompt}{$hint}: ".(USE_COLOR ? C['reset'] : '');
    $val = trim(fgets(STDIN));

    return $val !== '' ? $val : $default;
}

/**
 * Baca konfirmasi y/n dari user.
 *
 * Contoh: if (confirm('Jalankan server sekarang?')) { ... }
 */
function confirm(string $prompt, bool $default = false): bool
{
    $hint = $default ? 'Y/n' : 'y/N';
    echo (USE_COLOR ? C['yellow'] : '')."  {$prompt} [{$hint}]: ".(USE_COLOR ? C['reset'] : '');
    $val = strtolower(trim(fgets(STDIN)));
    if ($val === '') {
        return $default;
    }

    return in_array($val, ['y', 'ya', 'yes'], true);
}

// ============================================================
//  File .env
// ============================================================

/**
 * Update atau tambahkan key=value di file .env.
 * Jika key sudah ada, nilainya diganti. Jika belum, ditambah di akhir.
 *
 * Contoh: setEnv('/app/.env', 'DB_HOST', '127.0.0.1');
 */
function setEnv(string $file, string $key, string $val): void
{
    $content = file_get_contents($file);
    $line = "{$key}={$val}";
    $content = preg_match("/^{$key}=/m", $content)
        ? preg_replace("/^{$key}=.*/m", $line, $content)
        : $content.PHP_EOL.$line;
    file_put_contents($file, $content);
}

// ============================================================
//  Browser
// ============================================================

/**
 * Buka URL di browser default sesuai OS.
 * Di Linux, mencoba xdg-open, gnome-open, kde-open, sensible-browser.
 *
 * Contoh: openBrowser('http://localhost:8000');
 */
function openBrowser(string $url): void
{
    info("Membuka browser ke {$url}...");

    if (IS_WIN) {
        pclose(popen("start {$url}", 'r'));

        return;
    }

    if (IS_MAC) {
        exec("open {$url} > /dev/null 2>&1 &");

        return;
    }

    // Linux — coba launcher satu per satu
    foreach (['xdg-open', 'gnome-open', 'kde-open', 'sensible-browser', 'x-www-browser'] as $bin) {
        if (! empty(shell("which {$bin}"))) {
            exec("{$bin} {$url} > /dev/null 2>&1 &");

            return;
        }
    }

    warn('Tidak dapat membuka browser secara otomatis.');
    warn("Silahkan buka manual: {$url}");
}

// ============================================================
//  Configuration
// ============================================================

/**
 * Baca nilai dari config.json
 */
function get_config(?string $key = null, mixed $default = null)
{
    static $config = null;
    if ($config === null) {
        $file = __DIR__.'/config.json';
        $config = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    }

    if (is_null($key)) {
        return $config;
    }

    $keys = explode('.', $key);
    $val = $config;
    foreach ($keys as $k) {
        if (! is_array($val) || ! array_key_exists($k, $val)) {
            return $default;
        }
        $val = $val[$k];
    }

    return $val;
}

// ============================================================
//  Informasi Developer
// ============================================================

/**
 * Tampilkan box kontak developer di akhir script.
 */
function showContact(): void
{
    $e = fn (string $unicode, string $fallback) => IS_WIN ? $fallback : $unicode;

    out('');
    out('+'.str_repeat('-', 63).'+', 'bold', 'cyan');
    out('|'.str_repeat(' ', 63).'|', 'bold', 'cyan');
    out('|   '.$e('👤', '*').' Nama      : Akmal'.str_repeat(' ', 40).'|', 'bold', 'cyan');
    out('|   '.$e('📸', '*').' Instagram : @lukheeman'.str_repeat(' ', 35).'|', 'bold', 'cyan');
    out('|   '.$e('📱', '*').' No. HP    : 082250223147'.str_repeat(' ', 33).'|', 'bold', 'cyan');
    out('|   '.$e('🌐', '*').' Portfolio : https://lukheman.github.io/portfolio/'.str_repeat(' ', 8).'|', 'bold', 'cyan');
    out('|   '.$e('🌐', '*').' Website   : https://aplikasita.my.id/'.str_repeat(' ', 20).'|', 'bold', 'cyan');
    out('|'.str_repeat(' ', 63).'|', 'bold', 'cyan');
    out('|   Silahkan hubungi untuk pertanyaan atau bantuan!'.str_repeat(' ', 13).'|', 'bold', 'cyan');
    out('|'.str_repeat(' ', 63).'|', 'bold', 'cyan');
    out('+'.str_repeat('-', 63).'+', 'bold', 'cyan');
    out('');
}

showContact();
