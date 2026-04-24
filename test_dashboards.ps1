Write-Host "=== School User Dashboard Audit ===" -ForegroundColor Cyan
Write-Host ""

$prod = "https://saas-schools-production.up.railway.app"
$users = @(
    @{email="admin@admin.com"; pass="cj"; name="Admin"},
    @{email="teacher@teacher.com"; pass="cj"; name="Teacher"},
    @{email="parent@parent.com"; pass="cj"; name="Parent"},
    @{email="student@student.com"; pass="student"; name="Student"},
    @{email="accountant@accountant.com"; pass="cj"; name="Accountant"}
)

foreach ($user in $users) {
    $jar = "C:\temp\tmp_$($user.name.ToLower()).txt"
    
    Write-Host "$($user.name) Dashboard:" -NoNewline
    
    # Login
    curl.exe -s -c $jar -X POST "$prod/login" -d "email=$($user.email)&password=$($user.pass)" > $null 2>&1
    
    # Test dashboard
    $status = curl.exe -s -I -b $jar "$prod/dashboard" 2>&1 | Select-String "HTTP" | Select-Object -First 1
    
    if ($status -match "200") {
        Write-Host " ✓ 200 OK" -ForegroundColor Green
    } elseif ($status -match "302|301") {
        Write-Host " ✓ 30x (Redirect)" -ForegroundColor Yellow
    } elseif ($status -match "500") {
        Write-Host " ✗ 500 ERROR" -ForegroundColor Red
    } else {
        Write-Host " ? $status" -ForegroundColor Gray
    }
}

Write-Host "`nDashboard audit completed." -ForegroundColor Green
