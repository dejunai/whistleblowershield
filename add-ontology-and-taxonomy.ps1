Write-Host "Creating ontology and taxonomy documentation..."

$docs = @{

"documentation/architecture/whistleblower-law-ontology.md" = @"
# U.S. Whistleblower Law Ontology

Whistleblower
 ├ reports → violation
 ├ protected by → statute
 ├ reports to → agency
 └ may receive → financial award

Violation
 ├ reported by → whistleblower
 ├ investigated by → agency
 └ governed by → statute

Statute
 ├ protects → whistleblower
 ├ defines → violation
 ├ administered by → agency
 └ applies within → jurisdiction

Agency
 ├ receives → reports
 ├ investigates → violations
 └ enforces → statutes
"@

"documentation/architecture/whistleblower-law-taxonomy.md" = @"
# U.S. Whistleblower Law Taxonomy

Major categories of whistleblower law:

Fraud Against Government
Securities Law
Workplace Retaliation
Tax Fraud
Environmental Law
Public Sector Whistleblowing
"@

}

foreach ($path in $docs.Keys) {
    $dir = Split-Path $path
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    $docs[$path] | Out-File -Encoding utf8 $path
}

Write-Host "Adding files to git..."

git add documentation/architecture

Write-Host "Creating commit..."

git commit -m "Add whistleblower law ontology and taxonomy"

Write-Host "Pushing to GitHub..."

git push

Write-Host "Ontology and taxonomy added."