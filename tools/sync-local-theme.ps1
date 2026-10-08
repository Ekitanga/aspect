param(
    [string] $WordPressRoot = 'C:\xampp\htdocs\aspect-trading'
)

$ErrorActionPreference = 'Stop'
$sourceRoot = Split-Path $PSScriptRoot -Parent
$themeRoot = Join-Path $WordPressRoot 'wp-content\themes'
$childTarget = Join-Path $themeRoot 'aspect-trading'
$previewTarget = Join-Path $themeRoot 'aspect-trading-preview'

foreach ($path in @($sourceRoot, $themeRoot, $childTarget, $previewTarget)) {
    if (-not (Test-Path -LiteralPath $path -PathType Container)) {
        throw "Required directory was not found: $path"
    }
}

$sharedFiles = @(
    'functions.php',
    'header.php',
    'front-page.php',
    'footer.php',
    'sidebar.php',
    'inc\marketplace.php',
    'assets\css\branding.css',
    'assets\css\marketplace.css',
    'assets\js\hero-slider.js',
    'assets\js\product-gallery.js'
)

$brandingFiles = Get-ChildItem -LiteralPath (Join-Path $sourceRoot 'assets\images\branding') -File

foreach ($target in @($childTarget, $previewTarget)) {
    foreach ($relativePath in $sharedFiles) {
        $source = Join-Path $sourceRoot $relativePath
        $destination = Join-Path $target $relativePath
        $destinationDirectory = Split-Path $destination -Parent
        New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
        Copy-Item -LiteralPath $source -Destination $destination -Force
    }

    $brandingTarget = Join-Path $target 'assets\images\branding'
    New-Item -ItemType Directory -Path $brandingTarget -Force | Out-Null
    foreach ($file in $brandingFiles) {
        Copy-Item -LiteralPath $file.FullName -Destination (Join-Path $brandingTarget $file.Name) -Force
    }
}

Copy-Item -LiteralPath (Join-Path $sourceRoot 'style.css') -Destination (Join-Path $childTarget 'style.css') -Force
Copy-Item -LiteralPath (Join-Path $sourceRoot 'preview\style.css') -Destination (Join-Path $previewTarget 'style.css') -Force
Copy-Item -LiteralPath (Join-Path $sourceRoot 'preview\index.php') -Destination (Join-Path $previewTarget 'index.php') -Force

Write-Output 'Aspect Trading child and preview theme files are synchronized.'
