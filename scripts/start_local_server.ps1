$projectRoot = Split-Path -Parent $PSScriptRoot
$phpExe = Join-Path $projectRoot "tools\php\php.exe"

if (!(Test-Path $phpExe)) {
    Write-Error "Local PHP executable not found at $phpExe"
    exit 1
}

Set-Location $projectRoot
& $phpExe -S 127.0.0.1:8080 -t .
