<?php
// Simple Termux PHP chat
// Usage: php -S 0.0.0.0:8080 termux_chat.php
// Data directory
define('DATA_DIR', __DIR__ . '/data');
define('USERS_FILE', DATA_DIR . '/users.json');
define('MESSAGES_FILE', DATA_DIR . '/messages.txt');
define('CONFIG_FILE', DATA_DIR . '/config.json');

@mkdir(DATA_DIR, 0700, true);

// Helpers
function read_users() {
    if (!file_exists(USERS_FILE)) return [];
    $s = @file_get_contents(USERS_FILE);
    $j = json_decode($s, true);
    return is_array($j) ? $j : [];
}
function write_users($arr) {
    file_put_contents(USERS_FILE, json_encode($arr, JSON_PRETTY_PRINT), LOCK_EX);
}
function append_message($msg) {
    $line = json_encode($msg, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents(MESSAGES_FILE, $line, FILE_APPEND | LOCK_EX);
}
function read_messages() {
    if (!file_exists(MESSAGES_FILE)) return [];
    $lines = file(MESSAGES_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $out = [];
    foreach ($lines as $ln) {
        $j = json_decode($ln, true);
        if ($j) $out[] = $j;
    }
    return $out;
}
function now_ts(){ return time(); }
function sanitize($s){ return htmlspecialchars(trim((string)$s), ENT_QUOTES, 'UTF-8'); }

// Boot / identify user
// Priority: cookie 'chat_user' -> config default username -> login form
session_start();
$cookieUser = isset($_COOKIE['chat_user']) ? sanitize($_COOKIE['chat_user']) : '';
$configUser = '';
if (file_exists(CONFIG_FILE)) {
    $cfg = json_decode(file_get_contents(CONFIG_FILE), true);
    if (isset($cfg['username'])) $configUser = sanitize($cfg['username']);
}
$current = $cookieUser ?: $configUser ?: '';

// If a web form posted to set username
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user'])) {
    $u = sanitize($_POST['username']);
    if ($u === '') {
        $error = "Username tidak boleh kosong.";
    } else {
        setcookie('chat_user', $u, time()+60*60*24*365, '/');
        // ensure user exists in users.json
        $users = read_users();
        if (!isset($users[$u])) {
            $users[$u] = ['name'=>$u, 'last_seen'=>now_ts()];
            write_users($users);
        }
        // if config missing, write default config
        if (!file_exists(CONFIG_FILE)) {
            file_put_contents(CONFIG_FILE, json_encode(['username'=>$u], JSON_PRETTY_PRINT), LOCK_EX);
        }
        header("Location: ".$_SERVER['REQUEST_URI']);
        exit;
    }
}

// API endpoints
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
if ($action) {
    // Read user from cookie (must be set)
    $user = isset($_COOKIE['chat_user']) ? sanitize($_COOKIE['chat_user']) : '';
    if ($user === '') {
        header('Content-Type: application/json; charset=utf-8', true, 400);
        echo json_encode(['error'=>'no_user','message'=>'Set username terlebih dahulu.']);
        exit;
    }

    // Update last_seen
    $users = read_users();
    if (!isset($users[$user])) $users[$user] = ['name'=>$user,'last_seen'=>now_ts()];
    $users[$user]['last_seen'] = now_ts();
    write_users($users);

    if ($action === 'users') {
        // return list of users and online status
        $list = [];
        $now = now_ts();
        foreach ($users as $name => $meta) {
            $online = ($now - intval($meta['last_seen']) <= 60); // online if seen in last 60s
            $list[] = ['name'=>$name, 'online'=>$online, 'last_seen'=>intval($meta['last_seen'])];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['you'=>$user,'users'=>$list]);
        exit;
    } elseif ($action === 'send') {
        // POST with to and text
        $to = isset($_POST['to']) ? sanitize($_POST['to']) : (isset($_GET['to']) ? sanitize($_GET['to']) : '');
        $text = isset($_POST['text']) ? trim($_POST['text']) : (isset($_GET['text']) ? trim($_GET['text']) : '');
        if ($to === '' || $text === '') {
            header('Content-Type: application/json; charset=utf-8', true, 400);
            echo json_encode(['error'=>'bad_request']);
            exit;
        }
        // ensure recipient exists
        if (!isset($users[$to])) {
            $users[$to] = ['name'=>$to,'last_seen'=>0];
            write_users($users);
        }
        $m = ['from'=>$user,'to'=>$to,'time'=>now_ts(),'text'=>$text];
        append_message($m);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'ok','message'=>$m]);
        exit;
    } elseif ($action === 'get_messages') {
        // GET with with=username and since=ts (optional)
        $with = isset($_GET['with']) ? sanitize($_GET['with']) : '';
        $since = isset($_GET['since']) ? intval($_GET['since']) : 0;
        if ($with === '') {
            header('Content-Type: application/json; charset=utf-8', true, 400);
            echo json_encode(['error'=>'missing_with']);
            exit;
        }
        $all = read_messages();
        $res = [];
        foreach ($all as $m) {
            if (!isset($m['from']) || !isset($m['to']) || !isset($m['time'])) continue;
            $ts = intval($m['time']);
            // messages between user and 'with'
            if ($ts <= $since) continue;
            if (($m['from'] === $user && $m['to'] === $with) || ($m['from'] === $with && $m['to'] === $user)) {
                $res[] = $m;
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['you'=>$user,'with'=>$with,'messages'=>$res]);
        exit;
    } else {
        header('Content-Type: application/json; charset=utf-8', true, 404);
        echo json_encode(['error'=>'unknown_action']);
        exit;
    }
}

// If no username yet, show simple setup/login page
if ($current === '') {
    // show HTML form to set username
    $errMsg = isset($error) ? $error : '';
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Termux Chat - Setup</title></head><body>';
    echo '<h2>Termux Chat - Setup Username</h2>';
    if ($errMsg) echo '<p style="color:red;">'.htmlspecialchars($errMsg).'</p>';
    echo '<form method="post"><label>Username: <input name="username" required></label>';
    echo '<input type="hidden" name="set_user" value="1"><button>Set</button></form>';
    echo '<p>Or create data/config.json manually with {"username":"yourname"} before starting.</p>';
    echo '</body></html>';
    exit;
}

// Otherwise serve the chat single-file UI
// Minimal HTML + JS
$you = $current;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Termux Chat - <?=htmlspecialchars($you)?></title>
<style>
body{font-family:Arial,Helvetica,sans-serif;margin:10px}
#users{float:left;width:200px;border-right:1px solid #ccc;padding-right:10px}
#chat{margin-left:220px}
.user{padding:6px;cursor:pointer}
.user.online{background:#e8ffe8}
.msg{margin:6px 0;padding:6px;border-radius:4px}
.msg.me{background:#def; text-align:right}
.msg.they{background:#eee; text-align:left}
#messages{height:60vh;overflow:auto;border:1px solid #ddd;padding:8px;background:#fff}
</style>
</head>
<body>
<h3>Termux Chat — User: <?=htmlspecialchars($you)?></h3>
<p>
<button id="refreshUsers">Refresh users</button>
<a href="?logout=1" id="logout" onclick="document.cookie='chat_user=; Max-Age=0;path=/'">Logout</a>
</p>
<div id="users">
<h4>Users</h4>
<div id="usersList">Loading...</div>
</div>
<div id="chat">
<h4 id="chatWith">Pilih user</h4>
<div id="messages">Pilih user di sebelah kiri untuk mulai chat.</div>
<form id="sendForm" style="margin-top:10px;display:none">
<input type="text" id="msgInput" placeholder="Tulis pesan..." style="width:70%">
<button type="submit">Kirim</button>
</form>
</div>

<script>
const YOU = <?=json_encode($you)?>;
let selected = null;
let lastTs = 0;

function api(action, params = {}, method='GET') {
    const url = new URL(window.location.href);
    url.searchParams.set('action', action);
    if (method === 'GET') {
        Object.keys(params).forEach(k=>url.searchParams.set(k, params[k]));
        return fetch(url, {credentials:'same-origin'}).then(r=>r.json());
    } else {
        return fetch(url, {
            method:'POST',
            credentials:'same-origin',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams(params)
        }).then(r=>r.json());
    }
}

function refreshUsers(){
    api('users').then(data=>{
        const el = document.getElementById('usersList');
        el.innerHTML = '';
        (data.users || []).forEach(u=>{
            if (u.name === YOU) return;
            const div = document.createElement('div');
            div.textContent = u.name + (u.online ? ' (online)':'');
            div.className = 'user' + (u.online ? ' online':'');
            div.onclick = ()=>selectUser(u.name);
            el.appendChild(div);
        });
    });
}

function selectUser(name){
    selected = name;
    lastTs = 0;
    document.getElementById('chatWith').textContent = 'Chat dengan: ' + name;
    document.getElementById('sendForm').style.display = 'block';
    document.getElementById('messages').innerHTML = 'Loading...';
    loadMessages();
}

function loadMessages(){
    if (!selected) return;
    api('get_messages', {with: selected, since: lastTs}).then(data=>{
        const msgs = data.messages || [];
        const container = document.getElementById('messages');
        msgs.forEach(m=>{
            const div = document.createElement('div');
            div.className = 'msg ' + (m.from === YOU ? 'me':'they');
            div.innerHTML = '<small>' + new Date(m.time*1000).toLocaleTimeString() + '</small><div>' + escapeHtml(m.text) + '</div>';
            container.appendChild(div);
            lastTs = Math.max(lastTs, m.time);
        });
        container.scrollTop = container.scrollHeight;
    }).catch(err=>{console.warn(err)});
}

function escapeHtml(s){ return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

document.getElementById('refreshUsers').addEventListener('click', refreshUsers);
document.getElementById('sendForm').addEventListener('submit', function(e){
    e.preventDefault();
    const text = document.getElementById('msgInput').value.trim();
    if (!text || !selected) return;
    api('send', {to:selected, text:text}, 'POST').then(res=>{
        document.getElementById('msgInput').value = '';
        loadMessages();
    });
});

// Polling
setInterval(()=>{
    refreshUsers();
    if (selected) loadMessages();
}, 2000);

refreshUsers();
</script>
</body>
</html>
