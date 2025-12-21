# Script PowerShell pour copier les fichiers Laravel
# À exécuter depuis le répertoire restaurant-backend

Write-Host "🚀 Copie des fichiers Laravel..." -ForegroundColor Green

# Vérifier qu'on est dans le bon répertoire
if (-not (Test-Path "app")) {
    Write-Host "❌ Erreur: Ce script doit être exécuté depuis le répertoire restaurant-backend" -ForegroundColor Red
    exit 1
}

$sourceDir = "..\chef-s-table-main"

# Models
Write-Host "📁 Copie des Models..." -ForegroundColor Yellow
Copy-Item "$sourceDir\laravel-models\*.php" "app\Models\" -Force

# Controllers
Write-Host "📁 Copie des Controllers..." -ForegroundColor Yellow
Copy-Item "$sourceDir\laravel-controllers\*.php" "app\Http\Controllers\" -Force
Copy-Item "$sourceDir\laravel-controllers\routes\api.php" "routes\api.php" -Force

# Migrations
Write-Host "📁 Copie des Migrations..." -ForegroundColor Yellow
Copy-Item "$sourceDir\laravel-migrations\*.php" "database\migrations\" -Force

# Form Requests
Write-Host "📁 Copie des Form Requests..." -ForegroundColor Yellow
$requestDirs = @("Auth", "Role", "User", "Category", "MenuItem", "RestaurantTable", "Order", "OrderItem", "Payment", "StockItem", "Reservation", "Setting", "Backup")
foreach ($dir in $requestDirs) {
    $targetDir = "app\Http\Requests\$dir"
    New-Item -ItemType Directory -Force -Path $targetDir | Out-Null
    if (Test-Path "$sourceDir\laravel-requests\$dir") {
        Copy-Item "$sourceDir\laravel-requests\$dir\*.php" $targetDir -Force
    }
}

# Resources
Write-Host "📁 Copie des Resources..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path "app\Http\Resources\Collections" | Out-Null
Copy-Item "$sourceDir\laravel-resources\*.php" "app\Http\Resources\" -Force
Copy-Item "$sourceDir\laravel-resources\Collections\*.php" "app\Http\Resources\Collections\" -Force

Write-Host "✅ Copie terminée avec succès !" -ForegroundColor Green

