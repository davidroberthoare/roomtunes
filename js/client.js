// const endpoint = `${window.location.protocol.replace("http", "ws")}//${window.location.hostname}`;
// var client = new Colyseus.Client(endpoint);
var url = new URL(window.location.href);

// Get the value of "some_key" in eg "https://example.com/?some_key=some_value"
// var roomid = "{{roomid}}";
// var room;
// var userid = "{{userid}}";
// var username = "{{username}}";
// var is_owner = {{is_owner}};

const params = new Proxy(new URLSearchParams(window.location.search), {
  get: (searchParams, prop) => searchParams.get(prop),
});
let roomid = params.id; // "some_value"

// var userdata = {
//   id: profile.getId(),
//   name: profile.getName()
// };
var cookiestring = Cookies.get('user'); // Expires in 10 minutes
if (!cookiestring) {
  console.error("NO USER SET, bouncing...")
  window.location.href = "/"
}

const userdata = JSON.parse(cookiestring);
console.log("userdata:", userdata)

var userid = userdata.id;
var username = userdata.name;
var is_owner = true;


// HIDE/SHOW Layout
if (is_owner) {
  $(".owner").show()
  $(".player").remove()
  $("#room_title").text("Welcome to your room, " + username + ".")
} else {
  $(".player").show()
  $(".owner").remove()
  $("#room_title").text("Welcome, " + username + ".")
}


// {{#is_owner} }
if (is_owner) {

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

}

// Connect to the chatroom
// client.joinOrCreate("room", {roomid: roomid, userid: "{{userid}}", username: "{{username}}"}).then(myroom => {
//   room = myroom;
//   console.log(room.sessionId, "joined", room.name);

//   setInterval(function () {
//     console.log("ping");
//     room.send("ping");
//   }, 10000)

//   room.onMessage("pong", (message) => {
//     console.log("pong");
//   });


//   room.onMessage("chat_broadcast", (message) => {
//     console.log("message received from server", message);
//     if (message.text += "") {
//       $('#game').append("<div class='msg'>" + message.text + '</div>');
//     }
//   });


//   // state change listener
//   room.state.onChange = (changes) => {
//     // console.log("state change", changes);
//     changes.forEach(change => {
//       console.log(change.field);
//       console.log(change.value);

//       if (change.field == 'queue') {
//         // update UI with updated queue
//         $("#queue").empty();
//         var queue = change.value;
//         $.each(queue, function (i, item) {
//           console.log('adding queue row', item);
//           $row = $(".queue_row.template").clone();
//           $row.removeClass("template");

//           $row.data('vid_data', item);

//           $row.find(".vid_name").html(item.title);
//           $row.find(".vid_description").html(item.username);
//           $row.find(".vid_thumbnail").prop('src', item.thumbnail);
//           $row.find(".vid_delete").data('num', i);

//           // if I'm not the owner, or it's not my video
//           if (!is_owner && (userid != item.userid)) {
//             $row.find(".vid_delete").remove();
//           }

//           $("#queue").append($row);
//         });
//       }


//       // start playing the current song
//       if (change.field == 'currentSong') {
//         $("#queue").empty();
//         var song = change.value;
//         if (!song.title) song.title = '(no song playing)'

//         console.log("new current song:", song);
//         if (is_owner) {
//           if (player_ready && song.videoid) {
//             player.loadVideoById(song.videoid);
//           }
//         }
//         $("#playing_title").html(song.title);
//         $("#playing_username").html(song.username);
//         $("#playing_thumbnail").prop('src', song.thumbnail);
//       }


//     });
//   };




// }).catch(e => {
//   console.log("JOIN ERROR", e);
// });

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
  console.log("song row clicked - sending addsong", rowdata);

  var canAdd = true;
  //check if we've already got 2 in the queue (if we're not the owner)
  if (!is_owner) {
    var mycount = 0;
    $.each(room.state.queue, function(i, song) {
      if (song.userid == userid) {
        console.log("got one");
        mycount += 1;
      }

      if (mycount >= 2) {
        console.log("found 2, so not submitting");
        alert("Whoops - you've already got 2 songs in the queue. Please wait until one plays then try again.");
        canAdd = false;
      }
    });
  }

  if (canAdd == true) {
    room.send("addSong", rowdata);    // send to the server
  }
});


$("#btn_play_next").click(function() {
  console.log("trying to play next song...")
  room.send("nextSong");
});


$("#queue").on('click', '.vid_delete', function() {
  var id = $(this).data('num');
  console.log("deleting vid", id);
  room.send("deleteSong", id);
});
