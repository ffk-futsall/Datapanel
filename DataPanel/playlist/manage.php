<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
require_once __DIR__ . '/lib.php';
header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer');
$error=''; $message=''; $createdLink=null;
try {
    dp_tables($conn);
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (!isset($_POST['csrf_token']) || !$security->verifyCsrfToken($_POST['csrf_token'])) throw new RuntimeException('Kërkesa nuk u verifikua. Rifresko faqen.');
        $action=$_POST['action'] ?? '';
        if ($action==='import') {
            $url=trim($_POST['source'] ?? '');
            $entries=dp_parse_playlist(dp_fetch_playlist($url));
            if (!$entries) throw new RuntimeException('Nuk u gjet asnjë kanal i vlefshëm në listë.');
            $conn->beginTransaction();
            $q=$conn->prepare('INSERT INTO dp_channels (url_hash,name,source_url) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
            foreach($entries as $hash=>$entry) $q->execute([$hash,$entry[0],$entry[1]]);
            $conn->commit(); $message='U importuan ose u përditësuan '.count($entries).' kanale.';
        } elseif ($action==='create') {
            $client=filter_input(INPUT_POST,'client_id',FILTER_VALIDATE_INT);
            $ids=json_decode($_POST['channels'] ?? '',true);
            if (!$client || !is_array($ids) || !$ids || count($ids)>20000) throw new RuntimeException('Zgjidh klientin dhe të paktën një kanal.');
            $ids=array_values(array_unique(array_filter($ids, function($x) { return is_int($x) && $x>0; })));
            if (!$ids) throw new RuntimeException('Lista e kanaleve nuk është e vlefshme.');
            $end=trim($_POST['expires'] ?? ''); $endSql=null;
            if ($end!=='') { $d=DateTime::createFromFormat('!Y-m-d',$end); if (!$d || $d->format('Y-m-d')!==$end) throw new RuntimeException('Data nuk është e vlefshme.'); $endSql=$end.' 23:59:59'; }
            $token=bin2hex(random_bytes(32)); $base=dp_base_url();
            $conn->beginTransaction();
            // Serialize link creation for each client, so two requests cannot both remain active.
            $q=$conn->prepare('SELECT id FROM tv_clients WHERE id=? FOR UPDATE'); $q->execute([$client]); if (!$q->fetchColumn()) throw new RuntimeException('Klienti nuk ekziston.');
            $q=$conn->prepare('UPDATE dp_playlists SET revoked=1 WHERE client_id=? AND revoked=0'); $q->execute([$client]);
            $q=$conn->prepare('INSERT INTO dp_playlists (client_id,token_hash,expires_at) VALUES (?,?,?)'); $q->execute([$client,hash('sha256',$token),$endSql]);
            $pid=$conn->lastInsertId();
            $q=$conn->prepare('INSERT INTO dp_playlist_channels (playlist_id,channel_id) SELECT ?,id FROM dp_channels WHERE id=?');
            foreach($ids as $id) { $q->execute([$pid,$id]); if ($q->rowCount()!==1) throw new RuntimeException('Një kanal nuk ekziston.'); }
            $conn->commit();
            $_SESSION['dp_created_link']=$base.'/playlist/list.php?key='.$token;
            header('Location: manage.php?created=1',true,303); exit;
        } elseif ($action==='revoke') {
            $client=filter_input(INPUT_POST,'client_id',FILTER_VALIDATE_INT);
            if (!$client) throw new RuntimeException('Klienti nuk është i vlefshëm.');
            $q=$conn->prepare('UPDATE dp_playlists SET revoked=1 WHERE client_id=?'); $q->execute([$client]);
            $message='Lidhja e klientit u çaktivizua.';
        } else throw new RuntimeException('Veprimi nuk është i vlefshëm.');
    }
    if (isset($_GET['created'],$_SESSION['dp_created_link'])) { $createdLink=$_SESSION['dp_created_link']; unset($_SESSION['dp_created_link']); }
    $clients=$conn->query('SELECT id,first_name FROM tv_clients ORDER BY first_name,id')->fetchAll();
    $channels=$conn->query('SELECT id,name FROM dp_channels ORDER BY name,id')->fetchAll();
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $error = $e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'Veprimi dështoi. Kontrollo konfigurimin, bazën dhe burimin.';
    $clients=$clients ?? []; $channels=$channels ?? [];
}
$csrf=$security->generateCsrfToken();
// The shared header resolves relative paths based on /pages/; this page lives in /playlist/.
?>
<!doctype html><html lang="sq"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>Listat M3U — DataPanel</title><style>.channel{display:block}.channel[hidden]{display:none}</style><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body class="bg-light"><main class="container py-4"><a href="../index.php">← DataPanel</a><h1 class="h3 mt-3">Listat M3U të klientëve</h1>
<p>Importo një listë, zgjidh kanalet dhe krijo një lidhje për klientin. Burimet ruhen në bazën private të të dhënave. Lidhjet përmbajnë një çelës sekret; ndajini vetëm me klientin.</p>
<div class="alert alert-warning">Version prove: mbështeten transmetime MPEG-TS përmes HTTP/HTTPS, jo HLS. Për klientë realë nevojiten HTTPS, server me kapacitet transmetimi dhe kontroll sigurie. Importi përmes HTTP nuk është i enkriptuar.</div>
<?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?=htmlspecialchars($message,ENT_QUOTES,'UTF-8')?></div><?php endif; ?>
<?php if ($createdLink): ?><div class="alert alert-success"><strong>Ruaje këtë lidhje tani; nuk shfaqet më.</strong><input class="form-control mt-2" readonly value="<?=htmlspecialchars($createdLink,ENT_QUOTES,'UTF-8')?>"><p class="mb-0 mt-2">Krijimi i një lidhjeje tjetër e çaktivizon këtë.</p></div><?php endif; ?>
<section class="card my-3"><div class="card-body"><h2 class="h5">1. Importo kanalet</h2><form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf,ENT_QUOTES,'UTF-8')?>"><input type="hidden" name="action" value="import"><label class="form-label" for="source">Lidhja M3U e ofruesit</label><input id="source" type="password" class="form-control" name="source" required autocomplete="off"><button class="btn btn-primary mt-2">Importo</button></form></div></section>
<section class="card my-3"><div class="card-body"><h2 class="h5">2. Krijo një lidhje për klientin</h2><form method="post" id="create-form"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf,ENT_QUOTES,'UTF-8')?>"><input type="hidden" name="action" value="create"><input type="hidden" name="channels" id="selected-channels"><label class="form-label">Klienti</label><select name="client_id" class="form-select" required><option value="">Zgjidh klientin</option><?php foreach($clients as $c): ?><option value="<?=(int)$c['id']?>"><?=htmlspecialchars($c['first_name'],ENT_QUOTES,'UTF-8')?></option><?php endforeach; ?></select><label class="form-label mt-2">Skadon më (opsionale)</label><input type="date" name="expires" class="form-control"><label class="form-label mt-2" for="search">Kërko kanalet (<?=count($channels)?>)</label><input id="search" class="form-control" type="search"><div class="mt-2"><button class="btn btn-sm btn-outline-secondary" type="button" id="all">Zgjidh të dukshmet</button> <button class="btn btn-sm btn-outline-secondary" type="button" id="none">Pastro të gjitha</button></div><div id="channel-list" class="border rounded p-2 mt-2" style="max-height:24rem;overflow:auto"><?php foreach($channels as $c): ?><label class="channel"><input type="checkbox" value="<?=(int)$c['id']?>"> <?=htmlspecialchars($c['name'],ENT_QUOTES,'UTF-8')?></label><?php endforeach; ?></div><button class="btn btn-success mt-2">Krijo lidhjen</button></form></div></section>
<section class="card my-3"><div class="card-body"><h2 class="h5">Çaktivizo lidhjen e klientit</h2><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf,ENT_QUOTES,'UTF-8')?>"><input type="hidden" name="action" value="revoke"><select name="client_id" class="form-select" required><option value="">Zgjidh klientin</option><?php foreach($clients as $c): ?><option value="<?=(int)$c['id']?>"><?=htmlspecialchars($c['first_name'],ENT_QUOTES,'UTF-8')?></option><?php endforeach; ?></select><button class="btn btn-danger mt-2">Çaktivizo</button></form></div></section>
</main><script>
const rows=[...document.querySelectorAll('.channel')]; document.querySelector('#search').addEventListener('input',e=>{let s=e.target.value.toLowerCase(); rows.forEach(r=>r.hidden=!r.textContent.toLowerCase().includes(s));});
document.querySelector('#all').onclick=()=>rows.filter(r=>!r.hidden).forEach(r=>r.querySelector('input').checked=true);document.querySelector('#none').onclick=()=>rows.forEach(r=>r.querySelector('input').checked=false);document.querySelector('#create-form').onsubmit=e=>{let a=rows.filter(r=>r.querySelector('input').checked).map(r=>Number(r.querySelector('input').value)); if(!a.length){e.preventDefault();alert('Zgjidh të paktën një kanal.');return;}document.querySelector('#selected-channels').value=JSON.stringify(a);};
</script></body></html>
