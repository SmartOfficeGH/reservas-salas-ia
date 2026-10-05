# Ejecutar desde la raíz del proyecto después de composer install --no-dev.
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$privateSource = Join-Path $projectRoot 'private'
if (-not (Test-Path -LiteralPath (Join-Path $privateSource 'vendor/autoload.php'))) { throw 'Faltan dependencias: ejecuta composer install --no-dev.' }
$buildName = 'reservas-salas-' + (Get-Date -Format 'yyyyMMdd-HHmmss')
$distRoot = Join-Path $projectRoot 'dist'
$stageRoot = Join-Path $distRoot $buildName
New-Item -ItemType Directory -Path $stageRoot -Force | Out-Null
Copy-Item -LiteralPath (Join-Path $projectRoot 'public') -Destination (Join-Path $stageRoot 'public') -Recurse
$privateTarget = Join-Path $stageRoot 'reservas-salas-private'
New-Item -ItemType Directory -Path $privateTarget | Out-Null
Copy-Item -LiteralPath (Join-Path $privateSource 'app') -Destination $privateTarget -Recurse
Copy-Item -LiteralPath (Join-Path $privateSource 'vendor') -Destination $privateTarget -Recurse
Copy-Item -LiteralPath (Join-Path $privateSource 'config.example.php') -Destination $privateTarget
Copy-Item -LiteralPath (Join-Path $projectRoot 'database') -Destination $stageRoot -Recurse
foreach ($name in @('INSTALACION.md','PRUEBAS.md','IDENTIDAD-CORPORATIVA.md','composer.json','composer.lock')) { Copy-Item -LiteralPath (Join-Path $projectRoot $name) -Destination $stageRoot }
$zipPath = Join-Path $distRoot ($buildName + '.zip')
# ZipFile incluye .htaccess; la lista permitida nunca incluye config.php ni correos de prueba.
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($stageRoot,$zipPath)
Write-Output $zipPath
