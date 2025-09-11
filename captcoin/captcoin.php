<?php
// === Konfigurasi dasar ===
$loginUrl     = "https://captchacoin.site/login/";
$dashboardUrl = "https://captchacoin.site/dashboard/";
$earnUrl      = "https://captchacoin.site/captcha-type-and-earn/";
$ajaxUrl      = "https://captchacoin.site/wp-admin/admin-ajax.php";
$withdrawUrl  = "https://captchacoin.site/withdrwal/";
$cookieJar    = __DIR__ . "/cookies.txt";

$uagentList = file(__DIR__ . '/../USRAGNT.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$minWithdraw = 581;

// === Fungsi curl ===
function curlGet($url,$ua){
    global $cookieJar;
    $ch = curl_init();
    curl_setopt_array($ch,[
        CURLOPT_URL=>$url,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_COOKIEJAR=>$cookieJar,
        CURLOPT_COOKIEFILE=>$cookieJar,
        CURLOPT_USERAGENT=>$ua
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}
function curlPost($url,$data,$ua){
    global $cookieJar;
    $ch = curl_init();
    curl_setopt_array($ch,[
        CURLOPT_URL=>$url,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_COOKIEJAR=>$cookieJar,
        CURLOPT_COOKIEFILE=>$cookieJar,
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>http_build_query($data),
        CURLOPT_USERAGENT=>$ua
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// === Fungsi login ===
function login($username,$password,$ua){
    global $loginUrl;
    $page = curlGet($loginUrl."?t=".time(),$ua);
    preg_match('/name="_wpnonce" value="([^"]+)"/',$page,$m); $wpnonce=$m[1]??'';
    $post = [
        "username-21"=>$username,
        "user_password-21"=>$password,
        "form_id"=>"21",
        "redirect_to"=>"",
        "_wpnonce"=>$wpnonce,
        "_wp_http_referer"=>"/login/",
        "rememberme"=>"1"
    ];
    curlPost($loginUrl,$post,$ua);
}

// === Fungsi get balance ===
function getBalance($ua){
    global $dashboardUrl;
    $page = curlGet($dashboardUrl."?t=".time(),$ua);
    if(preg_match('/Balance:\s*<span>([^<]+)<\/span>/i',$page,$m)){
        return floatval($m[1]);
    }
    return 0;
}

// === Fungsi withdraw ===
function withdraw($amount,$walletId,$ua){
    global $ajaxUrl;
    $post = [
        'action'=>'cte_submit_withdrawal',
        'cte_withdraw_method'=>'0',
        'cte_withdraw_amount'=>$amount,
        'cte_withdraw_details'=>$walletId
    ];
    return curlPost($ajaxUrl,$post,$ua);
}

// === Fungsi captcha ===
function submitCaptcha($ua){
    global $earnUrl,$ajaxUrl;
    $earnPage = curlGet($earnUrl."?t=".time(),$ua);
    if(preg_match('/<div id="cte-captcha-box".*?>(.*?)<\/div>\s*<\/div>/is',$earnPage,$matchBox)){
        if(preg_match('/<div[^>]*>\s*([A-Za-z0-9]{5,6})\s*<\/div>/is',$matchBox[1],$matchCaptcha)){
            $captcha = trim($matchCaptcha[1]);
            $res = curlPost($ajaxUrl,['cte_input'=>$captcha,'action'=>'cte_submit_captcha'],$ua);
            if(preg_match("/Correct! (\d+) BONK added\./i",$res,$m)){
                return [$captcha,intval($m[1])];
            }
            return [$captcha,0];
        }
    }
    return [false,0];
}

// === Input multi akun (copy-paste) ===
$input = getenv('LOGIN');
$password = getenv('PASS');
$items = explode(" ", $input);
$accounts = [];
foreach($items as $itm){
    $parts = explode(",", $itm);
    if(count($parts) == 2){
        $accounts[] = ["username"=>trim($parts[0]), "wallet"=>trim($parts[1])];
    }
}
if(empty($accounts)) die("⚠️ Tidak ada akun valid diinput\n");

/* $accounts = [];
echo "Masukkan akun (format: user1,wallet1 user2,wallet2 ...): ";
$line = readline();
$items = explode(" ", $line); 
foreach($items as $itm){
    $parts = explode(",", $itm);
    if(count($parts) == 2){
        $accounts[] = ["username"=>trim($parts[0]), "wallet"=>trim($parts[1])];
    } else {
        echo "⚠️ Format akun '$itm' salah, dilewati\n";
    }
}

if(empty($accounts)) die("⚠️ Tidak ada akun valid diinput\n");*/

// $accounts siap dipakai
print_r($accounts); // cek array

// === Main loop ===
foreach($accounts as $acc){
    $username = $acc['username'];
    $walletId = $acc['wallet'];
    $ua = $uagentList[array_rand($uagentList)];
    if(file_exists($cookieJar)) unlink($cookieJar);

    echo "\n=== Memproses akun $username dengan UA: $ua ===\n";

    login($username,$password,$ua);
    echo "✅ Login sukses\n";

    for($i=1;$i<=200;$i++){
        [$captcha,$bonk] = submitCaptcha($ua);
        if($captcha){
            echo "[CAPTCHA $i] $captcha → +$bonk BONK\n";
        } else {
            echo "[CAPTCHA $i] tidak ditemukan\n";
        }
        sleep(2);
    }

    $balance = getBalance($ua);
    echo "💰 Balance akhir: $balance\n";
    if($balance>=$minWithdraw){
        $withdrawAmount = rand($minWithdraw, floor($balance));
        $res = withdraw($withdrawAmount,$walletId,$ua);
        echo "💸 Withdraw: $res\n";
    }

    echo "=== Selesai akun $username ===\n";
}
