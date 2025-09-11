<?php

error_reporting(0);
const versi   = "0.0.2";
const host    = "https://zerofaucet.com/";
const youtube = "https://youtube.com/@iewil";

$wallet = getenv('LOGIN');

/* $uagent = file('USRAGNT.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); */
$uagent = file(__DIR__ . '/../USRAGNT.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); 

$userAgent = $uagent[array_rand($uagent)];
echo "set UA => $userAgent\n";

function curl(string $method, string $url, $headers = null, $data = null) {
    $method = strtoupper($method);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIE, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
    curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function line() {
    print str_repeat("~", 50)."\n";
}

function banner($wallet) {
    print "Author\t\t:: iewil\nTitle\t\t:: Zerofaucet\n";
}

class Tesseract {
    private $isWindows, $img, $frame, $cleaned, $outputFile;

    public function __construct() {
        $this->isWindows  = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->img        = "img.png";
        $this->frame      = "frame.png";
        $this->cleaned    = "cleaned.png";
        $this->outputFile = "hasil";
        $this->checkModul();
    }

    private function isModulAvailable($command) {
        $null = $this->isWindows ? '2>NUL' : '2>/dev/null';
        $check = shell_exec("$command $null");
        return !empty($check);
    }

    private function checkModul() {
        if (!$this->isModulAvailable("tesseract -v")) {
            print("[!] Tesseract not installed!\n");
            exit;
        }
        $magickCmd = $this->isWindows ? 'magick' : 'convert';
        if (!$this->isModulAvailable("$magickCmd -version")) {
            print("[!] ImageMagick not installed!\n");
            exit;
        }
    }

    private function silent_exec($cmd) {
        $null = $this->isWindows ? '2>NUL' : '2>/dev/null';
        shell_exec("$cmd $null");
    }

    public function Zerofaucet($base64Image) {
        $start = microtime(true);
        file_put_contents($this->img, base64_decode($base64Image));
        $imgPath     = escapeshellarg($this->img);
        $framePath   = escapeshellarg($this->frame);
        $cleanedPath = escapeshellarg($this->cleaned);
        $outputPath  = escapeshellarg($this->outputFile);

        $magickCmd = $this->isWindows ? 'magick' : 'convert';
        $this->silent_exec("$magickCmd $imgPath -colorspace Gray $framePath");
        $this->silent_exec("$magickCmd $framePath -morphology Close Octagon -blur 1x1 $cleanedPath");
        $this->silent_exec("tesseract $cleanedPath $outputPath -l eng --psm 7");

        $hasil = file_get_contents($this->outputFile.'.txt');
        @unlink($this->frame);
        @unlink($this->cleaned);
        @unlink($this->outputFile . '.txt');
        @unlink($this->img);

        $duration = microtime(true) - $start;
        print "Solved ".round($duration * 1000, 2)." ms\n";
        return htmlspecialchars(trim($hasil));
    }
}

// === Start ===
banner($wallet);
$tesseract = new Tesseract();

$h = [
    "host: zerofaucet.com",
    "user-agent: $userAgent"
];

$r = curl("GET", host."index.php?loginwallet=".$wallet."&ref=", $h);
$balance = trim(explode("\n", explode("<font size='5' color='#7d5f2c'>My Balance", $r)[1])[1]);
print "Balance\t\t:: $balance\n";
line();

$balance_awal = $balance;
while (true) {
    $r = curl("GET", host."index.php?loginwallet=".$wallet."&ref=", $h);
    $location = explode('"', explode('window.location.href = "', $r)[1])[0];

    if ($location == "https://zerofaucet.com/dailygift.php") {
        print "Dailygift\n";
        $img = curl("GET", host."captcha.php?loginwallet=".$wallet, array_merge($h,["referer: https://zerofaucet.com/dailygift.php"]));
        $cap = $tesseract->Zerofaucet(base64_encode($img));
        $r = curl("GET", host."dailygift.php?easycaptcha=".$cap."&action=Collect", $h);
        $win = strip_tags(explode('</font>', explode('font color="white" size="6" class="msgClass">', $r)[1])[0]);
        print "You win\t\t:: $win\n";

        $r = curl("GET", host."index.php?loginwallet=".$wallet."&ref=", $h);
        $balance = trim(explode("\n", explode("<font size='5' color='#7d5f2c'>My Balance", $r)[1])[1]);
        print "Balance\t\t:: $balance\n";
        line();
        $balance_awal = $balance;
        continue;
    }

    $r = curl("POST", host."index.php?claim=1", $h);
    sleep(1);

    $location = explode('"', explode('window.location.href = "', $r)[1])[0];
cek_loc:
    $r = curl("GET", $location);
    sleep(1);
    $link_hash = explode('"', explode('<a href="https://zerofaucet.com/', $r)[1])[0];
    if (!$link_hash) {
        $list_data = explode('"', explode('<a id="countingbtn" href="https://1ink.cc/go.php?', $r)[1])[0];
        $location = curl("POST", "https://1ink.cc/api/pass.php","", $list_data."&captcha=");
        if ($location) goto cek_loc;
    }

    $r = curl("GET", host.$link_hash, $h);
    sleep(1);
    $confirm = explode('"', explode('<a href="index.php?confirm1=', $r)[1])[0];
    $r = curl("GET", host."index.php?confirm1=".$confirm, $h);
    sleep(1);
    $reward = strip_tags(explode('</font>', explode("<font size='6' color='#ffff00'><b>", $r)[1])[0]);

    $r = curl("GET", host."index.php?loginwallet=".$wallet."&ref=", $h);
    $balance = trim(explode("\n", explode("<font size='5' color='#7d5f2c'>My Balance", $r)[1])[1]);

    if ($balance_awal == $balance) {
        print "land under attack!\n";

        $r = curl("GET", host."enemy.php", $h);
        $img = curl("GET", host."captcha.php?loginwallet=".$wallet, array_merge($h,["referer: https://zerofaucet.com/enemy.php"]));
        $cap = $tesseract->Zerofaucet(base64_encode($img));
        $r = curl("GET", host."enemy.php?easycaptcha=".$cap."&action=Attack", $h);

        line();
        echo('wait 60s\n');
        continue;
    }

    print "Reward\t\t:: $reward\n";
    $r = curl("GET", host."index.php?loginwallet=".$wallet."&ref=", $h);
    $balance = trim(explode("\n", explode("<font size='5' color='#7d5f2c'>My Balance", $r)[1])[1]);
    print "Balance\t\t:: $balance\n";
    line();
    $balance_awal = $balance;
    echo('wait 60s\n');
    sleep(60);
}