<?php 
/* 
************************************************************
* RSS Feed Reader Bot - https://telegram.me/dirmantowebid **
************************************************************
*/
require_once('bot_config.php');
/* Variabel Yang Digunakan Pada bot_config.php */
$max_age_articoli = time() - 1200;
/* Parameter ini tidak digunakan max_age_articoli */
$last_send = false;
$last_send_title = "";

/* Fungsi */
$time = date_default_timezone_set("ASIA/Jakarta");
$log_text = "[$time] Bot RSS Blog. URL Feed: $rss".PHP_EOL;
file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
echo $log_text;
/* Fungsi Kontrol */
$pid = getmypid();
file_put_contents($pid_file, $pid);

/* Fungsi Pesan */
function telegram_send_chat_message($token, $chat, $messaggio) {
	/* Jika Error */
	$time = time();
	/* URL Variabel */
	$url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat";
	/* Pesan Terkirim */
	$send_text=urlencode($messaggio);
	$url = $url ."&text=$send_text";
	//inizio sessione curl 
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
		$log_text = "[$time] Kirim Pesan Error: $messaggio".PHP_EOL;
		file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
	}
	curl_close($ch);
}

/* Perputaran Waktu Pesan Terkirim */
while (true) {
	/* Se $last_send Bot Berjalan $max_age_articoli, Update informasi akan disampaikan dengan interval berita 20 menit terakhir*/
	if ($last_send == false) $last_send = $max_age_articoli;
	$ora_attuale = time();
	$articoli = @simplexml_load_file($rss);
	/* Lihat Log jika ada pesan error */
	if ($articoli === false) { 
		$time = date("m-d-y H:i", $ora_attuale);
		$log_text = "[$time] Bot gagal menerima informasi feed $rss.".PHP_EOL;
		file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
	/* Bot Melihat $articoli berita disampaikan */	
	}else{
		/* Menerima berita RSS */
		$xmlArray = array();
		foreach ($articoli->channel->item as $item) $xmlArray[] = $item;
		$xmlArray = array_reverse($xmlArray);
		
		/* Mulai putaran berita */
		foreach ($xmlArray as $item) {
			$timestamp_articolo = strtotime($item->pubDate);
			/* Memeriksa Berita */
			/* Jika berita yang sam diterima */
			if ($timestamp_articolo > $last_send and $last_send_title != $item->title) {
				$messaggio = ucfirst($item->category) . " - " . $item->title . PHP_EOL;
				$messaggio .= $item->link . PHP_EOL;
				telegram_send_chat_message($token, $chat, $messaggio);
				$last_send = $timestamp_articolo;
				$last_send_title = $item->title;
			}
		}
	}
	sleep($attesa);
}
?>
