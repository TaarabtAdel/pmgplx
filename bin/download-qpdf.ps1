# Tai qpdf Windows x64 (msvc) vao laravel/bin/
$ErrorActionPreference = 'Stop'
$Version = if ($env:QPDF_VERSION) { $env:QPDF_VERSION } else { '12.4.1' }
$here = Split-Path -LiteralPath $MyInvocation.MyCommand.Path -Parent
$zip = Join-Path $env:TEMP ("qpdf-$Version-msvc64.zip")
$url = "https://github.com/qpdf/qpdf/releases/download/v$Version/qpdf-$Version-msvc64.zip"
Write-Host "Downloading $url ..."
Invoke-WebRequest -Uri $url -OutFile $zip -UseBasicParsing
$dest = Join-Path $env:TEMP ("qpdf-extract-$Version")
if (Test-Path $dest) { Remove-Item -LiteralPath $dest -Recurse -Force }
Expand-Archive -LiteralPath $zip -DestinationPath $dest -Force
$bin = Join-Path $dest "qpdf-$Version-msvc64/bin"
Get-ChildItem -LiteralPath $bin -File | Copy-Item -Destination $here -Force
Remove-Item -LiteralPath $zip -Force -ErrorAction SilentlyContinue
Remove-Item -LiteralPath $dest -Recurse -Force -ErrorAction SilentlyContinue
Write-Host "OK: $(Join-Path $here 'qpdf.exe')"
