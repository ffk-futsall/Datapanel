<?php
// Playlist and relay helpers. Do not expose upstream URLs in HTTP responses or logs.
function dp_public_ip($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
    $n = ip2long($ip);
    foreach (['0.0.0.0/8','10.0.0.0/8','100.64.0.0/10','127.0.0.0/8','169.254.0.0/16','172.16.0.0/12','192.0.0.0/24','192.0.2.0/24','192.168.0.0/16','198.18.0.0/15','198.51.100.0/24','203.0.113.0/24','224.0.0.0/4','240.0.0.0/4'] as $cidr) {
        [$net,$bits] = explode('/', $cidr); $mask = (-1 << (32 - (int)$bits));
        if (($n & $mask) === (ip2long($net) & $mask)) return false;
    }
    return true;
}
function dp_resolve_url($url) {
    if (!is_string($url) || strlen($url) > 8192 || preg_match('/[\x00-\x20\x7f]/', $url)) throw new RuntimeException('Invalid URL');
    $p = parse_url($url);
    if (!$p || !isset($p['scheme'], $p['host']) || !in_array(strtolower($p['scheme']), ['http','https'], true) || isset($p['user']) || isset($p['pass']) || isset($p['fragment'])) throw new RuntimeException('Invalid URL');
    $host = strtolower($p['host']);
    if (!preg_match('/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/D', $host)) throw new RuntimeException('Invalid host');
    $port = $p['port'] ?? (strtolower($p['scheme']) === 'https' ? 443 : 80);
    if (!in_array((int)$port, [80,443,8080,8443], true)) throw new RuntimeException('Port not allowed');
    $ips = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? [$host] : @gethostbynamel($host);
    if (!$ips) throw new RuntimeException('Host not available');
    foreach ($ips as $ip) if (!dp_public_ip($ip)) throw new RuntimeException('Private address blocked');
    return [$host, (int)$port, $ips[0]];
}
function dp_curl($url) {
    [$host,$port,$ip] = dp_resolve_url($url);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_NOSIGNAL=>true, CURLOPT_PROXY=>'', CURLOPT_RESOLVE=>[$host.':'.$port.':'.$ip], CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_PROTOCOLS=>CURLPROTO_HTTP|CURLPROTO_HTTPS]);
    return $ch;
}
function dp_redirect_url($url, $location) {
    $location=trim($location);
    if ($location==='' || strlen($location)>8192 || preg_match('/[\x00-\x20\x7f]/',$location)) throw new RuntimeException('Invalid redirect');
    $p=parse_url($url); $scheme=strtolower($p['scheme']);
    $origin=$scheme.'://'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '');
    if (preg_match('~^https?://~i',$location)) $next=$location;
    elseif (str_starts_with($location,'//')) $next=$scheme.':'.$location;
    elseif (str_starts_with($location,'/')) $next=$origin.$location;
    elseif (str_starts_with($location,'?')) $next=$origin.($p['path'] ?? '/').$location;
    else { $path=$p['path'] ?? '/'; $next=$origin.substr($path,0,(strrpos($path,'/') ?: 0)+1).$location; }
    $nextParts=parse_url($next);
    if (!$nextParts || !isset($nextParts['scheme']) || ($scheme==='https' && strtolower($nextParts['scheme'])!=='https')) throw new RuntimeException('Insecure redirect blocked');
    // Re-resolve and pin a public IP on every redirect.
    return $next;
}
function dp_fetch_playlist($url) {
    for ($i=0;$i<5;$i++) {
        $ch=dp_curl($url); $body=''; $max=20*1024*1024; $location=''; $status=0;
        curl_setopt_array($ch, [CURLOPT_TIMEOUT=>45,
            CURLOPT_HEADERFUNCTION=>function($ch,$line) use (&$location,&$status) { if (preg_match('~^HTTP/\S+\s+(\d+)~i',$line,$m)) { $status=(int)$m[1]; $location=''; } elseif (stripos($line,'Location:')===0) $location=trim(substr($line,9)); return strlen($line); },
            CURLOPT_WRITEFUNCTION=>function($ch,$part) use (&$body,$max,&$status) { if ($status>=300 && $status<400) return strlen($part); if (strlen($body)+strlen($part)>$max) return 0; $body.=$part; return strlen($part); }]);
        $ok=curl_exec($ch); curl_close($ch);
        if ($status>=300 && $status<400 && $location!=='') { $url=dp_redirect_url($url,$location); continue; }
        if ($ok===false || $status!==200 || stripos(ltrim($body, "\xEF\xBB\xBF \t\r\n"), '#EXTM3U')!==0) throw new RuntimeException('Playlist download failed');
        return $body;
    }
    throw new RuntimeException('Too many redirects');
}
function dp_parse_playlist($body) {
    $result=[]; $name=null;
    foreach (preg_split('/\r\n|\n|\r/', $body) as $line) {
        $line=trim($line); if ($line==='') continue;
        if (stripos($line, '#EXTINF:')===0) {
            $comma=strpos($line, ','); $name=$comma===false ? 'Kanal' : trim(substr($line,$comma+1));
            $name=preg_replace('/[\x00-\x1f\x7f]/u',' ', $name); $name=mb_substr($name ?: 'Kanal',0,200,'UTF-8');
        } elseif ($line[0]!=='#' && $name!==null) {
            // Actual DNS/IP checks also run on every request, not merely at import.
            $p=parse_url($line);
            if (is_array($p) && isset($p['scheme'],$p['host']) && in_array(strtolower($p['scheme']), ['http','https'], true) && !isset($p['user']) && !isset($p['pass']) && !preg_match('/[\x00-\x20\x7f]/',$line) && strlen($line)<=8192) {
                $result[hash('sha256',$line)]=[$name,$line];
                if (count($result)>20000) throw new RuntimeException('Too many channels');
            }
            $name=null;
        }
    }
    return $result;
}
function dp_tables($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS dp_channels (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, url_hash CHAR(64) CHARACTER SET ascii NOT NULL UNIQUE, name VARCHAR(200) NOT NULL, source_url TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS dp_playlists (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, client_id INT NOT NULL, token_hash CHAR(64) CHARACTER SET ascii NOT NULL UNIQUE, expires_at DATETIME NULL, revoked TINYINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(client_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS dp_playlist_channels (playlist_id INT UNSIGNED NOT NULL, channel_id INT UNSIGNED NOT NULL, PRIMARY KEY(playlist_id,channel_id), FOREIGN KEY (playlist_id) REFERENCES dp_playlists(id) ON DELETE CASCADE, FOREIGN KEY (channel_id) REFERENCES dp_channels(id) ON DELETE CASCADE) ENGINE=InnoDB");
}
function dp_valid_token($db, $token) {
    if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/D', $token)) return false;
    $q=$db->prepare('SELECT p.id FROM dp_playlists p JOIN tv_clients c ON c.id=p.client_id WHERE p.token_hash=? AND p.revoked=0 AND (p.expires_at IS NULL OR p.expires_at > NOW())');
    $q->execute([hash('sha256',$token)]); return $q->fetchColumn();
}
function dp_base_url() {
    $host=$_SERVER['HTTP_HOST'] ?? '';
    if (!preg_match('/\A(?:[a-zA-Z0-9.-]+)(?::[0-9]{1,5})?\z/D',$host)) throw new RuntimeException('Invalid host');
    $scheme=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
    // Public links should never carry bearer tokens over plaintext HTTP.
    if ($scheme==='http' && !preg_match('/\A(?:localhost|127\.0\.0\.1)(?::[0-9]+)?\z/D',strtolower($host))) throw new RuntimeException('HTTPS required');
    $path=preg_replace('~/playlist/[^/]+$~','',$_SERVER['SCRIPT_NAME']);
    return $scheme.'://'.$host.$path;
}
