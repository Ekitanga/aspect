param(
    [string] $WordPressRoot = 'C:\xampp\htdocs\aspect-trading',
    [string] $PhpPath = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'
$runnerSource = Join-Path $PSScriptRoot 'test-local-checkout-order.php'
foreach ($path in @($WordPressRoot, $PhpPath, $runnerSource)) {
    if (-not (Test-Path -LiteralPath $path)) { throw "Required path was not found: $path" }
}
$tempRoot = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$tempDirectory = Join-Path $tempRoot ("aspect-checkout-test-{0}" -f [guid]::NewGuid().ToString('N'))
$resolvedTempDirectory = [System.IO.Path]::GetFullPath($tempDirectory)
if (-not $resolvedTempDirectory.StartsWith($tempRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'The temporary runner directory resolved outside the system temp directory.'
}
$previousWpRoot = $env:ASPECT_TRADING_WP_ROOT
try {
    New-Item -ItemType Directory -Path $resolvedTempDirectory -Force | Out-Null
    $tempRunner = Join-Path $resolvedTempDirectory 'test-local-checkout-order.php'
    Copy-Item -LiteralPath $runnerSource -Destination $tempRunner -Force
    $env:ASPECT_TRADING_WP_ROOT = $WordPressRoot
    & $PhpPath -d display_errors=1 -d log_errors=1 -d memory_limit=256M -d max_execution_time=0 $tempRunner
    if ($LASTEXITCODE -ne 0) { throw "Checkout order test failed with exit code $LASTEXITCODE." }
} finally {
    $env:ASPECT_TRADING_WP_ROOT = $previousWpRoot
    if (Test-Path -LiteralPath $resolvedTempDirectory) {
        $verified = [System.IO.Path]::GetFullPath($resolvedTempDirectory)
        if ($verified.StartsWith($tempRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
            Remove-Item -LiteralPath $verified -Recurse -Force
        }
    }
}
