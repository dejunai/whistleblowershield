Write-Host "Creating integrity and jurisdiction documentation..."

$docs = @{

"documentation/development/ws-core/data-integrity-rules.md" = @"
# ws-core Data Integrity Rules

Every legal entity must reference a jurisdiction.

Statutes must include:
- statute name
- citation
- official source

Legal updates must include:
- law name
- summary
- source URL
- effective date

Duplicate entities should be avoided.

Future enforcement may include validation logic in ws-core.
"@

"documentation/architecture/jurisdiction-scope-model.md" = @"
# Jurisdiction Scope Model

WhistleblowerShield focuses exclusively on United States law.

Hierarchy:

United States
 ├ Federal
 └ States
    ├ California
    ├ Texas
    ├ New York

Federal statutes attach to United States.

State statutes attach to the relevant state jurisdiction.
"@

}

foreach ($path in $docs.Keys) {
    $dir = Split-Path $path
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    $docs[$path] | Out-File -Encoding utf8 $path
}

Write-Host "Adding files to git..."

git add documentation

Write-Host "Creating commit..."

git commit -m "Add data integrity rules and jurisdiction scope model"

Write-Host "Pushing to GitHub..."

git push

Write-Host "Documentation added."