Write-Host ""
Write-Host "WhistleblowerShield Documentation Audit"
Write-Host "----------------------------------------"
Write-Host ""

$docs = Get-ChildItem -Recurse -Filter *.md

$results = @()

foreach ($doc in $docs) {

    $content = Get-Content $doc.FullName
    $lineCount = $content.Count
    $wordCount = ($content -join " " -split "\s+").Count

    $status = "OK"

    if ($lineCount -lt 20) {
        $status = "TOO SHORT"
    }

    if ($wordCount -lt 120) {
        $status = "LIKELY ABBREVIATED"
    }

    if ($content -match "TODO|TBD|placeholder|example") {
        $status = "PLACEHOLDER CONTENT"
    }

    $results += [PSCustomObject]@{
        File = $doc.FullName
        Lines = $lineCount
        Words = $wordCount
        Status = $status
    }
}

$results | Sort-Object Status | Format-Table -AutoSize

Write-Host ""
Write-Host "Total docs:" $results.Count
Write-Host ""