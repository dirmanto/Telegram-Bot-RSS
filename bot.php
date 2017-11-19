<?php 
/* 
*****************************************************
* Bot Telegram - https://telegram.me/dirmantowebid **
*****************************************************
*/
require_once('bot_config.php');
/* Variabel Yang Digunakan Pada bot_config.php */
$max_age_articoli = time() - 1200;
/* Parameter ini tidak digunakan max_age_articoli */
$last_send = false;
$last_send_title = "";

/* Fungsi */
$time = date_default_timezone_set("ASIA/Jakarta");
$log_text = "[$time] Bot avviato. URL Feed: $rss".PHP_EOL;
file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
echo $log_text;
/* FUngsi Kontrol */
$pid = getmypid();
file_put_contents($pid_file, $pid);

/* Fungsi Pesan */
function telegram_send_chat_message($token, $chat, $messaggio) {
	/* prelievo timestamp attuale per eventuale log dell'errore */
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
	/* In caso di errore, lo salvo nei log */
	if ($result == FALSE) {
		$time = date("m-d-y H:i", time());
		$log_text = "[$time] Invio messaggio fallito: $messaggio".PHP_EOL;
		file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
	}
	curl_close($ch);
}

/* Perputaran Waktu Pesan Terkirim */
while (true) {
	/* Se $last_send non Ã¨ stata parametizzata, significa che il bot Ã¨ appena partito. La imposto quindi uguale a $max_age_articoli, che Ã¨ il tempo attuale - 20 minuti. PubblicherÃ  quindi retroattivamente tutte le notizie piÃ¹ vecchie di 20 minuti*/
	if ($last_send == false) $last_send = $max_age_articoli;
	$ora_attuale = time();
	$articoli = @simplexml_load_file($rss);
	/* Se non Ã¨ riuscito a scaricare il feed, pubblico un messaggio di errore nel log */
	if ($articoli === false) { 
		$time = date("m-d-y H:i", $ora_attuale);
		$log_text = "[$time] Il bot non Ã¨ riuscito a contattare il Feed RSS. Connessione fallita a $rss.".PHP_EOL;
		file_put_contents($log_file, $log_text, FILE_APPEND | LOCK_EX);
	/* Vado avanti solo se $articoli non Ã¨ in false, ciÃ² vuol dire che simplexml Ã¨ riuscito a caricare il feed e posso procedere a processare le notizie */	
	}else{
		/* Inverto l'ordine delle notizie, da decrescente a crescente */
		$xmlArray = array();
		foreach ($articoli->channel->item as $item) $xmlArray[] = $item;
		$xmlArray = array_reverse($xmlArray);
		
		/* Inizio ciclo invio notizie */
		foreach ($xmlArray as $item) {
			$timestamp_articolo = strtotime($item->pubDate);
			/* Controllo se la notizia Ã¨ piÃ¹ recente dell'ultima pubblicata */
			/* Anche se dovrebbe *non farlo* ma lo fa per ignoti motivi, ho aggiunto un controllo che dovrebbe evitare di far pubblicare due volte la stessa notizia */
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
