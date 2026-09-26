# Merge DOCX files via Microsoft Word InsertFile (Windows + Word required).
# Usage: merge-docx.ps1 <files.txt> <output.docx>
# files.txt: UTF-8, one absolute path per line (ASCII paths recommended).
$ErrorActionPreference = 'Stop'
if ($args.Count -lt 2) {
    throw 'Usage: merge-docx.ps1 <files.txt> <output.docx>'
}

$listPath = [string]$args[0]
$outPath = [string]$args[1]

if (-not (Test-Path -LiteralPath $listPath)) {
    throw ('List file not found: ' + $listPath)
}

$raw = [System.IO.File]::ReadAllText($listPath)
if ($raw.Length -ge 1 -and [int][char]$raw[0] -eq 0xFEFF) {
    $raw = $raw.Substring(1)
}

$candidates = @(
    $raw -split "`r?`n" |
    ForEach-Object { $_.Trim() } |
    Where-Object { $_ -ne '' }
)

$files = @()
foreach ($candidate in $candidates) {
    if (-not (Test-Path -LiteralPath $candidate)) {
        throw ('DOCX not found: ' + $candidate)
    }
    $files += (Resolve-Path -LiteralPath $candidate).Path
}

if ($files.Count -lt 1) {
    $bytes = ([System.IO.FileInfo]$listPath).Length
    throw ('No DOCX in list (' + $bytes + ' bytes): ' + $listPath)
}

$word = $null
$doc = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0

    $doc = $word.Documents.Open([string]$files[0])
    for ($i = 1; $i -lt $files.Count; $i++) {
        $rng = $doc.Content
        $rng.Collapse(0) | Out-Null
        $rng.InsertBreak(7) | Out-Null
        $rng = $doc.Content
        $rng.Collapse(0) | Out-Null
        $rng.InsertFile([string]$files[$i])
    }

    $outDir = Split-Path -LiteralPath $outPath -Parent
    if ($outDir -and -not (Test-Path -LiteralPath $outDir)) {
        New-Item -ItemType Directory -Path $outDir -Force | Out-Null
    }

    if (Test-Path -LiteralPath $outPath) {
        Remove-Item -LiteralPath $outPath -Force
    }

    $wdFormatXMLDocument = 16
    $doc.SaveAs2([string]$outPath, $wdFormatXMLDocument)
    $doc.Close($false) | Out-Null
    $doc = $null
} finally {
    if ($null -ne $doc) {
        try { $doc.Close($false) | Out-Null } catch {}
    }
    if ($null -ne $word) {
        try { $word.Quit() | Out-Null } catch {}
        try {
            [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
        } catch {}
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}

if (-not (Test-Path -LiteralPath $outPath)) {
    throw 'Word did not write the output DOCX.'
}
