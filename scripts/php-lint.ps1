$ErrorActionPreference = 'Stop'

function Get-PhpExe {
    $candidates = @()
    if (Test-Path 'C:\wamp64\bin\php') {
        $candidates += Get-ChildItem -Path 'C:\wamp64\bin\php' -Recurse -Filter 'php.exe' -ErrorAction SilentlyContinue
    }
    try {
        $cmd = Get-Command php -ErrorAction Stop
        if ($cmd -and $cmd.Source) {
            $candidates += Get-Item $cmd.Source -ErrorAction SilentlyContinue
        }
    } catch {}

    $candidates = $candidates | Where-Object { $_ -and (Test-Path $_.FullName) } | Sort-Object FullName -Descending
    if (-not $candidates -or $candidates.Count -eq 0) {
        throw "php.exe not found. Ensure WAMP PHP is installed (e.g. C:\wamp64\bin\php\php*\php.exe) or add PHP to PATH."
    }
    return $candidates[0].FullName
}

$php = Get-PhpExe
Write-Output ("Using PHP: " + $php)

$root = Split-Path -Parent $PSScriptRoot
$files = Get-ChildItem -Path $root -Recurse -Filter '*.php' | Select-Object -ExpandProperty FullName

foreach ($f in $files) {
    $out = & $php -l $f 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Output ("FAIL: " + $f)
        Write-Output $out
        exit 1
    }
}

Write-Output ("OK: " + $files.Count + " PHP files passed syntax check")

