# Gộp nhiều DOCX bằng Microsoft Word (InsertFile). Chạy trên Windows có cài Word.
# Args: <files.txt UTF-8, mỗi dòng một đường dẫn> <output.docx>
$ErrorActionPreference = 'Stop'
if ($args.Count -lt 2) {
    throw 'Usage: merge-docx.ps1 <files.txt> <output.docx>'
}

$listPath = [string]$args[0]
$outPath = [string]$args[1]
$files = @(Get-Content -LiteralPath $listPath -Encoding UTF8 | Where-Object { $_.Trim() -ne '' })
if ($files.Count -lt 1) {
    throw 'Không có file Word để gộp.'
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
    throw 'Word không ghi được file tổng.'
}
