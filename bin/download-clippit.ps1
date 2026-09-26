# Tai Clippit CLI (Windows x64) vao laravel/bin/clippit.exe
# Nguon: npm @sergey-tihon/clippit-bin-win32-x64 (MIT)
$ErrorActionPreference = 'Stop'
$here = Split-Path -LiteralPath $MyInvocation.MyCommand.Path -Parent
$out = Join-Path $here 'clippit.exe'
$tmp = Join-Path $env:TEMP ('clippit-npm-' + [guid]::NewGuid().ToString('n'))
New-Item -ItemType Directory -Path $tmp -Force | Out-Null
try {
    Push-Location $tmp
    npm pack @sergey-tihon/clippit-bin-win32-x64 --silent
    $tgz = Get-ChildItem -Filter 'sergey-tihon-clippit-bin-win32-x64-*.tgz' | Select-Object -First 1
    if (-not $tgz) { throw 'npm pack failed' }
    tar -xzf $tgz.FullName
    Copy-Item -LiteralPath (Join-Path $tmp 'package/clippit.exe') -Destination $out -Force
    Write-Host "OK: $out"
    & $out --version
} finally {
    Pop-Location
    Remove-Item -LiteralPath $tmp -Recurse -Force -ErrorAction SilentlyContinue
}
