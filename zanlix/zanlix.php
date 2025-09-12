```php
<?php
date_default_timezone_set("Asia/Kuala_Lumpur");

// === Warna terminal
function colorText($text, $color = "white") {
    $colors = [
        "white" => "\033[1;37m",
        "green" => "\033[0;32m",
        "aqua" => "\033[1;36m",
        "darkpink" => "\033[1;35m",
        "skyblue" => "\033[1;34m",
        "reset" => "\033[0m"
    ];
    return $colors[$color]. $text. $colors["reset"];
}

// === Countdown minit:detik
function countdown($seconds) {
    for ($i = $seconds; $i>= 0; $i--) {
        $m = floor($i / 60);
        $s = $i % 60;
        echo "\r". colorText(sprintf("⏳ Menunggu %02d:%02d (min:s)", $m, $s), "aqua");
        flush();
        sleep(1);
}
    echo "\r". str_repeat(" ", 50). "\r";
}

// === Input pengguna
echo "\n". colorText("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", "aqua"). "\n";
echo colorText("🔥 BOT AUTO ATTACK ZANLIX", "green"). "\n";
echo colorText("CREATED BY: AKIEFX", "white"). "\n";
echo colorText("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", "aqua"). "\n";
echo colorText("Masukkan full cookie:", "white"). " ";
/* $cookie = trim(fgets(STDIN));
echo colorText("Masukkan User-Agent:", "white"). " "; */
$cookie = file_get_contents('cookie.txt'); 
/* $uagent = file(__DIR__ . 'USRAGNT.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); */
$uagent = file(__DIR__ . '/../USRAGNT.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$userAgent = $uagent[array_rand($uagent)];
echo "set UA => $userAgent\n";

// === Konfigurasi
$delay = 60;
$healThreshold = 300;
$monsterList = ["shadow_leviathan", "ice_wolf", "fire_dragon"];
$stats = ["damage" => 0, "wins" => 0, "attacks" => 0];
$i = 1;

// === Fungsi GET
function sendGet($url, $cookie, $userAgent) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_COOKIE => $cookie
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// === Semak HP pemain
function getPlayerHP($cookie, $userAgent) {
    $html = sendGet("https://zanlix.eu/game", $cookie, $userAgent);
    preg_match('/HP:\s*(\d+)/', $html, $match);
    return isset($match[1])? intval($match[1]): null;
}

// === Semak HP monster
function getMonsterHP($monster, $cookie, $userAgent) {
    $html = sendGet("https://zanlix.eu/game", $cookie, $userAgent);
    preg_match('/HP:\s*(\d+)\s*\/\s*\d+/', $html, $match);
    return isset($match[1])? intval($match[1]): null;
}

// === Auto heal
function autoHeal($cookie, $userAgent) {
    $url = "https://zanlix.eu/game";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => "heal=heal",
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_COOKIE => $cookie,
        CURLOPT_HTTPHEADER => ["Referer: $url"]
    ]);
    curl_exec($ch);
    curl_close($ch);
    echo colorText("💊 Auto Heal dihantar!", "green"). "\n";
    file_put_contents("log_zanlix.txt", date("Y-m-d H:i:s"). " - Heal sent\n", FILE_APPEND);
}

// === Serangan
function attackMonster($monster, &$stats, $cookie, $userAgent) {
    $url = "https://zanlix.eu/game/attack";
    $htmlCheck = sendGet($url, $cookie, $userAgent);
    if (!preg_match('/ATTACK/i', $htmlCheck)) {
        echo colorText("❌ Tombol ATTACK tidak dijumpai. Skip...\n", "darkpink");
        return false;
}

    echo colorText("⚡ Menyiapkan serangan...", "aqua"). "\n";
    usleep(500000); // animasi 0.5s

    $payload = ["attack" => "ATTACK", "enemy" => $monster];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_COOKIE => $cookie,
        CURLOPT_HTTPHEADER => ["Referer: $url"]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $stats["attacks"]++;
    file_put_contents("log_zanlix.txt", date("Y-m-d H:i:s"). " - ATTACK $monster\n", FILE_APPEND);

    echo colorText("📤 Serangan dihantar ke [$monster]...", "aqua"). "\n";

    if (preg_match('/damage[:\s]+(\d+)/i', $response, $match)) {
        $damage = intval($match[1]);
        $stats["damage"] += $damage;
        if ($damage>= 1000) {
            echo colorText("💥 CRITICAL HIT! Damage: $damage", "darkpink"). "\n";
} else {
            echo colorText("🔸 Damage biasa: $damage", "white"). "\n";
}
}

    if (strpos($response, 'success')!== false || strpos($response, 'damage')!== false) {
        echo colorText("⚔️ [$monster] Serangan berjaya!", "green"). "\n";
        return true;
} else {
        echo colorText("❌ [$monster] Gagal.\n", "darkpink");
        return false;
}
}

// === Loop tanpa had
while (true) {
    echo colorText("🔁 Pusingan ke-$i", "skyblue"). "\n";

    $hp = getPlayerHP($cookie, $userAgent);
    echo colorText("❤️ HP Pemain: $hp", "white"). "\n";
    if ($hp!== null && $hp < $healThreshold) {
        autoHeal($cookie, $userAgent);
}

    // Reset monster list jika habis
    if (!isset($currentMonster) || $currentMonster>= count($monsterList)) {
        $currentMonster = 0;
        echo colorText("🔄 Reset monster list...", "aqua"). "\n";
}

    $monster = $monsterList[$currentMonster];
    $monsterHP = getMonsterHP($monster, $cookie, $userAgent);
    echo colorText("🧟 HP $monster: $monsterHP", "white"). "\n";

    if ($monsterHP!== null && $monsterHP <= 0) {
        echo colorText("✅ [$monster] dikalahkan! Tukar monster...\n", "darkpink");
        echo colorText("🎁 Anda menerima 75 tokens sebagai ganjaran!", "green"). "\n";
        $stats["wins"]++;
        $currentMonster++;
        continue;
}

    attackMonster($monster, $stats, $cookie, $userAgent);
    echo "tunggu 60s";
    sleep($delay);
    $i++;
}
?>
```