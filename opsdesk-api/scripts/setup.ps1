$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root
if (-not (Test-Path '.env')) {
    $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
    $keyBytes = New-Object byte[] 32
    $passwordBytes = New-Object byte[] 36
    $rng.GetBytes($keyBytes)
    $rng.GetBytes($passwordBytes)
    $rng.Dispose()
    $key = 'base64:' + [Convert]::ToBase64String($keyBytes)
    $dbPassword = [Convert]::ToBase64String($passwordBytes)
    $template = [IO.File]::ReadAllText((Join-Path $root '.env.example'))
    $template = $template.Replace('APP_KEY=', "APP_KEY=$key").Replace('DB_PASSWORD=', "DB_PASSWORD=$dbPassword")
    [IO.File]::WriteAllText((Join-Path $root '.env'), $template, [Text.UTF8Encoding]::new($false))
    Write-Host 'Created .env with random APP_KEY and DB_PASSWORD.'
} else {
    Write-Host 'Keeping existing .env; not overwriting credentials.'
}
$envContent = [IO.File]::ReadAllText((Join-Path $root '.env'))
if ($envContent -match '(?m)^APP_KEY=\s*$' -or $envContent -match '(?m)^DB_PASSWORD=\s*$') {
    throw '.env has an empty APP_KEY or DB_PASSWORD. Set both securely before continuing.'
}
docker compose up --build -d
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }
docker compose exec -T app php artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Database migration failed' }
Write-Host 'API: http://localhost:8080  |  Swagger: http://localhost:8080/docs/'
