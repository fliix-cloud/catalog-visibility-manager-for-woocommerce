# Build a WordPress-compatible plugin ZIP (run from this script's directory).
# Usage: powershell -ExecutionPolicy Bypass -File .\build-release.ps1

$ErrorActionPreference = 'Stop'

$slug = 'catalog-visibility-manager-for-woocommerce'
$root = $PSScriptRoot
if ((Split-Path $root -Leaf) -ne $slug) {
    Write-Error "Run this script from the plugin folder named '$slug'."
}

$parent = Split-Path $root -Parent
$stage  = Join-Path $env:TEMP "wp-plugin-build-$slug"
$zip    = Join-Path $parent "$slug.zip"

$excludeDirs = @('.git', 'node_modules', 'mcps', 'bin', 'tests', '.github')
$excludeFiles = @('.gitignore', '.distignore', '.env', 'build-release.ps1', '*.zip', 'composer.json', 'README.md')

if (Test-Path $stage) {
    Remove-Item -LiteralPath $stage -Recurse -Force
}
New-Item -ItemType Directory -Path (Join-Path $stage $slug) -Force | Out-Null
$dest = Join-Path $stage $slug

Get-ChildItem -LiteralPath $root -Force | ForEach-Object {
    if ($_.Name -in $excludeDirs) { return }
    if ($_.Name -like '.*') { return }
    if ($_.Name -eq 'build-release.ps1') { return }
    if ($_.Name -eq 'composer.json' -or $_.Name -eq 'README.md') { return }
    if ($_.Name -like '*.zip') { return }
    Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $dest $_.Name) -Recurse -Force
}

if (-not (Test-Path (Join-Path $dest 'catalog-visibility-manager-for-woocommerce.php'))) {
    Write-Error 'Main plugin file missing in staged build.'
}
if (-not (Test-Path (Join-Path $dest 'assets\css\admin-tree.css'))) {
    Write-Error 'assets folder incomplete in staged build.'
}

if (Test-Path $zip) {
    Remove-Item -LiteralPath $zip -Force
}

# tar -a produces ZIP archives WordPress unpacks reliably on Windows (unlike Compress-Archive).
Push-Location $stage
try {
    tar -a -c -f $zip $slug
}
finally {
    Pop-Location
}

Remove-Item -LiteralPath $stage -Recurse -Force

Write-Host "Created: $zip"
Write-Host "Top-level folder inside zip: $slug\"
Write-Host "Upload this file in Plugins -> Add New -> Upload, or to WordPress.org."