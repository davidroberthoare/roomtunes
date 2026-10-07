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

