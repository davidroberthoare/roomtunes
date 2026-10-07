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


$stmt = $conn->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$stmt->execute([$user['id']]);
$user_row = $stmt->fetchObject();
$is_banned = ($user_row->banned == 1);
// var_dump($user_row);die();

// enforce the room's email allow/deny rules for non-owners on every request (in case rules changed after they joined)
if(!$is_owner && !room_email_allowed($room, $user['email'] ?? '')){
    $ret['status'] = 'error - access restricted';
    output($ret);
}


switch ($action) {
    case 'add':
        if($is_banned){
            $ret['status'] = 'error - banned';
            output($ret);
        }

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
            // new songs go to the end of the queue
            $stmt = $conn->prepare("INSERT INTO songs (roomid, owner, videoid, title, description, thumbnail, position) values (?,?,?,?,?,?,(SELECT COALESCE(MAX(position),0)+1 FROM songs WHERE roomid=?))");
            $stmt->execute([$roomid, $user['id'], $song['videoid'], $song['title'], $song['description'], $song['thumbnail'], $roomid]);


        }else{
            $ret['status'] = 'error - no song';
        }


        break;

        // get the whole current song queue, and the current playing song
    case 'queue':
        $stmt = $conn->prepare("SELECT * FROM songs INNER JOIN users on songs.owner=users.id WHERE roomid=? AND played=0 AND banned=0 ORDER BY position, added");
        $stmt->execute([$roomid]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ret['queue'] = $result;

        $stmt = $conn->prepare("SELECT * FROM songs INNER JOIN users on songs.owner=users.id WHERE roomid=? AND played=1 AND banned=0 LIMIT 1");
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
        $stmt = $conn->prepare("SELECT * FROM songs WHERE roomid=? AND played=0 ORDER BY position, added LIMIT 1");
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
    

        //ban a user, if I'm the song or room owner
    case 'ban':
        if(isset($_POST['banid'])){
            $banid = $_POST['banid'];
        }else{
            $ret['status'] = 'error - no user ID to ban';
            output($ret);
        }

        if($is_owner==false){
            $ret['status'] = 'error - not the room owner';
            output($ret);
        }

        //ban the user...
        $stmt = $conn->prepare("UPDATE users SET banned = 1 WHERE id=?");
        $stmt->execute([$banid]);
        break;


        //update this room's email allow/deny regex filters, owner only
    case 'update_settings':
        if($is_owner==false){
            $ret['status'] = 'error - not the room owner';
            output($ret);
        }

        $allow_regex = isset($_POST['allow_regex']) ? trim($_POST['allow_regex']) : '';
        $deny_regex = isset($_POST['deny_regex']) ? trim($_POST['deny_regex']) : '';

        // validate both patterns compile before saving
        foreach(['allow_regex' => $allow_regex, 'deny_regex' => $deny_regex] as $field => $pattern){
            if($pattern !== '' && @preg_match('#' . str_replace('#', '\#', $pattern) . '#i', '') === false){
                $ret['status'] = "error - invalid regex in $field";
                output($ret);
            }
        }

        $stmt = $conn->prepare("UPDATE rooms SET allow_regex=?, deny_regex=? WHERE name=?");
        $stmt->execute([$allow_regex, $deny_regex, $roomid]);
        $ret['data'] = ['allow_regex' => $allow_regex, 'deny_regex' => $deny_regex];
        break;
    

        //set the order of the waiting songs in this room, owner only. expects songids[] in the new order
    case 'reorder':
        if($is_owner==false){
            $ret['status'] = 'error - not the room owner';
            output($ret);
        }
        $ids = isset($_POST['songids']) && is_array($_POST['songids']) ? array_values($_POST['songids']) : [];
        $stmt = $conn->prepare("UPDATE songs SET position=? WHERE songid=? AND roomid=? AND played=0");
        $conn->beginTransaction();
        foreach($ids as $i => $songid){
            $stmt->execute([$i + 1, $songid, $roomid]);
        }
        $conn->commit();
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