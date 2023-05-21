<?PHP 
require("ini.php");

$is_owner=false;  //default

// setup the room
if(isset($_GET["id"]) && isset($_COOKIE["user"])){
  $roomid = htmlspecialchars($_GET["id"]);
  $user = json_decode($_COOKIE['user'], true);
  // var_dump($user);die();
  
  // create the user record if it doesn't exist
  $stmt = $conn->prepare("INSERT OR IGNORE INTO users (id, name) VALUES(?,?)");
  $stmt->execute([$user['id'], $user['name']]);

  // create the room record if it doesn't exist
  $stmt = $conn->prepare("INSERT OR IGNORE INTO rooms (name, userid) VALUES(?,?)");
  $stmt->execute([$roomid, $user['id']]);

  //get the room record, with the proper owner
  $stmt = $conn->prepare("SELECT * FROM rooms WHERE name=?");
  $stmt->execute([$roomid]);
  $result = $stmt->fetchAll();
  if(isset($result[0])){
    $room = $result[0];
    
    // set global var if I'm the owner
    if($room['userid']==$user['id']){
      $is_owner=true; //I'm the owner - yay!
    }
    // var_dump($is_owner);die();
  }else{
    die("no room with that ID");
  }



}else{
  die("missing room or user ID");
}
// $stmt = $conn->prepare("SELECT * FROM users");
// $stmt->execute();
// $result_set = $stmt->fetchAll();


?>
<!DOCTYPE html>
<html>

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RoomTunes</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css">
  <link rel="stylesheet" href="/css/styles.css">




  <link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png">
  <link rel="manifest" href="/img/site.webmanifest">
  <link rel="mask-icon" href="/img/safari-pinned-tab.svg" color="#5bbad5">
  <meta name="msapplication-TileColor" content="#2d89ef">
  <meta name="theme-color" content="#ffffff">

  <!-- Global site tag (gtag.js) - Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-J5GDDQYN4N"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() {dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-J5GDDQYN4N');
  </script>


</head>

<body>
  <a href='/' class='is-pulled-left' style='margin:10px;'>HOME</a>
  <section class='section has-text-centered'>


    <div id='room_title' class='subtitle'>Welcome<?PHP 
        if($is_owner){
          echo " to your room, ".$user['name'];
        }else{
          echo ", ".$user['name'];
        }
      ?>
    </div>
    <div class='columns'>
      <!--PLAYER COLUMN-->
      <div class='column col_player is-half'>

        <!-- OWNER ONLY -->
        <?PHP if($is_owner===true){  ?>
          <div class='box' class="owner">
            <div id='player'></div>
            <div id='playing_title' class='subtitle is-6'></div>
            <div id='playing_username' class='title is-7'></div>
          <div class='queue_control'>
            <button class='button is-success' id='btn_play_next'>Play Next Song</button>
          </div>
        </div>
        <?PHP } ?>
        
        <!-- PLAYER ONLY -->
        <?PHP if($is_owner===false){  ?>
          <div class='box' class="player">
            <div class='title is-7'>Now Playing</div>
            <img id='playing_thumbnail' />
            <div id='playing_title' class='subtitle is-5'></div>
            <div id='playing_username' class='title is-7'></div>
          </div>
        <?PHP } ?>

        <div id='queue'>
          (no videos in the queue)
        </div>
      </div>


      <!--SEARCH COLUMN-->
      <div class='column col_search is-half'>
        <div class='box'>
          <div class="field">
            <div class=''>Search for a video, then click to add it to the shared playlist for this room.</div>
            <div class="control">
              <input id="input_search" class="input is-primary" type="text" placeholder="Search...">
            </div>
          </div>

          <div id='search_results'>

          </div>

          <div class='pagination'>
            <div id='go_prev' class='page_btn hidden'>&lt;Prev</div>
            <div id='go_next' class='page_btn hidden'>Next&gt;</div>
          </div>

        </div>
      </div>

    </div>


    </div>
    <!--end section-->

    <!--hidden elements-->
    <div style='display:none'>

      <!-- video row template -->
      <div class="card video_row template" data-id=''>
        <img src="https://bulma.io/images/placeholders/96x96.png" class='vid_thumbnail is-pulled-left'>
        <p class="title is-6 vid_name">John Smith</p>
        <p class="subtitle is-7 vid_description">@johnsmith</p>
      </div>

      <!-- queue video row template -->
      <div class="card queue_row template" data-id=''>
        <button class="button is-small vid_delete" data-num=''>X</button>
        <img src="https://bulma.io/images/placeholders/96x96.png" class='vid_thumbnail is-pulled-left'>
        <p class="title is-6 vid_name">John Smith</p>
        <p class="subtitle is-7 vid_description">@johnsmith</p>
      </div>


    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="/js/cookies.js"></script>

    <script>

      const is_owner = <?PHP echo(json_encode($is_owner));?>;
      const userid = <?PHP echo(json_encode($user['id']));?>;

      <?PHP if($is_owner===true){  ?>
        // init the video player
        // 2. This code loads the IFrame Player API code asynchronously.
        var tag = document.createElement('script');

        tag.src = "https://www.youtube.com/iframe_api";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        // 3. This function creates an <iframe> (and YouTube player)
        //    after the API code downloads.
        var player;
        var player_ready = false;
        var now_playing = false;
        function onYouTubeIframeAPIReady() {
          player = new YT.Player('player', {
            height: '200',
            width: '300',
            //   videoId: 'M7lc1UVf-VE',
            events: {
              'onReady': onPlayerReady,
              'onStateChange': onPlayerStateChange
            }
          });
        }

        // 4. The API will call this function when the video player is ready.
        function onPlayerReady(event) {
          // event.target.playVideo();
          player_ready = true;
        }

        // 5. The API calls this function when the player's state changes.
        //    The function indicates that when playing a video (state=1),
        //    the player should play for six seconds and then stop.
        var done = false;
        function onPlayerStateChange(event) {
          if (event.data == YT.PlayerState.ENDED && !done) {
            done = true;
            $("#btn_play_next").trigger('click');
          }
          else if (event.data == YT.PlayerState.PLAYING) {
            done = false;
          }
        }

    <?PHP } ?>
    
            
      // search on text update
      $("#input_search").on("change", function() {
        searchVids();
      });

      $(".page_btn").on('click', function() {
        searchVids($(this).data('id'));
      });

      function searchVids(token) {
        var q = $("#input_search").val().trim();

        if (q == '') return false;

        console.log("searching for", q);
        // container to display search results
        var $results = $('#search_results');

        // YouTube Data API base URL (JSON response)
        var url = "https://www.googleapis.com/youtube/v3/search?1=1"
        url = url + '&part=snippet';
        url = url + '&key=AIzaSyAuQwAKHd13idhbRRHVqOs6dlokLVVAufg';
        url = url + '&paid-content=false';
        url = url + '&safeSearch=strict';
        url = url + '&type=video';

        if (token) {
          url = url + '&pageToken=' + token;
        }

        $.getJSON(url + "&q=" + q, function(data) {
          // console.log("got video results:", data)
          $results.text("");//empty it first
          $(".page_btn").addClass('hidden').data('id', false);

          if (data.items) {
            $.each(data.items, function(i, item) {
              // console.log("adding video", item);
              $row = $(".video_row.template").clone();
              $row.removeClass("template");

              var rowdata = {
                videoid: item.id.videoId,
                title: item.snippet.title,
                description: item.snippet.description,
                thumbnail: item.snippet.thumbnails.default.url
              }
              $row.data('vid_data', rowdata);

              $row.find(".vid_name").html(rowdata.title);
              $row.find(".vid_description").html(rowdata.description);
              $row.find(".vid_thumbnail").prop('src', rowdata.thumbnail);

              $results.append($row);
            });

            // if there's more
            if (data.nextPageToken) {
              $("#go_next").removeClass('hidden').data('id', data.nextPageToken);
            }
            if (data.prevPageToken) {
              $("#go_prev").removeClass('hidden').data('id', data.prevPageToken);
            }

          } else {
            $results.html("No videos found");
          }
        });
      }


      // on video_row click, tyr to add the video to the queue
      // data.videoid, data.title, data.description, data.thumbnail, client.userid
      $("#search_results").on('click', '.video_row', function() {
        var rowdata = $(this).data('vid_data');
        console.log("song row clicked - sending ADD", rowdata);
          $.post("/api.php", {
            roomid: "<?PHP echo $roomid;?>",
            action:"add", 
            song:rowdata
          },
            function (data, textStatus, jqXHR) {
              console.log("got back: ", data)
              if(data.status != 'success'){
                if(data.status.indexOf("too many songs") >-1 ){
                  alert("Whoops - you've already got 2 songs in the queue. Please wait until one plays then try again.")
                }else{
                  alert("Whoops - there was a problem adding that song...")
                }
              }
              getQueue();
            },
            "JSON"
          );
      });



      function getQueue(){
        console.log("getting song queue")
        $.post("/api.php", {
            roomid: "<?PHP echo $roomid;?>",
            action:"queue", 
          },
            function (response, textStatus, jqXHR) {
              console.log("got back: ", response)
              if(response.status == 'success'){
                buildQueue(response.queue);
                setPlaying(response.playing);
              }else{
                console.warn("Whoops - there was a problem fetching the queue...", response.status)
              }
            },
            "JSON"
          );
      }


      function buildQueue(data){
        // update UI with updated queue
        $("#queue").empty();
        $.each(data, function (i, item) {
          console.log('adding queue row', item);
          $row = $(".queue_row.template").clone();
          $row.removeClass("template");

          $row.data('vid_data', item);
          
          $row.find(".vid_name").html(item.title);
          $row.find(".vid_description").html(item.name);
          $row.find(".vid_thumbnail").prop('src', item.thumbnail);
          $row.find(".vid_delete").data('num', item.songid);

          // if I'm not the owner, or it's not my video
          if (!is_owner && (userid != item.owner)) {
            $row.find(".vid_delete").remove();
          }

          $("#queue").append($row);
        });
      }
      
      function setPlaying(song){
        console.log("setting PLAYING", song)
        if (is_owner) {
          if (player_ready && song.videoid && now_playing!=song.videoid) {
            console.log("loading a new song...", song.videoid)
            player.loadVideoById(song.videoid);
            now_playing=song.videoid; //set it
          }else{
            console.log("not ready, or already playing...", now_playing)
          }
        }
        $("#playing_title").html(song.title);
        $("#playing_username").html(song.username);
        $("#playing_thumbnail").prop('src', song.thumbnail);
      }


      $("#btn_play_next").click(function() {
        console.log("trying to play next song...")
        
        $.post("/api.php", {
            roomid: "<?PHP echo $roomid;?>",
            action:"next"
          },
            function (data, textStatus, jqXHR) {
              console.log("got back: ", data)
              if(data.status == 'success'){
                getQueue();
              }else{
                console.warn(data.status);
              }
            },
            "JSON"
          );

      });


      $("#queue").on('click', '.vid_delete', function() {
        var id = $(this).data('num');
        console.log("deleting vid", id);

        $.post("/api.php", {
            roomid: "<?PHP echo $roomid;?>",
            action:"delete",
            songid:id
          },
            function (data, textStatus, jqXHR) {
              console.log("got back: ", data)
              if(data.status == 'success'){
                getQueue();
              }else{
                console.warn(data.status);
              }
            },
            "JSON"
          );


      });


      //set the polling...
      setInterval(getQueue, 10000);
      getQueue();

    </script>

</body>

</html>