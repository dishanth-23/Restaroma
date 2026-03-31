Param(
  [string]$InputDir = "$(Resolve-Path "$PSScriptRoot\..\docs\diagrams")",
  [string]$OutputDir = "$(Resolve-Path "$PSScriptRoot\..\docs" -ErrorAction SilentlyContinue)\diagrams-out"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $InputDir)) {
  throw "InputDir not found: $InputDir"
}

if (-not (Test-Path $OutputDir)) {
  New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null
}

$files = Get-ChildItem -Path $InputDir -Filter *.mmd | Sort-Object Name
if ($files.Count -eq 0) {
  throw "No .mmd files found in $InputDir"
}

Write-Host "Rendering $($files.Count) Mermaid diagram(s)..." -ForegroundColor Cyan

foreach ($f in $files) {
  $out = Join-Path $OutputDir ($f.BaseName + ".png")
  Write-Host " - $($f.Name) -> $([System.IO.Path]::GetFileName($out))"

  # Uses Mermaid CLI via npx (downloads on first run).
  # If your environment blocks downloads, install once: npm i -g @mermaid-js/mermaid-cli
  npx -y @mermaid-js/mermaid-cli -i "$($f.FullName)" -o "$out" -b transparent
}

Write-Host "Done. Output folder: $OutputDir" -ForegroundColor Green

