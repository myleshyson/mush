#!/usr/bin/env php
<?php

/**
 * Build script for creating a standalone Fusion executable using static-php-cli.
 *
 * This script:
 * 1. Downloads spc (static-php-cli) if not present
 * 2. Builds micro.sfx with required extensions
 * 3. Creates a PHAR archive using box
 * 4. Combines micro.sfx + PHAR into a standalone executable
 */

// Configuration
$phpVersion = '8.4';
$extensions = 'ctype,dom,filter,iconv,mbstring,phar,posix,tokenizer,pcntl';
$outputBinary = 'builds/fusion';

// Detect OS and architecture
$os = PHP_OS_FAMILY === 'Darwin' ? 'macos' : 'linux';
$arch = php_uname('m') === 'arm64' || php_uname('m') === 'aarch64' ? 'aarch64' : 'x86_64';
$spcUrl = "https://dl.static-php.dev/static-php-cli/spc-bin/nightly/spc-{$os}-{$arch}";

echo "🔨 Building Fusion standalone executable\n";
echo "   OS: {$os}, Arch: {$arch}\n";
echo "   PHP: {$phpVersion}\n";
echo "   Extensions: {$extensions}\n\n";

// Step 1: Download spc if not present
if (! file_exists('spc')) {
    echo "📥 Downloading spc...\n";
    $spcContent = file_get_contents($spcUrl);
    if ($spcContent === false) {
        fwrite(STDERR, "❌ Failed to download spc from {$spcUrl}\n");
        exit(1);
    }
    file_put_contents('spc', $spcContent);
    chmod('spc', 0755);
    echo "   ✓ Downloaded spc\n\n";
} else {
    echo "✓ spc already exists\n\n";
}

// Step 2: Create craft.yml for spc
echo "📝 Creating craft.yml...\n";
$craftYml = <<<YAML
php-version: "{$phpVersion}"
extensions: "{$extensions}"
sapi:
  - micro
download-options:
  prefer-pre-built: true
YAML;
file_put_contents('craft.yml', $craftYml);
echo "   ✓ Created craft.yml\n\n";

// Step 3: Build micro.sfx using spc
if (! file_exists('buildroot/bin/micro.sfx')) {
    echo "🏗️  Building micro.sfx (this may take a while on first run)...\n";
    passthru('./spc craft', $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, "❌ Failed to build micro.sfx\n");
        exit(1);
    }
    echo "   ✓ Built micro.sfx\n\n";
} else {
    echo "✓ micro.sfx already exists (delete buildroot/ to rebuild)\n\n";
}

// Step 4: Download box if not present
if (! file_exists('box.phar')) {
    echo "📥 Downloading box.phar...\n";
    $boxContent = file_get_contents('https://github.com/box-project/box/releases/download/4.6.6/box.phar');
    if ($boxContent === false) {
        fwrite(STDERR, "❌ Failed to download box.phar\n");
        exit(1);
    }
    file_put_contents('box.phar', $boxContent);
    chmod('box.phar', 0755);
    echo "   ✓ Downloaded box.phar\n\n";
} else {
    echo "✓ box.phar already exists\n\n";
}

// Step 5: Install production dependencies
echo "📦 Installing production dependencies...\n";
passthru('composer install --no-dev --optimize-autoloader --quiet', $exitCode);
if ($exitCode !== 0) {
    fwrite(STDERR, "❌ Failed to install dependencies\n");
    exit(1);
}
echo "   ✓ Dependencies installed\n\n";

// Step 6: Build PHAR
echo "📦 Building PHAR...\n";
passthru('php box.phar compile --quiet', $exitCode);
if ($exitCode !== 0) {
    fwrite(STDERR, "❌ Failed to build PHAR\n");
    exit(1);
}
echo "   ✓ Built builds/fusion.phar\n\n";

// Step 7: Combine micro.sfx with PHAR
echo "🔗 Combining micro.sfx + PHAR...\n";
$microSfx = file_get_contents('buildroot/bin/micro.sfx');
$phar = file_get_contents('builds/fusion.phar');

if ($microSfx === false || $phar === false) {
    fwrite(STDERR, "❌ Failed to read micro.sfx or PHAR\n");
    exit(1);
}

// Ensure builds directory exists
if (! is_dir('builds')) {
    mkdir('builds', 0755, true);
}

file_put_contents($outputBinary, $microSfx.$phar);
chmod($outputBinary, 0755);

$size = round(filesize($outputBinary) / 1024 / 1024, 2);
echo "   ✓ Created {$outputBinary} ({$size} MB)\n\n";

// Step 8: Restore dev dependencies
echo "📦 Restoring dev dependencies...\n";
passthru('composer install --quiet', $exitCode);
echo "   ✓ Dev dependencies restored\n\n";

// Step 9: Test the binary
echo "🧪 Testing the binary...\n";
passthru("./{$outputBinary} --version", $exitCode);
if ($exitCode !== 0) {
    fwrite(STDERR, "❌ Binary test failed\n");
    exit(1);
}

echo "\n✅ Build complete! Run with: ./{$outputBinary}\n";
