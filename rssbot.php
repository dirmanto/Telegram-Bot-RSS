<?php 
/* 
*
* Fungsi: Membaca RSS Sebuah Blog atau Website
* Tutorial: https://wp.me/p5DRvJ-en
* Versi PHP: 5. hingga 7. --dengan penyesuaian--
* Telegram Grup: Personal Blog Indonesia - https://t.me/personalblogid
* Modifikasi Terakhir: 13 Oktober 2018
*
*/
/* Token API Telegram. Dari @BotFather */ 
$token = 'ABCDEFGHIJKLMNOPQRTSUVWXYZ:1234567890';

/* Isi Dengan Grup ID */
$chat = '-1234567890';
/* Sumber RSS Feed */
$rss = 'https://feeds.feedburner.com/KangDirmantelegram';

/* Log Disimpan */
$log_file = 'bot-rss.log';

/* Proses PID Bot */
$pid_file = 'bot-rss.pid';

/* Timer Waktu */
$wait = 120;

/* Waktu */
$max_age_articles = time() - 240;
/* Parameter ini tidak digunakan, silahkan dikembangkan sendiri */
$last_send = false;
$last_send_title = "";

/* Bot Berjalan */
$time = date_default_timezone_set("ASIA/Jakarta");
$log_text = "[$time] Berjalan... URL Feed: $rss".PHP_EOL;
file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
echo $log_text;
/* Bot PID */
$pid = getmypid();
file_put_contents($pid_file, $pid);

/* API Pesan */
function telegram_send_chat_message($token, $chat, $message, $reply_markup) {
	/* Jika Error */
	$time = time();
	/* URL Variabel */
	$url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat&reply_markup=$reply_markup";
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
			/* Jika ada Berita, Sampaikan.. */
			if ($timestamp_article > $last_send and $last_send_title != $item->title) {
				$message = ucfirst($item->category) . " - " . $item->title . PHP_EOL;
				$message .= $item->link . PHP_EOL;
				$message = urlencode( $message );
				$reply_markup = json_encode( array(
					'inline_keyboard' => array(
						array(
							array(
								'text' => '🔗 Baca Aku',
								'url'  => urlencode( $item->link ),
							)
						)
					),
				) );
				telegram_send_chat_message($token, $chat, $message,$reply_markup);
				$last_send = $timestamp_article;
				$last_send_title = $item->title;
			}
		}
	}
	sleep($wait);
}
?>
