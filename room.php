<?PHP 
require("ini.php");

$is_owner=false;  //default

if(!isset($_GET["id"])){
  header('Location: /');
  exit;
}
if(!isset($_COOKIE["user"])){
  header('Location: /?room=' . rawurlencode($_GET["id"]));
  exit;
}

// setup the room
if(isset($_GET["id"]) && isset($_COOKIE["user"])){
  $roomid = htmlspecialchars($_GET["id"], ENT_QUOTES, 'UTF-8');
  $user = json_decode($_COOKIE['user'], true);
  // var_dump($user);die();
  
  // create the user record if it doesn't exist
  if(!isset($user['email'])) $user['email'] = 'user@noemail.com';

  // if the room already exists and I'm not its owner, enforce its email allow/deny rules before letting me in
  $stmt = $conn->prepare("SELECT * FROM rooms WHERE name=?");
  $stmt->execute([$roomid]);
  $existing_room = $stmt->fetchAll();
  if(isset($existing_room[0]) && $existing_room[0]['userid'] != $user['id']){
    if(!room_email_allowed($existing_room[0], $user['email'])){
      http_response_code(403);
      die("Sorry, this room is restricted and your email address (".htmlspecialchars($user['email']).") isn't allowed in. Please contact the room owner.");
    }
  }

  $stmt = $conn->prepare("INSERT OR IGNORE INTO users (id, name, email) VALUES(?,?,?)");
  $stmt->execute([$user['id'], $user['name'], $user['email'] ]);

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

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
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
  <nav class='level is-mobile room_bar px-3 py-2 mb-0'>
    <div class='level-left'><a href='/' class='level-item has-text-weight-semibold'>&larr; Home</a></div>
    <div id='room_title' class='level-item has-text-centered is-size-6 truncate'>
      <?PHP if($is_owner){ ?><strong><?PHP echo $roomid; ?></strong><?PHP }else{ ?>Room <strong><?PHP echo $roomid; ?></strong> &middot; <?PHP echo htmlspecialchars($user['name']); ?><?PHP } ?>
    </div>
    <div class='level-right'>
      <?PHP if($is_owner===true){ ?><a id='btn_room_settings' class='level-item' title='Room settings'>&#9881;</a><?PHP } else { ?><span class='level-item'>&nbsp;</span><?PHP } ?>
    </div>
  </nav>
  <section class='section has-text-centered px-3 py-3'>
    <div class='columns room_columns'>
      <!--PLAYER COLUMN-->
      <div class='column col_player is-half-tablet'>

        <!-- OWNER ONLY -->
        <?PHP if($is_owner===true){  ?>
          <div class='box player_box p-3 mb-3'>
            <div id='player'></div>
            <div id='playing_title' class='subtitle is-6 mt-3 mb-1 line_clamp'></div>
            <div class='is-size-7 has-text-grey mb-3'>
              <span id='playing_username'></span>
              <a id="playing_user_ban" data-num='' class='has-text-danger'> ban</a>
            </div>
            <div class='queue_control'>
              <button class='button is-success is-fullwidth-mobile' id='btn_play_next'>Play Next Song</button>
            </div>
          </div>
        <?PHP } ?>
        
        <!-- PLAYER ONLY -->
        <?PHP if($is_owner===false){  ?>
          <div class='box player_box p-3 mb-3'>
            <div class='is-size-7 has-text-weight-bold has-text-grey is-uppercase mb-2'>Now Playing</div>
            <img id='playing_thumbnail' />
            <div id='playing_title' class='subtitle is-6 mt-2 mb-1 line_clamp'></div>
            <div id='playing_username' class='is-size-7 has-text-grey'></div>
          </div>
        <?PHP } ?>

        <div class='queue_wrap'>
          <h2 class='is-size-6 has-text-weight-bold has-text-left mb-2'>Up Next <span id='queue_count' class='tag is-rounded'>0</span></h2>
          <div id='queue' class='queue_list'>
            (no videos in the queue)
          </div>
        </div>
      </div>


      <!--SEARCH COLUMN-->
      <div class='column col_search is-half-tablet'>
        <div class='box'>
          <div class="field">
            <p class='help mb-2 has-text-left'>Search for a video and tap it to add to the room's playlist, or paste a YouTube URL or ID.</p>
            <div class="control">
              <input id="input_search" class="input is-primary" type="search" autocomplete="off" placeholder="Search...">
            </div>
            <!-- <div class="field" style="margin-top:8px;">
              <label class="checkbox"><input type="checkbox" id="force_fallback"> Force fallback (simulate API failure)</label>
            </div> -->
          </div>

          <div id='search_results'>

          </div>

          <div class='pagination is-centered mt-3'>
            <a id='go_prev' class='pagination-previous page_btn hidden'>&lsaquo; Prev</a>
            <a id='go_next' class='pagination-next page_btn hidden'>Next &rsaquo;</a>
          </div>

        </div>
      </div>

    </div>


    </div>
    <!--end section-->

    <?PHP if($is_owner===true){ ?>
    <!-- room settings modal (owner only) - mobile-friendly Bulma modal-card -->
    <div class="modal" id="room_settings_modal">
      <div class="modal-background"></div>
      <div class="modal-card">
        <header class="modal-card-head">
          <p class="modal-card-title">Room Access Settings</p>
          <button class="delete" aria-label="close" id="room_settings_close"></button>
        </header>
        <section class="modal-card-body has-text-left">
          <div class="field">
            <label class="label">Only allow emails matching <span class='has-text-grey'>(optional)</span></label>
            <div class="control">
              <input type="text" class="input" id="setting_allow_regex" placeholder="e.g. @kprschools\.ca$|@kprdsb\.net$">
            </div>
            <p class="help">If set, only Google logins whose email matches this pattern may join. Leave blank to allow anyone.</p>
          </div>
          <div class="field">
            <label class="label">Block emails matching <span class='has-text-grey'>(optional)</span></label>
            <div class="control">
              <input type="text" class="input" id="setting_deny_regex" placeholder="e.g. @gmail\.com$|tempmail|mailinator">
            </div>
            <p class="help">If set, logins matching this pattern are always blocked, even if they match the allow pattern above.</p>
          </div>
          <div class="notification is-info is-light">
            <p><strong>Regex hints:</strong></p>
            <ul>
              <li><code>@example\.com$</code> &mdash; matches any address ending in @example.com</li>
              <li><code>@foo\.com$|@bar\.com$</code> &mdash; matches either domain (use <code>|</code> for "or")</li>
              <li><code>^student[0-9]+@</code> &mdash; matches addresses starting with student + numbers</li>
              <li>Patterns are case-insensitive and don't need surrounding slashes. Deny always wins over allow.</li>
            </ul>
          </div>
        </section>
        <footer class="modal-card-foot">
          <button class="button is-success" id="room_settings_save">Save</button>
          <button class="button" id="room_settings_cancel">Cancel</button>
        </footer>
      </div>
    </div>
    <?PHP } ?>

    <!--hidden elements-->
    <div style='display:none'>

      <!-- video row template -->
      <div class="box video_row template p-1 mb-1" data-id=''>
        <div class='media is-align-items-center'>
          <figure class='media-left mr-3'><img src="https://bulma.io/images/placeholders/96x96.png" class='vid_thumbnail'></figure>
          <div class='media-content has-text-left'>
            <p class="is-size-7 has-text-weight-semibold vid_name line_clamp">John Smith</p>
            <p class="is-size-7 has-text-grey vid_description truncate">@johnsmith</p>
          </div>
        </div>
      </div>

      <!-- queue video row template -->
      <div class="box queue_row template p-1 mb-1" data-id=''>
        <div class='media is-align-items-center'>
          <?PHP if($is_owner===true) { ?><span class='drag_handle has-text-grey-light mr-2' title='Drag to reorder'>&#8942;</span><?PHP } ?>
          <figure class='media-left mr-3'><img src="https://bulma.io/images/placeholders/96x96.png" class='vid_thumbnail'></figure>
          <div class='media-content has-text-left'>
            <p class="is-size-7 has-text-weight-semibold vid_name line_clamp">John Smith</p>
            <p class="is-size-7 has-text-grey truncate">
              <span class="vid_description">@johnsmith</span>
              <?PHP if($is_owner===true) { ?><a class="user_ban has-text-danger" data-num=''>ban</a><?PHP } ?>
            </p>
          </div>
          <div class='media-right ml-2'><button class="delete vid_delete" aria-label="remove" data-num=''></button></div>
        </div>
      </div>


    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="/js/cookies.js"></script>
    <?PHP if($is_owner===true){ ?><script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script><?PHP } ?>

    <script>

      const is_owner = <?PHP echo(json_encode($is_owner));?>;
      const userid = <?PHP echo(json_encode($user['id']));?>;
      const user = <?PHP echo(json_encode($user));?>;

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
            height: '100%',
            width: '100%',
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

        // room access settings modal
        const room_settings = <?PHP echo json_encode(['allow_regex' => $room['allow_regex'] ?? '', 'deny_regex' => $room['deny_regex'] ?? '']); ?>;

        function openRoomSettings() {
          $("#setting_allow_regex").val(room_settings.allow_regex || '');
          $("#setting_deny_regex").val(room_settings.deny_regex || '');
          $("#room_settings_modal").addClass('is-active');
        }

        function closeRoomSettings() {
          $("#room_settings_modal").removeClass('is-active');
        }

        $("#btn_room_settings").on('click', openRoomSettings);
        $("#room_settings_close, #room_settings_cancel, .modal-background").on('click', closeRoomSettings);

        $("#room_settings_save").on('click', function() {
          var allow_regex = $("#setting_allow_regex").val().trim();
          var deny_regex = $("#setting_deny_regex").val().trim();

          $.post("/api.php", {
              roomid: <?PHP echo json_encode($roomid, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>,
              action: "update_settings",
              allow_regex: allow_regex,
              deny_regex: deny_regex
            },
            function(data, textStatus, jqXHR) {
              console.log("got back: ", data);
              if (data.status == 'success') {
                room_settings.allow_regex = allow_regex;
                room_settings.deny_regex = deny_regex;
                closeRoomSettings();
              } else {
                alert("Whoops - " + data.status);
              }
            },
            "JSON"
          );
        });

    <?PHP } ?>
    
            
      // search on text update OR handle direct YouTube URL / ID paste
      // better input handling: input event for typing, keydown for Enter, paste with timeout
      function handleSearchInput() {
        var q = $("#input_search").val().trim();
        if (q == '') return false;
        var vid = extractYouTubeID(q);
        if (vid) {
          addVideoById(vid);
        } else {
          searchVids();
        }
      }

      // Only trigger search/add when the user presses Enter.
      // Typing or pasting will behave like a normal textbox and will not auto-submit.
      $("#input_search").on("keydown input", function(e) {
        if (e.which === 13) {
          e.preventDefault();
          handleSearchInput();
        }
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


      // helper: extract YouTube video ID from many common URL patterns or accept raw 11-char id
      function extractYouTubeID(input) {
        if (!input) return false;
        input = input.trim();
        // common youtube url patterns (youtu.be/, youtube.com/watch?v=, /embed/, /v/, /shorts/)
        var urlMatch = input.match(/(?:youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
        if (urlMatch && urlMatch[1]) return urlMatch[1];

        // if it's a plain id (11 chars, allowed chars)
        var idMatch = input.match(/^([A-Za-z0-9_-]{11})$/);
        if (idMatch && idMatch[1]) return idMatch[1];

        return false;
      }

      // Test helper: return true if the test checkbox is checked to force the fallback path
      function isForceFallback(){
        try{
          return $('#force_fallback').is(':checked');
        }catch(e){
          return false;
        }
      }


      // helper: given a video id, fetch snippet details then post to add endpoint
      function addVideoById(videoid) {
        console.log('Adding direct video id', videoid);
        var apiKey = 'AIzaSyAuQwAKHd13idhbRRHVqOs6dlokLVVAufg';
        var url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' + encodeURIComponent(videoid) + '&key=' + apiKey;
        // If the test checkbox is checked, simulate API failure and use the fallback path.
        if (isForceFallback()){
          console.log('Force fallback enabled - skipping YouTube API and using minimal add for', videoid);
          postAddMinimal(videoid);
          return;
        }
        // Try to fetch metadata from YouTube API. If that fails (quota/network/private video),
        // fall back to adding the video with minimal metadata (so the room can still play it).
        $.getJSON(url, function(data) {
          if (data.items && data.items.length > 0) {
            var item = data.items[0];
            var rowdata = {
              videoid: videoid,
              title: item.snippet.title || ('YouTube Video ' + videoid),
              description: item.snippet.description || '',
              thumbnail: (item.snippet.thumbnails && item.snippet.thumbnails.default && item.snippet.thumbnails.default.url) ? item.snippet.thumbnails.default.url : ''
            };

            postAddSong(rowdata);

          } else {
            // No snippet data returned for this id (private/removed/etc.) - fallback to minimal add
            console.warn('No snippet data for video id', videoid, '- adding with minimal metadata');
            postAddMinimal(videoid);
          }
        }).fail(function(jqxhr, textStatus, error) {
          console.warn('YouTube API lookup failed:', textStatus, error, '- falling back to minimal add for', videoid);
          postAddMinimal(videoid);
        });
      }

      // Helper: post a minimal song object to the API (no thumbnail/title lookup)
      function postAddMinimal(videoid){
        var rowdata = {
          videoid: videoid,
          title: 'YouTube Video ' + videoid,
          description: '',
          // Use YouTube's standard static thumbnail as a graceful fallback so the UI isn't blank
          thumbnail: 'https://i.ytimg.com/vi/' + encodeURIComponent(videoid) + '/hqdefault.jpg'
        };
        console.log('Fallback add - sending minimal ADD', rowdata);
        postAddSong(rowdata);
      }

      // Helper: centralize posting to /api.php so both normal and fallback flows reuse the same code
      function postAddSong(rowdata){
        $.post('/api.php', {
            roomid: <?PHP echo json_encode($roomid, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>,
            action: 'add',
            song: rowdata
          },
          function(data, textStatus, jqXHR) {
            console.log('got back: ', data);
            if (data.status != 'success') {
              if (data.status.indexOf && data.status.indexOf('too many songs') > -1) {
                alert("Whoops - you've already got 2 songs in the queue. Please wait until one plays then try again.");
              } else {
                alert('Whoops - there was a problem adding that song...');
              }
            }
            getQueue();
            $("#input_search").val(''); //clear input
          },
          'JSON'
        );
      }


      // on video_row click, tyr to add the video to the queue
      // data.videoid, data.title, data.description, data.thumbnail, client.userid
      $("#search_results").on('click', '.video_row', function() {
        var rowdata = $(this).data('vid_data');
        console.log("song row clicked - sending ADD", rowdata);
          $.post("/api.php", {
            roomid: <?PHP echo json_encode($roomid, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>,
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
            roomid: <?PHP echo json_encode($roomid, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>,
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


      var queue_dragging = false;

      function buildQueue(data){
        // don't rebuild the list out from under the owner while they're dragging a row
        if(queue_dragging) return;
        // update UI with updated queue
        $("#queue").empty();
        $("#queue_count").text(data.length);
        $.each(data, function (i, item) {
          console.log('adding queue row', item);
          $row = $(".queue_row.template").clone();
          $row.removeClass("template");

          $row.data('vid_data', item);
          $row.attr('data-songid', item.songid);
          
          $row.find(".vid_name").html(item.title);
          $row.find(".vid_description").html(item.name + " · " + item.email);
          $row.find(".vid_thumbnail").prop('src', item.thumbnail);
          $row.find(".vid_delete").data('num', item.songid);
          $row.find(".user_ban").data('num', item.owner);

          // if I'm not the owner, or it's not my video
          if (!is_owner && (userid != item.owner)) {
            $row.find(".vid_delete").remove();
          }

          //if I'm the room owner, and it's my video, remove the ban button so I don't ban myself...
          if(is_owner && userid == item.owner){
            $row.find(".user_ban").remove();
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
        $("#playing_username").html(song.name + " · " + song.email);
        $("#playing_user_ban").data('num', song.owner);
        $("#playing_thumbnail").prop('src', song.thumbnail);

        if(is_owner && userid == song.owner){
          $("#playing_user_ban").hide();
        }else{
          $("#playing_user_ban").show();
        }

      }


      $("#btn_play_next").click(function() {
        console.log("trying to play next song...")
        
        $.post("/api.php", {
            roomid: <?PHP echo json_encode($roomid, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>,
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
            roomid: <?PHP echo json_encode($roomid, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>,
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


      function banUser(banid){
        console.log("banning user", banid);
        if(banid && confirm("Are you sure you want to BAN the user: "+banid)){

          $.post("/api.php", {
            roomid: <?PHP echo json_encode($roomid, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>,
            action:"ban",
            banid:banid
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
        }
      }

      $("#queue").on('click', '.user_ban', function() {
        var banid = $(this).data('num');
        banUser(banid);
      });

      $("#playing_user_ban").on('click', function() {
        var banid = $(this).data('num');
        banUser(banid);
      });


      // owner can drag the waiting songs into a new order
      if(is_owner){
        new Sortable(document.getElementById('queue'), {
          draggable: '.queue_row',
          handle: '.drag_handle',
          animation: 150,
          ghostClass: 'has-background-light',
          onStart: function(){ queue_dragging = true; },
          onEnd: function(){
            queue_dragging = false;
            var ids = $("#queue .queue_row").map(function(){ return $(this).attr('data-songid'); }).get();
            $.post("/api.php", {
                roomid: <?PHP echo json_encode($roomid, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>,
                action: "reorder",
                songids: ids
              },
              function(response){
                if(response.status != 'success') console.warn("reorder failed", response.status);
              },
              "JSON"
            ).always(getQueue); // re-render from the server whether or not the save worked
          }
        });
      }

      //set the polling...
      setInterval(getQueue, 10000);
      getQueue();

    </script>

</body>

</html>