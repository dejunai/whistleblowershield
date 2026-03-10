Write-Host "Creating legal citation model documentation..."

$path = "documentation/architecture/legal-citation-model.md"

New-Item -ItemType Directory -Force -Path "documentation/architecture" | Out-Null

$content = @"
# U.S. Legal Citation Model

Statutes:
31 U.S.C. § 3729
15 U.S.C. § 78u-6

Regulations:
17 C.F.R. § 240.21F-2
29 C.F.R. § 24.102

Case Law:
United States ex rel. Marcus v. Hess, 317 U.S. 537 (1943)

Preferred sources:
govinfo.gov
congress.gov
federalregister.gov
state legislature websites
"@

$content | Out-File -Encoding utf8 $path

Write-Host "Adding file to git..."

git add $path

Write-Host "Creating commit..."

git commit -m "Add legal citation model documentation"

Write-Host "Pushing to GitHub..."

git push

Write-Host "Legal citation model added."