// const endpoint = `${window.location.protocol.replace("http", "ws")}//${window.location.hostname}`;
// var client = new Colyseus.Client(endpoint);
// var url = new URL(window.location.href);

// // Get the value of "some_key" in eg "https://example.com/?some_key=some_value"
// // var roomid = "{{roomid}}";
// // var room;
// // var userid = "{{userid}}";
// // var username = "{{username}}";
// // var is_owner = {{is_owner}};

// const params = new Proxy(new URLSearchParams(window.location.search), {
//   get: (searchParams, prop) => searchParams.get(prop),
// });
// let roomid = params.id; // "some_value"

// // var userdata = {
// //   id: profile.getId(),
// //   name: profile.getName()
// // };
// var cookiestring = Cookies.get('user'); // Expires in 10 minutes
// if (!cookiestring) {
//   console.error("NO USER SET, bouncing...")
//   window.location.href = "/"
// }

// const userdata = JSON.parse(cookiestring);
// console.log("userdata:", userdata)

// var userid = userdata.id;
// var username = userdata.name;
// var is_owner = true;




// {{#is_owner} }


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
