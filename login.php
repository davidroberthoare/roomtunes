<?PHP
// Verifies a Google sign-in credential (ID token) with Google, then starts a signed session cookie.
require("ini.php");
header('Content-Type: application/json; charset=utf-8');

function fail($msg){
  http_response_code(401);
  echo json_encode(['status'=>"error - $msg"]);
  exit;
}

$credential = $_POST['credential'] ?? '';
if($credential === '') fail('no credential');

// let Google check the token's signature and expiry
$ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($credential));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if($body === false || $code !== 200) fail('invalid credential');

$token = json_decode($body, true);
// the token must be issued by Google, for this app, and for a verified email
if(!is_array($token)
  || ($token['aud'] ?? '') !== $env['G_CLIENT_ID']
  || !in_array($token['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)
  || ($token['exp'] ?? 0) < time()
  || !in_array($token['email_verified'] ?? false, [true, 'true'], true)
  || empty($token['sub'])
) fail('credential rejected');

$user = ['id'=>(string)$token['sub'], 'name'=>$token['name'] ?? $token['email'], 'email'=>$token['email']];

setcookie(SESSION_COOKIE, make_session_cookie_value($user), [
  'expires'  => time() + SESSION_TTL,
  'path'     => '/',
  'secure'   => !empty($_SERVER['HTTPS']),
  'httponly' => true,
  'samesite' => 'Lax',
]);

echo json_encode(['status'=>'success', 'user'=>$user]);
