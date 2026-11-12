<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib.php';
header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer'); header('X-Content-Type-Options: nosniff');
try {
    $token=$_GET['key'] ?? ''; $id=dp_valid_token($conn,$token); $channel=filter_input(INPUT_GET,'channel',FILTER_VALIDATE_INT);
    if (!$id || !$channel) { http_response_code(403); exit; }
    $q=$conn->prepare('SELECT c.source_url FROM dp_channels c JOIN dp_playlist_channels pc ON pc.channel_id=c.id WHERE pc.playlist_id=? AND c.id=?'); $q->execute([$id,$channel]); $url=$q->fetchColumn();
    if (!$url) { http_response_code(404); exit; }
    $started=false; $lastAuthCheck=time();
    for ($attempt=0; $attempt<5; $attempt++) {
        $ch=dp_curl($url); $code=0; $buffer=''; $location='';
        curl_setopt_array($ch, [CURLOPT_TIMEOUT=>14400, CURLOPT_LOW_SPEED_LIMIT=>1, CURLOPT_LOW_SPEED_TIME=>30,
            CURLOPT_HEADERFUNCTION=>function($ch,$line) use (&$code,&$location) { if (preg_match('~^HTTP/\S+\s+(\d+)~i',$line,$m)) { $code=(int)$m[1]; $location=''; } elseif (stripos($line,'Location:')===0) $location=trim(substr($line,9)); return strlen($line); },
            CURLOPT_WRITEFUNCTION=>function($ch,$data) use (&$code,&$buffer,&$started,&$lastAuthCheck,$conn,$token) {
                if (time()-$lastAuthCheck >= 15) {
                    $lastAuthCheck=time();
                    try { if (!dp_valid_token($conn,$token)) return 0; } catch (Throwable $e) { return 0; }
                }
                $len=strlen($data); if ($code!==200 && $code!==206) return 0;
                if (!$started) {
                    $buffer.=$data; if (strlen($buffer)<377) return $len;
                    // Only relay MPEG-TS packet-aligned streams, not text playlists or errors.
                    if ($buffer[0] !== "\x47" || $buffer[188] !== "\x47" || $buffer[376] !== "\x47") return 0;
                    header('Content-Type: video/mp2t'); while(ob_get_level()>0) ob_end_flush(); $started=true; echo $buffer; $buffer='';
                } else echo $data;
                flush(); return connection_aborted() ? 0 : $len;
            }
        ]);
        $ok=curl_exec($ch); curl_close($ch);
        if (!$started && $code>=300 && $code<400 && $location!=='') { $url=dp_redirect_url($url,$location); continue; }
        break;
    }
    if (!$started) { http_response_code(502); echo 'Stream unavailable'; }
} catch(Throwable $e) { if (!headers_sent()) http_response_code(502); exit('Stream unavailable'); }
