$ErrorActionPreference = "Stop"

$Root = Split-Path -Parent $PSScriptRoot
$OutputDir = Join-Path $Root "dist"
$BundlePath = Join-Path $OutputDir "janus-eb.zip"

if (!(Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

if (Test-Path $BundlePath) {
    Remove-Item $BundlePath -Force
}

$excludeNames = @(
    ".git",
    "vendor",
    "node_modules",
    "tests",
    "postman",
    "dist",
    ".env"
)

$items = Get-ChildItem -Path $Root -Force | Where-Object {
    $excludeNames -notcontains $_.Name
}

# Build a zip with normalized forward-slash paths for Linux unzip compatibility on Elastic Beanstalk.
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zip = [System.IO.Compression.ZipFile]::Open($BundlePath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($item in $items) {
        if ($item.PSIsContainer) {
            $files = Get-ChildItem -Path $item.FullName -Recurse -File -Force
            foreach ($file in $files) {
                $relative = $file.FullName.Substring($Root.Length + 1).Replace('\', '/')
                [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $relative, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
            }
        }
        elseif ($item -is [System.IO.FileInfo]) {
            $relative = $item.FullName.Substring($Root.Length + 1).Replace('\', '/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $item.FullName, $relative, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
        }
    }
}
finally {
    $zip.Dispose()
}

Write-Output "Elastic Beanstalk bundle created: $BundlePath"
