<?PHP 

require("ini.php");

// $stmt = $conn->prepare("SELECT * FROM users");
// $stmt->execute();
// $result_set = $stmt->fetchAll();
// var_dump($result_set);die();

// setup return variable
$ret = ['status'=>'success', 'data'=>null];
// API action to take
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
// if none, return...
if(!$action){
    $ret['status'] = 'error - no post action';
    output($ret);
}

if(!isset($_COOKIE['user'])){
    $ret['status'] = 'error - no user cookie set';
    output($ret);
}

//set global room and user IDs
$roomid = htmlspecialchars($_REQUEST["roomid"]);
$user = json_decode($_COOKIE['user'], true);

//get the room record, with the proper owner
$stmt = $conn->prepare("SELECT * FROM rooms WHERE name=?");
$stmt->execute([$roomid]);
$result = $stmt->fetchAll();
if(isset($result[0])){
    $room = $result[0];
    
    // set global var if I'm the owner
    $is_owner = false; //default
    if($room['userid']==$user['id']){
        $is_owner=true; //I'm the owner - yay!
    }
}else{
    $ret['status'] = 'error - no room with that ID';
    output($ret);
}



switch ($action) {
    case 'add':
        if(isset($_REQUEST['song'])){
            $ret['msg'] = "adding song...";
            $song = $ret['data'] = $_REQUEST['song'];
            //if I'm not the owner, check how many of my unplayed songs are currently in the queue
            if($is_owner===false){
                $stmt = $conn->prepare("SELECT * FROM songs WHERE roomid=? AND owner=? AND played=0");
                $stmt->execute([$roomid, $user['id']]);
                $result = $stmt->fetchAll();
                if(count($result) >= 2){
                    $ret['status'] = 'error - too many songs';
                    output($ret);
                }
            }

            // then add it...
            $stmt = $conn->prepare("INSERT INTO songs (roomid, owner, videoid, title, description, thumbnail) values (?,?,?,?,?,?)");
            $stmt->execute([$roomid, $user['id'], $song['videoid'], $song['title'], $song['description'], $song['thumbnail']]);


        }else{
            $ret['status'] = 'error - no song';
        }


        break;

        // get the whole current song queue, and the current playing song
    case 'queue':
        $stmt = $conn->prepare("SELECT * FROM songs INNER JOIN users on songs.owner=users.id WHERE roomid=? AND played=0 ORDER BY added");
        $stmt->execute([$roomid]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ret['queue'] = $result;

        $stmt = $conn->prepare("SELECT * FROM songs INNER JOIN users on songs.owner=users.id WHERE roomid=? AND played=1 LIMIT 1");
        $stmt->execute([$roomid]);
        $result = $stmt->fetchObject();
        $ret['playing'] = $result;
        break;
    

        //update the current playing song to 'played', and set the next one in the line to 'playing
    case 'next':
        //set any playing songs to played in this room
        $stmt = $conn->prepare("UPDATE songs SET played=2 WHERE roomid=? AND played=1");
        $stmt->execute([$roomid]);

        //set the first unplayed song to playing
        $stmt = $conn->prepare("SELECT * FROM songs WHERE roomid=? AND played=0 ORDER BY added LIMIT 1");
        $stmt->execute([$roomid]);
        $song = $stmt->fetchObject();

        $stmt = $conn->prepare("UPDATE songs SET played=1 WHERE songid=?");
        $stmt->execute([$song->songid]);
        break;
        

        //delete the specified song, if I'm the song or room owner
    case 'delete':
        if(isset($_POST['songid'])){
            $songid = $_POST['songid'];
        }else{
            $ret['status'] = 'error - no song ID';
            output($ret);
        }

        //get the specified song...
        $stmt = $conn->prepare("SELECT * FROM songs WHERE roomid=? AND songid=?");
        $stmt->execute([$roomid, $songid]);
        $song = $stmt->fetchObject();
        if($song){
            if($song->owner == $user['id'] || $user['id'] == $room['userid']){
                $stmt = $conn->prepare("DELETE FROM songs WHERE roomid=? AND songid=?");
                $stmt->execute([$roomid,$songid]);
                $ret['data'] = $songid;
            }else{
                $ret['status'] = 'error - not the owner';
                $ret['data'] = $song;
                $ret['user'] = $user;
                $ret['room'] = $room;
                output($ret);
            }

        }else{
            $ret['status'] = 'error - no song';
            $ret['data'] = $song;
            output($ret);
        }
        break;
    

    default:
        # code...

        break;
}

output($ret);
// **********************
function output($return){
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($return);
    die();
}