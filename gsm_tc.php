<?php
header('Content-Type: application/json; charset=utf-8');

/* Parametreler */
$token = $_GET['token'] ?? '';
$tc    = $_GET['tc'] ?? '';
$gsm   = $_GET['gsm'] ?? '';

/* TOKEN KONTROL */
if ($token !== 'mutluyasor23') {
    http_response_code(403);
    echo json_encode(['success'=>false,'hata'=>'token hatalı']);
    exit;
}

/* EN AZ BİRİ GEREKLİ */
if ($tc === '' && $gsm === '') {
    http_response_code(400);
    echo json_encode(['success'=>false,'hata'=>'tc veya gsm gerekli']);
    exit;
}

/* TEMİZLE */
$tc = preg_replace('/[^0-9]/','',$tc);
$gsm = preg_replace('/[^0-9]/','',$gsm);

/* DOSYALAR */
$gsmDosya = __DIR__.'/gsm.txt';
$tcDosya  = __DIR__.'/tcdata.txt';

if (!file_exists($gsmDosya)) {
    echo json_encode(['success'=>false,'hata'=>'gsm.txt dosyası yok']);
    exit;
}
if (!file_exists($tcDosya)) {
    echo json_encode(['success'=>false,'hata'=>'tcdata.txt dosyası yok']);
    exit;
}

/* GSM ↔ TC SORGULAMA */
$gsmIcerik = file_get_contents($gsmDosya);
$kayitlar = preg_split("/\n\s*\n/", trim($gsmIcerik));

foreach($kayitlar as $kayit){
    preg_match('/tc:\s*(\d+)/i',$kayit,$tcMatch);
    preg_match('/gsm:\s*(\d+)/i',$kayit,$gsmMatch);

    $tc_dosya = $tcMatch[1] ?? '';
    $gsm_dosya = $gsmMatch[1] ?? '';

    if($gsm !== '' && $gsm === $gsm_dosya){
        echo json_encode(['success'=>true,'sorgu'=>'gsm','data'=>['gsm'=>$gsm_dosya,'tc'=>$tc_dosya]],JSON_UNESCAPED_UNICODE);
        exit;
    }
    if($tc !== '' && $tc === $tc_dosya){
        /* TC bulundu → TC veri dosyasını oku */
        $tcVeriIcerik = file_get_contents($tcDosya);
        $tcKayitlar = preg_split("/\n\s*\n/", trim($tcVeriIcerik));
        $tcData = null;
        foreach($tcKayitlar as $tck){
            preg_match('/tc:\s*(\d+)/i',$tck,$m);
            if(!empty($m[1]) && $m[1] === $tc){
                /* tüm satırları anahtar-değer dizisine çevir */
                $lines = explode("\n",$tck);
                $tcData = [];
                foreach($lines as $line){
                    $line = trim($line);
                    if($line === '') continue;
                    $parts = explode(":",$line,2);
                    if(count($parts)==2){
                        $tcData[trim($parts[0])] = trim($parts[1]);
                    }
                }
                break;
            }
        }

        echo json_encode(['success'=>true,'sorgu'=>'tc','data'=>$tcData],JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/* BULUNAMAZSA */
echo json_encode(['success'=>false,'hata'=>'kayıt bulunamadı'],JSON_UNESCAPED_UNICODE);
