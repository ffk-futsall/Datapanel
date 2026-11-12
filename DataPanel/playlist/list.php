<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib.php';
header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer'); header('X-Content-Type-Options: nosniff');
try {
    $token=$_GET['key'] ?? ''; $id=dp_valid_token($conn,$token); if (!$id) { http_response_code(403); exit; }
    $base=dp_base_url();
    $q=$conn->prepare('SELECT c.id,c.name FROM dp_channels c JOIN dp_playlist_channels pc ON pc.channel_id=c.id WHERE pc.playlist_id=? ORDER BY c.name,c.id'); $q->execute([$id]);
    header('Content-Type: audio/x-mpegurl; charset=UTF-8'); header('Content-Disposition: attachment; filename="playlist.m3u"');
    echo "#EXTM3U\n";
    while($r=$q->fetch()) { $name=str_replace(["\r","\n"],' ', $r['name']); echo '#EXTINF:-1,'.$name."\n".$base.'/playlist/stream.php?key='.$token.'&channel='.(int)$r['id']."\n"; }
} catch(Throwable $e) { http_response_code(500); exit('Playlist unavailable'); }
