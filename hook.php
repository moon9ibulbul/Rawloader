<?php
// hook.php
$TOKEN = '8262988385:AAFtwQhtBestsgRvYs3Zyxzh-uojD-cWgl8';  // simpan via env di produksi
$input = file_get_contents('php://input');
$update = json_decode($input, true);

function sendPhoto($chat_id, $pathOrUrl, $caption='') {
  global $TOKEN;
  $url = "https://api.telegram.org/bot{$TOKEN}/sendPhoto";
  $post_fields = ['chat_id' => $chat_id, 'caption' => $caption];

  if (preg_match('#^https?://#', $pathOrUrl)) {
    $post_fields['photo'] = $pathOrUrl; // URL langsung
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POSTFIELDS => $post_fields,
    ]);
  } else {
    // file lokal
    $post_fields['photo'] = new CURLFile(realpath($pathOrUrl));
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POSTFIELDS => $post_fields,
    ]);
  }
  $res = curl_exec($ch);
  curl_close($ch);
  return $res;
}

if (!$update) { http_response_code(200); exit; }

if (isset($update['message'])) {
  $msg = $update['message'];
  $chat_id = $msg['chat']['id'];
  $text = $msg['text'] ?? '';

  if (str_starts_with($text, '/start')) {
    $reply = "Halo! Kirim /qris untuk menerima QRIS default.";
    file_get_contents("https://api.telegram.org/bot{$TOKEN}/sendMessage?chat_id={$chat_id}&text=" . urlencode($reply));
  }
  if (str_starts_with($text, '/qris')) {
    // TODO: parsing key opsional dari argumen. Untuk demo: kirim qris/default.jpg
    $path = __DIR__ . '/qris/default.jpg';
    if (file_exists($path)) {
      sendPhoto($chat_id, $path, 'QRIS: default');
    } else {
      file_get_contents("https://api.telegram.org/bot{$TOKEN}/sendMessage?chat_id={$chat_id}&text=" . urlencode("QRIS default belum ada."));
    }
  }
}

http_response_code(200);
