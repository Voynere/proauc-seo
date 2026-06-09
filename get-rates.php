<?php 
//узнаем дату
$today_date  = date("Y-m-d");
$cache_key = $_SERVER["DOCUMENT_ROOT"]."/rates.json";
//проверим, существует ли у нас в кэше файл на эту дату,
//если информация закеширована - выдать её, если нет - запросить у ЦБ РФ.
//if(!is_file($cache_key)){
    //С помощью CURL запросим информацию у ЦБ РФ
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://www.cbr-xml-daily.ru/daily_json.js');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($curl);
    curl_close($curl);

    file_put_contents($cache_key, $response );
//}
//Прочитаем данные из кэша
$rates = json_decode(file_get_contents($cache_key));    
//Отправим заголовок с уточнением кодировки XML

var_dump($rates);
echo "Обменный курс KRW по ЦБ РФ на сегодня: {$rates->Valute->KRW->Value}\n";
echo "Обменный курс JPY по ЦБ РФ на сегодня: {$rates->Valute->JPY->Value}\n";
echo "Обменный курс CNY по ЦБ РФ на сегодня: {$rates->Valute->CNY->Value}\n";