<?php

// These PHP releases fix CVE-2026-109618 and CVE-2026-109619.
// Check the real interpreter, independently of Composer's platform override.
$minimumVersions = [
    '8.3' => '8.3.36',
    '8.4' => '8.4.27',
    '8.5' => '8.5.12',
];
$branch = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

if (!isset($minimumVersions[$branch]) || version_compare(PHP_VERSION, $minimumVersions[$branch], '<')) {
    fwrite(STDERR, 'Unsupported PHP runtime ' . PHP_VERSION . '. Upgrade to PHP 8.3.36, 8.4.27, or 8.5.12 (or a newer patch in those branches).' . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'PHP ' . PHP_VERSION . ' meets the Panasaurus security baseline.' . PHP_EOL);
