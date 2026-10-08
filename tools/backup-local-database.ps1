param(
    [string] $WordPressRoot = 'C:\xampp\htdocs\aspect-trading',
    [string] $PhpPath = 'C:\xampp\php\php.exe',
    [string] $DumpPath = 'C:\xampp\mysql\bin\mysqldump.exe',
    [string] $BackupDirectory = (Join-Path $PSScriptRoot '..\.artifacts\backups'),
    [ValidatePattern('^[a-z0-9-]+$')]
    [string] $Label = 'client-categories'
)

$ErrorActionPreference = 'Stop'

foreach ($path in @($WordPressRoot, $PhpPath, $DumpPath)) {
    if (-not (Test-Path -LiteralPath $path)) {
        throw "Required path was not found: $path"
    }
}

$tempRoot = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$token = [guid]::NewGuid().ToString('N')
$tempPhp = Join-Path $tempRoot "aspect-trading-backup-$token.php"
$tempDefaults = Join-Path $tempRoot "aspect-trading-backup-$token.cnf"
$tempDatabaseName = Join-Path $tempRoot "aspect-trading-backup-$token.txt"
$tempError = Join-Path $tempRoot "aspect-trading-backup-$token.err"

foreach ($path in @($tempPhp, $tempDefaults, $tempDatabaseName, $tempError)) {
    if (-not $path.StartsWith($tempRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw 'A temporary backup path resolved outside the system temp directory.'
    }
}

$php = @'
define('ABSPATH', rtrim(str_replace('\\', '/', getenv('ASPECT_TRADING_WP_ROOT')), '/') . '/');
require ABSPATH . 'wp-config.php';

$host = DB_HOST;
$port = 3306;
if (preg_match('/^(.+):(\d+)$/', $host, $matches)) {
    $host = $matches[1];
    $port = (int) $matches[2];
}
$escape = static function ($value) {
    return str_replace(array('\\', '"', "\r", "\n"), array('\\\\', '\\"', '', ''), (string) $value);
};
$defaults = "[client]\n"
    . 'user="' . $escape(DB_USER) . "\"\n"
    . 'password="' . $escape(DB_PASSWORD) . "\"\n"
    . 'host="' . $escape($host) . "\"\n"
    . 'port=' . $port . "\n";
file_put_contents(getenv('ASPECT_TRADING_MY_CNF'), $defaults);
file_put_contents(getenv('ASPECT_TRADING_DB_NAME_FILE'), DB_NAME);
'@

New-Item -ItemType Directory -Path $BackupDirectory -Force | Out-Null
$backupRoot = [System.IO.Path]::GetFullPath((Resolve-Path -LiteralPath $BackupDirectory).Path)
$backupFile = Join-Path $backupRoot ("aspect-trading-before-{0}-{1}.sql" -f $Label, (Get-Date -Format 'yyyyMMdd-HHmmss'))

if (-not $backupFile.StartsWith($backupRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'The database backup path resolved outside the configured backup directory.'
}

$previousRoot = $env:ASPECT_TRADING_WP_ROOT
$previousDefaults = $env:ASPECT_TRADING_MY_CNF
$previousNameFile = $env:ASPECT_TRADING_DB_NAME_FILE
$env:ASPECT_TRADING_WP_ROOT = $WordPressRoot
$env:ASPECT_TRADING_MY_CNF = $tempDefaults
$env:ASPECT_TRADING_DB_NAME_FILE = $tempDatabaseName

try {
    [System.IO.File]::WriteAllText($tempPhp, "<?php`n$php", [System.Text.UTF8Encoding]::new($false))
    & $PhpPath $tempPhp
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $tempDefaults) -or -not (Test-Path -LiteralPath $tempDatabaseName)) {
        throw 'Could not prepare the private database backup configuration.'
    }

    $databaseName = (Get-Content -Raw -LiteralPath $tempDatabaseName).Trim()
    if (-not $databaseName) {
        throw 'The configured database name is empty.'
    }

    $process = Start-Process -FilePath $DumpPath -ArgumentList @(
        "--defaults-extra-file=$tempDefaults",
        '--single-transaction',
        '--skip-lock-tables',
        '--default-character-set=utf8mb4',
        $databaseName
    ) -RedirectStandardOutput $backupFile -RedirectStandardError $tempError -PassThru -Wait -NoNewWindow

    if ($process.ExitCode -ne 0) {
        if (Test-Path -LiteralPath $tempError) {
            $message = Get-Content -Raw -LiteralPath $tempError
        } else {
            $message = 'No database-dump error output was available.'
        }
        throw "Database backup failed: $message"
    }

    $backup = Get-Item -LiteralPath $backupFile
    if ($backup.Length -lt 1024) {
        throw 'Database backup output is unexpectedly small.'
    }

    [pscustomobject]@{
        Backup = $backup.FullName
        Bytes = $backup.Length
        Created = $backup.LastWriteTime
    } | Format-List
} finally {
    $env:ASPECT_TRADING_WP_ROOT = $previousRoot
    $env:ASPECT_TRADING_MY_CNF = $previousDefaults
    $env:ASPECT_TRADING_DB_NAME_FILE = $previousNameFile
    foreach ($path in @($tempPhp, $tempDefaults, $tempDatabaseName, $tempError)) {
        if (Test-Path -LiteralPath $path -PathType Leaf) {
            Remove-Item -LiteralPath $path -Force
        }
    }
}
