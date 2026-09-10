#!/usr/bin/env php
<?php
/**
 * cron.php — Auto Sync for SiCantik Cloud
 * Run via crontab: every 5 minutes /usr/bin/php /var/www/sicantik.ptspkotim.my.id/cron.php >> /var/log/sicantik-cron.log 2>&1
 */
define('CRON_MODE', true);
require_once __DIR__ . '/core.php';

$enabled = getSetting('auto_sync_enabled', 'off');
if ($enabled !== 'on') exit(0);

$mode      = getSetting('auto_sync_mode', 'interval');
$interval  = intval(getSetting('auto_sync_interval', '60'));
$dailyTime = getSetting('auto_sync_daily_time', '06:00');
$lastRun   = getSetting('auto_sync_last_run', '');

$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
$shouldRun = false;

if ($mode === 'interval') {
    if (!$lastRun) {
        $shouldRun = true;
    } else {
        $last = new DateTimeImmutable($lastRun, new DateTimeZone('Asia/Jakarta'));
        $diff = $now->getTimestamp() - $last->getTimestamp();
        $shouldRun = ($diff >= $interval * 60);
    }
} elseif ($mode === 'daily') {
    $targetHour = intval(substr($dailyTime, 0, 2));
    $currentHour = intval($now->format('H'));
    $currentMin  = intval($now->format('i'));
    // Run if current hour matches and we haven't run today
    if ($currentHour === $targetHour && $currentMin < 10) {
        $today = $now->format('Y-m-d');
        if (!$lastRun || substr($lastRun, 0, 10) !== $today) {
            $shouldRun = true;
        }
    }
}

if (!$shouldRun) exit(0);

// Sync all connections
$db = getDB();
$conns = $db->query("SELECT id, name FROM connections")->fetchAll();
$total = 0;
$errors = 0;

foreach ($conns as $c) {
    try {
        $r = syncConnection($c['id']);
        $total += count($r['data']);
        if (!$r['success']) $errors++;
        echo date('Y-m-d H:i:s') . " [SYNC] {$c['name']}: " . ($r['success'] ? count($r['data']) . " records" : "ERROR: {$r['error']}") . "\n";
    } catch (Throwable $e) {
        $errors++;
        echo date('Y-m-d H:i:s') . " [ERROR] {$c['name']}: {$e->getMessage()}\n";
    }
}

saveSetting('auto_sync_last_run', $now->format('Y-m-d H:i:s'));
echo date('Y-m-d H:i:s') . " [DONE] Synced " . count($conns) . " connections, {$total} total records, {$errors} errors\n";
