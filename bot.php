<?php 
/* 
*
* Fungsi: Membaca RSS Sebuah Blog atau Website
* Tutorial: https://wp.me/p5DRvJ-en
* Telegram Grup: @dirmantowebid - https://t.me/dirmantowebid
* Modifikasi Terakhir: Desember 2017
*
*/
require_once('bot_config.php');
/* Variabel Yang Digunakan Pada bot_config.php */
$max_age_articles = time() - 240;
/* Parameter ini tidak digunakan max_age_articles */
$last_send = false;
$last_send_title = "";

/* Fungsi */
$time = date_default_timezone_set("ASIA/Jakarta");
$log_text = "[$time] Berjalan... URL Feed: $rss".PHP_EOL;
file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
echo $log_text;
/* Fungsi Kontrol */
$pid = getmypid();
file_put_contents($pid_file, $pid);

/* Fungsi Pesan */
function telegram_send_chat_message($token, $chat, $message) {
	/* Jika Error */
	$time = time();
	/* URL Variabel */
	$url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat";
	/* Pesan Terkirim */
	$send_text=urlencode($message);
	$url = $url ."&text=$send_text";
	//Mulai Sesi cURL 
	$ch = curl_init();
	$optArray = array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true
	);
	curl_setopt_array($ch, $optArray);
	$result = curl_exec($ch);
	/* Simpan Error Log */
	if ($result == FALSE) {
		$time = date("m-d-y H:i", time());
		$log_text = "[$time] Kirim Pesan Error: $message".PHP_EOL;
		file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
	}
	curl_close($ch);
}

/* Perputaran Waktu Pesan Terkirim */
while (true) {
	/* Update informasi akan disampaikan dengan interval berita 5 menit terakhir */
	if ($last_send == false) $last_send = $max_age_articles;
	$current_time = time();
	$articles = @simplexml_load_file($rss);
	/* Lihat Log jika ada pesan error */
	if ($articles === false) { 
		$time = date("m-d-y H:i", $current_time);
		$log_text = "[$time] Bot gagal menerima informasi $rss.".PHP_EOL;
		file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
	/* Bot Membaca Berita Disampaikan */	
	}else{
		/* Menerima berita RSS */
		$xmlArray = array();
		foreach ($articles->channel->item as $item) $xmlArray[] = $item;
		$xmlArray = array_reverse($xmlArray);
		
		/* Mulai putaran berita */
		foreach ($xmlArray as $item) {
			$timestamp_article = strtotime($item->pubDate);
			/* Memeriksa Berita */
			/* Jika berita yang sam diterima */
			if ($timestamp_article > $last_send and $last_send_title != $item->title) {
				$message = ucfirst($item->category) . " - " . $item->title . PHP_EOL;
				$message .= $item->link . PHP_EOL;
				telegram_send_chat_message($token, $chat, $message);
				$last_send = $timestamp_article;
				$last_send_title = $item->title;
			}
		}
	}
	sleep($wait);
}
?>
