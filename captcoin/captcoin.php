<?php
// === Konfigurasi dasar ===
$loginUrl     = "https://captchacoin.site/login/";
$dashboardUrl = "https://captchacoin.site/dashboard/";
$earnUrl      = "https://captchacoin.site/captcha-type-and-earn/";
$ajaxUrl      = "https://captchacoin.site/wp-admin/admin-ajax.php";
$cookieJar    = __DIR__. "/cookies.txt";
if(file_exists($cookieJar)) unlink($cookieJar);
$uagent       = file(__DIR__ . '/../USRAGNT.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
//$uagent       = file('USRAGNT.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$userAgent    = $uagent[array_rand($uagent)];
echo "set UA => $userAgent\n";

// === Warna terminal ===
function color($text, $colorCode) { return "\033[{$colorCode}m{$text}\033[0m";}
function curlGet($url) {
    global $cookieJar, $userAgent;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_USERAGENT => "$userAgent"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
function curlPost($url, $data) {
    global $cookieJar, $userAgent;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_USERAGENT => "$userAgent"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// === Load config ===

 $username = getenv('USERNAME'); 
 $password = getenv('PASSWORD'); 
/*
$username = readline("user: ");
$password = readline("pass: "); */
$remember = '1';
if(!$username) die(color("⚠️ Username\n","31"));

// === STEP AWAL ===
system('clear');
echo color("=== CaptchaCoin Bot ===\n","36");

// --- LOGIN ---
$loginPage = curlGet($loginUrl . "?t=" . time());
preg_match('/name="_wpnonce" value="([^"]+)"/', $loginPage, $m); $wpnonce = $m[1]?? '';
preg_match('/name="form_id" value="([^"]+)"/', $loginPage, $m); $formId = $m[1]?? '21';
preg_match('/name="redirect_to" value="([^"]+)"/', $loginPage, $m); $redirectTo = $m[1]?? '';

$postFields = [
    "username-21" => $username,
    "user_password-21" => $password,
    "form_id" => $formId,
    "redirect_to" => $redirectTo,
    "_wpnonce" => $wpnonce,
    "_wp_http_referer" => "/login/"
];
if($remember === "1") $postFields["rememberme"] = $remember;

curlPost($loginUrl, $postFields);
echo color("✅ Login berhasil\n","32");

// --- Ambil Balance ---
function getBalance() {
    $dashboardPage = curlGet("https://captchacoin.site/dashboard/?t=". time());
    if(preg_match('/<div class="balance">\s*Balance:\s*<span>([^<]+)<\/span>/i', $dashboardPage, $match)) {
        return trim($match[1]);
}
    return "Tidak ditemukan";
}
echo color("💰 Balance: ". getBalance(). "\n", "33");

// --- LOOP CAPTCHA ---
$lastCaptcha = '';
while(true){

    $earnPage = curlGet($earnUrl. "?t=". time());
    $captcha = '';
    if(preg_match('/<div id="cte-captcha-box".*?>(.*?)<\/div>\s*<\/div>/is', $earnPage, $matchBox)){
        $boxHtml = $matchBox[1];
        if(preg_match('/<div[^>]*>\s*([A-Za-z0-9]{5,6})\s*<\/div>/is', $boxHtml, $matchCaptcha)){
            $captcha = trim($matchCaptcha[1]);
}
}

    if($captcha){
        if($captcha === $lastCaptcha){
            echo color("[SKIP] Captcha sama seperti sebelumnya: $captcha\n", "33");
            sleep(2);
            continue;
}
        $lastCaptcha = $captcha;
        $ajaxResponse = curlPost($ajaxUrl, ['cte_input' => $captcha, 'action' => 'cte_submit_captcha']);
        if(preg_match("/Correct! (\d+) BONK added\./i", $ajaxResponse, $m)){
            $bonk = $m[1];
            echo color("[CAPTCHA] $captcha → +$bonk BONK");
} else {
            echo color("[CAPTCHA] $captcha → gagal\n", "31");
}
} else {
        echo color("[!] Captcha tidak ditemukan\n", "31");
}

    sleep(2);
}
