param(
    [int[]] $Offsets = (0..26),
    [string] $WordPressRoot = 'C:\xampp\htdocs\aspect-trading',
    [string] $SourceRoot = (Split-Path -Parent $PSScriptRoot),
    [string] $PhpPath = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'
$runnerSource = Join-Path $PSScriptRoot 'run-catalogue-upgrade-batch.php'
$dataSource = Join-Path $PSScriptRoot 'catalogue-upgrade-data.php'

foreach ($path in @($WordPressRoot, $SourceRoot, $PhpPath, $runnerSource, $dataSource)) {
    if (-not (Test-Path -LiteralPath $path)) {
        throw "Required path was not found: $path"
    }
}

$tempRoot = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$tempDirectory = Join-Path $tempRoot ("aspect-catalogue-upgrade-{0}" -f [guid]::NewGuid().ToString('N'))
$resolvedTempDirectory = [System.IO.Path]::GetFullPath($tempDirectory)
if (-not $resolvedTempDirectory.StartsWith($tempRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'The temporary runner directory resolved outside the system temp directory.'
}

$previousWpRoot = $env:ASPECT_TRADING_WP_ROOT
$previousSourceRoot = $env:ASPECT_TRADING_SOURCE_ROOT
$previousOffset = $env:ASPECT_BATCH_OFFSET
$previousSize = $env:ASPECT_BATCH_SIZE

try {
    New-Item -ItemType Directory -Path $resolvedTempDirectory -Force | Out-Null
    $env:ASPECT_TRADING_WP_ROOT = $WordPressRoot
    $env:ASPECT_TRADING_SOURCE_ROOT = $SourceRoot
    $env:ASPECT_BATCH_SIZE = '1'

    foreach ($offset in $Offsets) {
        if ($offset -lt 0 -or $offset -gt 26) {
            throw "Invalid catalogue offset: $offset"
        }

        $tempRunner = Join-Path $resolvedTempDirectory 'run-catalogue-upgrade-batch.php'
        $tempData = Join-Path $resolvedTempDirectory 'catalogue-upgrade-data.php'
        Copy-Item -LiteralPath $runnerSource -Destination $tempRunner -Force
        Copy-Item -LiteralPath $dataSource -Destination $tempData -Force
        $env:ASPECT_BATCH_OFFSET = [string] $offset

        & $PhpPath -d display_errors=1 -d log_errors=1 -d memory_limit=512M -d max_execution_time=0 $tempRunner
        if ($LASTEXITCODE -ne 0) {
            throw "Catalogue batch offset $offset failed with exit code $LASTEXITCODE."
        }
    }
} finally {
    $env:ASPECT_TRADING_WP_ROOT = $previousWpRoot
    $env:ASPECT_TRADING_SOURCE_ROOT = $previousSourceRoot
    $env:ASPECT_BATCH_OFFSET = $previousOffset
    $env:ASPECT_BATCH_SIZE = $previousSize

    if (Test-Path -LiteralPath $resolvedTempDirectory) {
        $verified = [System.IO.Path]::GetFullPath($resolvedTempDirectory)
        if ($verified.StartsWith($tempRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
            Remove-Item -LiteralPath $verified -Recurse -Force
        }
    }
}
