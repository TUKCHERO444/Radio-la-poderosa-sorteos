<?php
declare(strict_types=1);

$source = $argv[1] ?? '';
$target = $argv[2] ?? '';

if ($source === '' || $target === '' || !is_dir($source)) {
    fwrite(STDERR, "Uso: php build_templates.php <origen> <destino>\n");
    exit(1);
}

$pages = [
    'index.html' => 'pages/home.html',
    'subpage/index.html' => 'pages/subpage.html',
    'legal-notice/index.html' => 'pages/legal-notice.html',
    'privacy/index.html' => 'pages/privacy.html',
];

$collections = [
    'real-estate-single-page-layout' => 'real-estate-single-page-layout',
    'jobs-1-item' => 'jobs-1-item',
    'modules-1-item' => 'modules-1-item',
];

function transform_page(string $html): string
{
    $html = str_replace('class="inner collection-loading"', 'class="inner"', $html);
    $html = str_replace(' style="opacity:0"', '', $html);
    $html = preg_replace('/\s+data-collection-bind="[^"]*"/', '', $html);
    $html = preg_replace("/\s+data-collection-bind='[^']*'/", '', $html);
    return $html;
}

function write_template(string $sourceFile, string $targetFile, bool $transform): void
{
    $html = file_get_contents($sourceFile);
    if ($html === false) {
        fwrite(STDERR, "No se pudo leer: {$sourceFile}\n");
        exit(1);
    }
    if ($transform) {
        $html = transform_page($html);
    }
    $dir = dirname($targetFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    if (file_put_contents($targetFile, $html) === false) {
        fwrite(STDERR, "No se pudo escribir: {$targetFile}\n");
        exit(1);
    }
}

foreach ($pages as $relative => $destination) {
    $sourceFile = $source . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_file($sourceFile)) {
        write_template($sourceFile, $target . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $destination), false);
    }
}

foreach ($collections as $folder => $destinationFolder) {
    $dir = $source . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($dir)) {
        continue;
    }
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $itemDir) {
        $sourceFile = $itemDir . DIRECTORY_SEPARATOR . 'index.html';
        if (!is_file($sourceFile)) {
            continue;
        }
        $slug = basename($itemDir);
        $destination = $target . DIRECTORY_SEPARATOR . $destinationFolder . DIRECTORY_SEPARATOR . $slug . '.html';
        write_template($sourceFile, $destination, true);
    }
}

echo "Templates generados en {$target}\n";
