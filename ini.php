<?PHP 
$env = parse_ini_file('.env');

const PATH_TO_SQLITE_FILE = 'data.db';
$dir = 'sqlite:db.sqlite';
$conn  = new PDO($dir) or die("cannot open the database");

