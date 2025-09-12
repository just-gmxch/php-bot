
<?php
date_default_timezone_set("Asia/Kuala_Lumpur");

echo "\n\033[1;35mðŸ”¥ COOLFAUCET CREATOR BY - AKIEFX ðŸ‡²ðŸ‡¾\033[0m\n";
/* echo "\033[1;36mMasukkan full Cookie:\033[0m ";
$cookie = trim(fgets(STDIN));
echo "\033[1;36mMasukkan User-Agent:\033[0m ";
$userAgent = trim(fgets(STDIN)); */

$cookie = file_get_contents('cookie.txt');
$uagent = file(__DIR__ . '/../USRAGNT.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$userAgent = $uagent[array_rand($uagent)];
echo "set UA => $userAgent\n";

$base = "https://beasthit.com";
$dashboard = "$base/dashboard";
$faucet = "$base/faucet";
$faucetClaim = "$base/faucet_claim";
$reward = "$base/claim-reward";
$claimCard = "$dashboard/claim_card";
$withdraw = "$base/withdraw";
$addDamage = "$dashboard/add_damage";
$referer = $base;

function curlGet($url, $cookie, $ua, $ref)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIE => $cookie,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => ["Referer: $ref"],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function curlPost($url, $data, $cookie, $ua, $ref)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_COOKIE => $cookie,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => [
            "Referer: $ref",
            "Content-Type: application/x-www-form-urlencoded",
        ],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function getMonsterDetails($url, $cookie, $ua, $ref)
{
    $html = curlGet($url, $cookie, $ua, $ref);
    preg_match(
        '/<span class="monster-title">([^<]+)<\/span>/',
        $html,
        $nameMatch,
    );
    preg_match(
        "/<strong>\s*HP:\s*(\d+)\s*\/\s*(\d+)\s*<\/strong>/",
        $html,
        $hpMatch,
    );
    preg_match("/XP:\s*(\d+)/", $html, $xpMatch);
    preg_match(
        "/Reward:\s*(\d+)\s*tokens\s*\(([\d\.]+)\s*LTC\)/",
        $html,
        $rewardMatch,
    );
    return [
        "name" => $nameMatch[1] ?? "Unknown",
        "hpNow" => intval($hpMatch[1] ?? 0),
        "hpMax" => intval($hpMatch[2] ?? 0),
        "xp" => intval($xpMatch[1] ?? 0),
        "tokens" => intval($rewardMatch[1] ?? 0),
        "ltc" => floatval($rewardMatch[2] ?? 0),
    ];
}

function getDamageStatus($url, $cookie, $ua, $ref)
{
    $html = curlGet($url, $cookie, $ua, $ref);
    if (
        preg_match(
            "/Damage:<\/span>\s*<span[^>]*>(\d+)\s*\/\s*(\d+)/",
            $html,
            $match,
        )
    ) {
        return [intval($match[1]), intval($match[2])];
    }
    return [null, null];
}

function getTotalDamage($url, $cookie, $ua, $ref)
{
    $html = curlGet($url, $cookie, $ua, $ref);
    if (
        preg_match(
            "/Your Total Damage:\s*<span[^>]*>(\d+)<\/span>/",
            $html,
            $match,
        )
    ) {
        return intval($match[1]);
    }
    return null;
}

function getTokenBalance($html)
{
    if (
        preg_match(
            "/Balance:<\/span>\s*<span[^>]*>\s*(\d+)\s+tokens/i",
            $html,
            $match,
        )
    ) {
        return intval($match[1]);
    }
    return 0;
}

function addDamage($url, $cookie, $ua, $ref)
{
    curlPost($url, ["amount" => "20"], $cookie, $ua, $ref);
    echo "[" . date("H:i:s") . "] ðŸ§¨ Damage +20 dihantar\n";
}

function attackMonster($url, $cookie, $ua, $ref)
{
    curlPost($url, ["attack" => 1], $cookie, $ua, $ref);
    echo "[" . date("H:i:s") . "] âš”ï¸ Serangan dihantar\n";
}

function claimReward($url, $cookie, $ua, $ref)
{
    curlPost($url, [], $cookie, $ua, $ref);
    echo "[" . date("H:i:s") . "] ðŸŽ Reward dituntut\n";
}

function claimCard($statusUrl, $claimUrl, $cookie, $ua, $ref)
{
    $html = curlGet($statusUrl, $cookie, $ua, $ref);
    if (strpos($html, "Inventory Card") !== false) {
        echo "[" . date("H:i:s") . "] ðŸƒ Kartu ditemukan! Mengklaim...\n";
        curlPost($claimUrl, [], $cookie, $ua, $ref);
    }
}
function getBalance($url, $cookie, $ua, $ref)
{
    $html = curlGet($url, $cookie, $ua, $ref);
    if (preg_match("/\(([\d\.]+)\s*LTC\)/i", $html, $m)) {
        return floatval($m[1]);
    }
    return 0;
}

function autoWithdraw($url, $cookie, $ua, $ref)
{
    curlPost(
        $url,
        ["withdraw_all" => "1", "currency" => "LTC"],
        $cookie,
        $ua,
        $ref,
    );
    echo "[" . date("H:i:s") . "] ðŸ’¸ Auto WD dihantar\n";
}

function readCaptchaInstruction($html)
{
    $validColors = ["red", "orange", "yellow", "green", "blue", "purple"];
    if (
        preg_match(
            "/Click\s+the\s+<strong>(\w+)<\/strong>\s+circle/i",
            $html,
            $match,
        )
    ) {
        $color = strtolower($match[1]);
        if (in_array($color, $validColors)) {
            return $color;
        }
    }
    foreach ($validColors as $color) {
        if (preg_match("/click. _?$color._?circle/i", $html)) {
            return $color;
        }
    }
    return null;
}

function claimFaucet($cookie, $ua, $ref)
{
    $html = curlGet("https://coolfaucet.hu/faucet", $cookie, $ua, $ref);
    file_put_contents("debug_faucet.html", $html);
    if (strpos($html, "Faucet not ready yet") !== false) {
        echo "[" .
            date("H:i:s") .
            "] â„1¤7 Faucet belum sedia. Tunggu sebentar...\n";
        return;
    }
    $color = readCaptchaInstruction($html);
    if ($color) {
        echo "[" . date("H:i:s") . "] ðŸ§  Arahan captcha: klik warna '$color'\n";
        $res = curlPost(
            "https://coolfaucet.hu/faucet_claim",
            ["color" => $color],
            $cookie,
            $ua,
            $ref,
        );
        echo "[" .
            date("H:i:s") .
            "] âœ„1¤7 Faucet diklaim dengan warna '$color'\n";
        echo "[" . date("H:i:s") . "] ðŸ“© Respons: " . strip_tags($res) . "\n";
    } else {
        echo "[" . date("H:i:s") . "] âš ï¸ Arahan captcha tidak dijumpai\n";
    }
}

function countdown($sec)
{
    while ($sec > 0) {
        echo "\033[1;33mâ„1¤7 Menunggu: {$sec}s\r\033[0m";
        sleep(1);
        $sec--;
    }
    echo "\n";
}

$lastFaucet = time();
while (true) {
    echo "\n[" . date("H:i:s") . "] ðŸ”„ Kitaran bermula...\n";

    $monster = getMonsterDetails($dashboard, $cookie, $userAgent, $referer);
    echo "[" . date("H:i:s") . "] ðŸ‰ Monster: {$monster["name"]}\n";
    echo "[" .
        date("H:i:s") .
        "] â¤ï¸ HP: {$monster["hpNow"]} / {$monster["hpMax"]} | ðŸ§  XP: {$monster["xp"]}\n";
    echo "[" .
        date("H:i:s") .
        "] ðŸŽ Reward: {$monster["tokens"]} tokens ({$monster["ltc"]} LTC)\n";

    [$dmgNow, $dmgMax] = getDamageStatus(
        $dashboard,
        $cookie,
        $userAgent,
        $referer,
    );
    echo "[" . date("H:i:s") . "] ðŸ’¥ Damage: $dmgNow / $dmgMax\n";

    $totalDamage = getTotalDamage($dashboard, $cookie, $userAgent, $referer);
    if (!is_null($totalDamage)) {
        echo "[" .
            date("H:i:s") .
            "] ðŸ§¨ Total Damage Keseluruhan: $totalDamage\n";
    }

    if ($dmgNow < $dmgMax) {
        addDamage($addDamage, $cookie, $userAgent, $referer);
    }

    if ($monster["hpNow"] > 0) {
        attackMonster($dashboard, $cookie, $userAgent, $referer);
    } else {
        claimReward($reward, $cookie, $userAgent, $referer);
    }

    claimCard($dashboard, $claimCard, $cookie, $userAgent, $referer);

    $balance = getBalance($dashboard, $cookie, $userAgent, $referer);
    echo "[" . date("H:i:s") . "] ðŸ’° Balance: \033[1;32m$balance LTC\033[0m\n";

    $dashboardHtml = curlGet($dashboard, $cookie, $userAgent, $referer);
    $tokenBalance = getTokenBalance($dashboardHtml);
    echo "[" . date("H:i:s") . "] ðŸª™ Token Balance: $tokenBalance tokens\n";

    if ($tokenBalance >= 10000) {
        autoWithdraw($withdraw, $cookie, $userAgent, $referer);
    }

    if (time() - $lastFaucet >= 300) {
        claimFaucet($cookie, $userAgent, $referer);
        $lastFaucet = time();
    }
    
    echo "tunggu 60s";
    sleep(60);
}

