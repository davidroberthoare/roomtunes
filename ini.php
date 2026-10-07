<?PHP 
error_reporting(E_ALL);
ini_set('display_errors', '1');

$env = parse_ini_file('.env');

const PATH_TO_SQLITE_FILE = 'data.db';
$dir = 'sqlite:db.sqlite';
$conn  = new PDO($dir) or die("cannot open the database");

// tests whether $email matches a stored regex (no delimiters) - returns null if the pattern is empty/invalid
function email_matches_regex($pattern, $email){
  $pattern = trim((string)$pattern);
  if($pattern === '') return null;
  $result = @preg_match('#' . str_replace('#', '\#', $pattern) . '#i', (string)$email);
  if($result === false) return null; // invalid regex - ignore the rule
  return $result === 1;
}

// applies a room's allow/deny regex rules to an email address; deny wins, then allow (if set) must match
function room_email_allowed($room, $email){
  $deny_match = email_matches_regex($room['deny_regex'] ?? '', $email);
  if($deny_match === true) return false;

  $allow_match = email_matches_regex($room['allow_regex'] ?? '', $email);
  if($allow_match === false) return false;

  return true;
}


// ---- signed login session ----
// The signed-in user lives in an HttpOnly cookie made by login.php: base64url(payload) . '.' . base64url(HMAC-SHA256).
// Nothing the browser can edit is trusted - current_user() only returns a user if the signature checks out and it hasn't expired.
const SESSION_COOKIE = 'rt_session';
const SESSION_TTL = 60000; // seconds

function b64url_encode($data){
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode($data){
  return base64_decode(strtr($data, '-_', '+/'));
}

function session_secret(){
  global $env;
  $secret = $env['SESSION_SECRET'] ?? '';
  if(strlen($secret) < 32) die("SESSION_SECRET is missing from .env");
  return $secret;
}

function make_session_cookie_value($user){
  $payload = b64url_encode(json_encode(['id'=>$user['id'], 'name'=>$user['name'], 'email'=>$user['email'], 'exp'=>time() + SESSION_TTL]));
  return $payload . '.' . b64url_encode(hash_hmac('sha256', $payload, session_secret(), true));
}

// returns ['id','name','email'] for a valid, unexpired session cookie - otherwise null
function current_user(){
  $cookie = $_COOKIE[SESSION_COOKIE] ?? '';
  if(substr_count($cookie, '.') !== 1) return null;
  list($payload, $sig) = explode('.', $cookie);
  $expected = b64url_encode(hash_hmac('sha256', $payload, session_secret(), true));
  if(!hash_equals($expected, $sig)) return null;
  $data = json_decode(b64url_decode($payload), true);
  if(!is_array($data) || !isset($data['id'], $data['exp']) || $data['exp'] < time()) return null;
  return ['id'=>(string)$data['id'], 'name'=>$data['name'] ?? '', 'email'=>$data['email'] ?? ''];
}
