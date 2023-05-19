<?PHP 
const PATH_TO_SQLITE_FILE = 'data.db';
$dir = 'sqlite:/db.sqlite';
$dbh  = new PDO($dir) or die("cannot open the database");
echo("YAY");