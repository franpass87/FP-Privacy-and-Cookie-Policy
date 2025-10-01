<?php
declare(strict_types=1);

$pluginRoot = dirname(__DIR__);

$options = getopt('', ['set:', 'major', 'minor', 'patch']);

$setVersion = $options['set'] ?? null;
$actions = array_filter([
    'major' => array_key_exists('major', $options),
    'minor' => array_key_exists('minor', $options),
    'patch' => array_key_exists('patch', $options),
]);

if ($setVersion !== null && count($actions) > 0) {
    fwrite(STDERR, "Options --set and --major/--minor/--patch are mutually exclusive." . PHP_EOL);
    exit(1);
}

if ($setVersion === null && empty($actions)) {
    $actions['patch'] = true;
}

$pluginFile = null;
foreach (glob($pluginRoot . '/*.php') as $candidate) {
    $contents = file_get_contents($candidate);
    if ($contents === false) {
        continue;
    }

    if (preg_match('/^\s*\*\s*Plugin Name:/mi', $contents) === 1) {
        $pluginFile = $candidate;
        break;
    }
}

if ($pluginFile === null) {
    fwrite(STDERR, "Unable to locate the main plugin file with a WordPress header." . PHP_EOL);
    exit(1);
}

$contents = file_get_contents($pluginFile);
if ($contents === false) {
    fwrite(STDERR, "Unable to read plugin file: {$pluginFile}" . PHP_EOL);
    exit(1);
}

if (preg_match('/^(\s*\*\s*Version:\s*)([^\r\n]+)/mi', $contents, $matches, PREG_OFFSET_CAPTURE) !== 1) {
    fwrite(STDERR, "Unable to find Version header in plugin file." . PHP_EOL);
    exit(1);
}

$currentVersion = trim($matches[2][0]);

if ($setVersion !== null) {
    $newVersion = $setVersion;
} else {
    if (preg_match('/^(\d+)\.(\d+)\.(\d+)(.*)$/', $currentVersion, $versionParts) !== 1) {
        fwrite(STDERR, "Current version '{$currentVersion}' is not in semantic versioning format." . PHP_EOL);
        exit(1);
    }

    [$full, $major, $minor, $patch, $suffix] = $versionParts;
    $major = (int) $major;
    $minor = (int) $minor;
    $patch = (int) $patch;

    if (!empty($suffix)) {
        fwrite(STDERR, "Version suffix '{$suffix}' is not supported for automatic bumping." . PHP_EOL);
        exit(1);
    }

    if (!empty($actions['major'])) {
        $major += 1;
        $minor = 0;
        $patch = 0;
    } elseif (!empty($actions['minor'])) {
        $minor += 1;
        $patch = 0;
    } else {
        $patch += 1;
    }

    $newVersion = sprintf('%d.%d.%d', $major, $minor, $patch);
}

if ($currentVersion === $newVersion) {
    fwrite(STDOUT, $newVersion . PHP_EOL);
    exit(0);
}

$contents = substr_replace(
    $contents,
    $matches[1][0] . $newVersion,
    $matches[0][1],
    strlen($matches[0][0])
);

$patterns = [
    "/(define\\(\\s*'[^']*_PLUGIN_VERSION'\\s*,\\s*')([^']+)('\\s*\\)\\s*;)/",
    '/(define\(\s*"[^"]*_PLUGIN_VERSION"\s*,\s*")([^"]+)("\s*\)\s*;)/',
];

foreach ($patterns as $pattern) {
    $contents = preg_replace_callback(
        $pattern,
        function (array $match) use ($newVersion) {
            return $match[1] . $newVersion . $match[3];
        },
        $contents,
        1,
        $count
    );

    if ($count > 0) {
        break;
    }
}

if (file_put_contents($pluginFile, $contents) === false) {
    fwrite(STDERR, "Failed to write updated plugin file." . PHP_EOL);
    exit(1);
}

echo $newVersion . PHP_EOL;
exit(0);
