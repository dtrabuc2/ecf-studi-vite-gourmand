[CmdletBinding()]
param(
    [string]$PhpExe = "php",
    [string]$HostName = "127.0.0.1",
    [int]$Port = 8080
)
$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$DocsDir = Join-Path $ProjectRoot "docs"
$ReportPath = Join-Path $DocsDir "rapport-tests.php"
$PublicDir = Join-Path $ProjectRoot "public"
$BaseUrl = "http://{0}:{1}" -f $HostName, $Port
$Results = [System.Collections.Generic.List[object]]::new()
$StartedServer = $false
$ServerProcess = $null
$StartTime = Get-Date

function Add-Test {
    param([string]$Category,[string]$Name,[string]$Status,[string]$Details="")
    $Results.Add([pscustomobject]@{Category=$Category;Name=$Name;Status=$Status;Details=$Details})
}
function Run-Cmd {
    param([string]$File,[string[]]$Args)
    $out=& $File @Args 2>&1
    [pscustomobject]@{Code=$LASTEXITCODE;Output=(($out|ForEach-Object{$_.ToString()})-join [Environment]::NewLine)}
}
function Test-PhpSyntax {
    param([string]$File)
    $r=Run-Cmd $PhpExe @("-l",$File)
    $rel=$File.Substring($ProjectRoot.Length).TrimStart("\/")
    if($r.Code -eq 0){Add-Test "PHP" "Syntaxe $rel" "PASS" $r.Output}else{Add-Test "PHP" "Syntaxe $rel" "FAIL" $r.Output}
}
function Test-Http {
    param([string]$Method,[string]$Path,[int[]]$Expected,[string]$Name)
    try{$r=Invoke-WebRequest -Uri ($BaseUrl+$Path) -Method $Method -MaximumRedirection 0 -UseBasicParsing -TimeoutSec 15 -ErrorAction Stop;$code=[int]$r.StatusCode}
    catch{$code=$null;if($_.Exception.Response){try{$code=[int]$_.Exception.Response.StatusCode.value__}catch{}}}
    if($null -ne $code -and $Expected -contains $code){Add-Test "HTTP" $Name "PASS" "HTTP $code"}
    elseif($null -ne $code){Add-Test "HTTP" $Name "FAIL" "HTTP $code; attendu: $($Expected -join ', ')"}
    else{Add-Test "HTTP" $Name "FAIL" "Aucune réponse HTTP"}
}

try {
    New-Item -ItemType Directory -Path $DocsDir -Force | Out-Null

    $v=Run-Cmd $PhpExe @("-r","echo PHP_VERSION;")
    if($v.Code -eq 0){
        $ver=$v.Output.Trim()
        if($ver -match "^8\.5"){Add-Test "Environnement" "PHP" "PASS" $ver}
        elseif($ver -match "^8\.[2-9]"){Add-Test "Environnement" "PHP" "WARN" "$ver - PHP 8.5 recommandé"}
        else{Add-Test "Environnement" "PHP" "FAIL" "$ver - PHP 8.2 minimum"}
    }else{Add-Test "Environnement" "PHP" "FAIL" "PHP introuvable"}

    $mods=(Run-Cmd $PhpExe @("-m")).Output
    foreach($ext in @("PDO","mbstring","json","openssl","mongodb")){
        if($mods -match "(?im)^$ext$"){Add-Test "Environnement" "Extension $ext" "PASS"}else{Add-Test "Environnement" "Extension $ext" "FAIL" "Extension absente"}
    }

    $composer=Get-Command composer -ErrorAction SilentlyContinue
    if($composer){
        $r=Run-Cmd $composer.Source @("validate","--no-check-publish")
        if($r.Code -eq 0){Add-Test "Composer" "composer validate" "PASS" $r.Output}else{Add-Test "Composer" "composer validate" "FAIL" $r.Output}
    }else{Add-Test "Composer" "Composer disponible" "FAIL" "Commande composer introuvable"}

    if(Test-Path (Join-Path $ProjectRoot "vendor\autoload.php")){Add-Test "Composer" "vendor/autoload.php" "PASS"}else{Add-Test "Composer" "vendor/autoload.php" "FAIL" "Autoloader absent"}

    Get-ChildItem $ProjectRoot -Recurse -Filter *.php -File | Where-Object{$_.FullName -notmatch "\\vendor\\"} | ForEach-Object{Test-PhpSyntax $_.FullName}

    $critical=@("public\index.php","composer.json",".env.example","viteetgourmand.sql","mongodb-init-viteetgourmand.js","config\routes.php","config\app.php","App\Core\Application.php","App\Core\Database.php","App\Controller\AuthController.php","App\Controller\OrderController.php","App\Controller\AdminController.php","App\Controller\AddressController.php","public\assets\js\order.js","public\assets\js\address.js","public\assets\css\responsive.css")
    foreach($f in $critical){if(Test-Path (Join-Path $ProjectRoot $f)){Add-Test "Fichiers" $f "PASS"}else{Add-Test "Fichiers" $f "FAIL" "Fichier absent"}}

    if(-not(Test-Path (Join-Path $PublicDir "index.php"))){throw "public\index.php absent."}
    $existing=Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
    if(-not $existing){
        $log=Join-Path $env:TEMP "viteetgourmand-php.log"
        $err=Join-Path $env:TEMP "viteetgourmand-php-error.log"
        $serverAddress="{0}:{1}" -f $HostName,$Port
        $ServerProcess=Start-Process -FilePath $PhpExe -ArgumentList @("-S",$serverAddress,"-t",$PublicDir) -WorkingDirectory $ProjectRoot -RedirectStandardOutput $log -RedirectStandardError $err -PassThru -WindowStyle Hidden
        $StartedServer=$true
        Start-Sleep -Seconds 2
    }else{Add-Test "Serveur" "Port $Port" "WARN" "Port déjà utilisé; serveur existant utilisé"}

    $public=@(@("/","Accueil"),@("/menus","Menus"),@("/menus/1","Détail menu"),@("/menus/filter","Filtre menus"),@("/public/menus","API menus"),@("/public/menus/1","API menu"),@("/contact","Contact"),@("/quote","Devis"),@("/legal","Mentions légales"),@("/cgv","CGV"),@("/login","Connexion"),@("/register","Inscription"),@("/forgot-password","Mot de passe oublié"),@("/admin/login","Login admin"))
    foreach($t in $public){Test-Http "GET" $t[0] @(200,301,302) $t[1]}
    Test-Http "GET" "/api/address/autocomplete?q=Auch" @(200,400,503) "API adresse"

    $protected=@(@("/profile","Profil"),@("/orders","Commandes"),@("/orders/new","Nouvelle commande"),@("/notifications","Notifications"),@("/admin/dashboard","Dashboard admin"),@("/admin/orders","Admin commandes"),@("/admin/quotes","Admin devis"),@("/admin/emails","Emails"),@("/admin/hours","Horaires"),@("/admin/dishes","Plats"),@("/admin/customers","Clients"),@("/admin/employees","Employés"),@("/admin/menus","Menus admin"),@("/admin/comments/pending","Commentaires"),@("/admin/revenue","CA"))
    foreach($t in $protected){Test-Http "GET" $t[0] @(302,303,401,403) $t[1]}

    $end=Get-Date
    $summary=[ordered]@{total=$Results.Count;pass=@($Results|Where-Object Status -eq "PASS").Count;warn=@($Results|Where-Object Status -eq "WARN").Count;fail=@($Results|Where-Object Status -eq "FAIL").Count}
    $json=$Results|ConvertTo-Json -Depth 5
    $php=@'
<?php
declare(strict_types=1);
$report=['generated_at'=>'__DATE__','duration_seconds'=>__DURATION__,'summary'=>['total'=>__TOTAL__,'pass'=>__PASS__,'warn'=>__WARN__,'fail'=>__FAIL__],'results'=>json_decode(<<<'JSON'
__JSON__
JSON,true,512,JSON_THROW_ON_ERROR)];
$statusClass=static function(string $status):string{return match($status){'PASS'=>'pass','WARN'=>'warn',default=>'fail',};};
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Rapport de tests Vite &amp; Gourmand</title><style>body{font-family:Arial,sans-serif;max-width:1400px;margin:2rem auto;padding:0 1rem}.cards{display:flex;gap:1rem;flex-wrap:wrap}.card{border:1px solid #ddd;padding:1rem;border-radius:8px}table{width:100%;border-collapse:collapse}th,td{padding:.5rem;border-bottom:1px solid #ddd;text-align:left;vertical-align:top}.pass{color:#176b2c}.warn{color:#8a5a00}.fail{color:#a00000;font-weight:bold}.details{white-space:pre-wrap;font-family:monospace}</style></head><body>
<h1>Rapport de tests — Vite &amp; Gourmand</h1><p>Généré le <?=htmlspecialchars($report['generated_at'],ENT_QUOTES,'UTF-8')?> — durée <?=$report['duration_seconds']?> s</p>
<div class="cards"><div class="card">Total: <strong><?=$report['summary']['total']?></strong></div><div class="card pass">PASS: <strong><?=$report['summary']['pass']?></strong></div><div class="card warn">WARN: <strong><?=$report['summary']['warn']?></strong></div><div class="card fail">FAIL: <strong><?=$report['summary']['fail']?></strong></div></div>
<table><thead><tr><th>Catégorie</th><th>Test</th><th>Statut</th><th>Détails</th></tr></thead><tbody><?php foreach($report['results'] as $r): ?><tr><td><?=htmlspecialchars((string)$r['Category'],ENT_QUOTES,'UTF-8')?></td><td><?=htmlspecialchars((string)$r['Name'],ENT_QUOTES,'UTF-8')?></td><td class="<?=$statusClass((string)$r['Status'])?>"><?=htmlspecialchars((string)$r['Status'],ENT_QUOTES,'UTF-8')?></td><td class="details"><?=htmlspecialchars((string)($r['Details']??''),ENT_QUOTES,'UTF-8')?></td></tr><?php endforeach; ?></tbody></table></body></html>
'@
    $php=$php.Replace("__DATE__",$end.ToString("yyyy-MM-dd HH:mm:ss"))
    $php=$php.Replace("__DURATION__",[string]::Format([Globalization.CultureInfo]::InvariantCulture,[math]::Round(($end-$StartTime).TotalSeconds,2)))
    $php=$php.Replace("__TOTAL__",$summary.total).Replace("__PASS__",$summary.pass).Replace("__WARN__",$summary.warn).Replace("__FAIL__",$summary.fail).Replace("__JSON__",$json)
    Set-Content -Path $ReportPath -Value $php -Encoding UTF8

    Write-Host ""
    Write-Host "===== VITE & GOURMAND / TEST COMPLET ====="
    Write-Host "PASS : $($summary.pass)"
    Write-Host "WARN : $($summary.warn)"
    Write-Host "FAIL : $($summary.fail)"
    Write-Host "Rapport : $ReportPath"
    if($summary.fail -gt 0){exit 1}
}
catch{Write-Host "ERREUR TESTEUR : $($_.Exception.Message)" -ForegroundColor Red;exit 2}
finally{if($StartedServer -and $ServerProcess -and -not $ServerProcess.HasExited){Stop-Process -Id $ServerProcess.Id -Force -ErrorAction SilentlyContinue}}
