const colyseus = require('colyseus');
const schema = require('@colyseus/schema');
const Schema = schema.Schema;
const ArraySchema = schema.ArraySchema;

class Song extends Schema {}
schema.defineTypes(Song, {
  videoid: "string",
  title: "string",
  description: "string",
  thumbnail: "string",
  userid: "string",
  username: "string"
});

class MyState extends Schema {
  constructor() {
    super();
    this.currentSong = new Song();
    this.queue = new ArraySchema();
  }
}

schema.defineTypes(MyState, {
  currentSong: Song,
  queue: [Song]
});


exports.Room = class extends colyseus.Room {
    // When room is initialized
    onCreate (options) { 
        console.log("creating new Room", options);
        this.setState(new MyState());
        
        this.sessionId = options.roomid;
        console.log("set room sessionID to ", options.roomid);
        
        
        /*client wants to add a new song to the list*/
        this.onMessage("addSong", (client, data) => {
          if(data.videoid){
            try{
              var song = new Song();
              song.videoid = data.videoid;
              song.title = data.title;
              song.description = data.description;
              song.thumbnail = data.thumbnail;
              song.userid = client.userid;
              song.username = client.username;
              
              this.state.queue.push(song);
              console.log("added new song", song);
            }catch(e){
              console.log("error adding item", e);
            }
          }
          
        });
        
        /*owner wants to play the next song*/
        this.onMessage("nextSong", (client) => {
          try{
            if(this.state.queue.length > 0){
              this.state.currentSong = this.state.queue[0]; //assign the current song
              console.log("updated currentsong", this.state.currentSong);
              this.state.queue.shift(); //remove it from the queue
            }
          }catch(e){
            console.log("error going to next song", e);
          }
        });
        
        
        /*owner wants to delete a song from the queue*/
        this.onMessage("deleteSong", (client, id) => {
          try{
            if(this.state.queue[id]){
              this.state.queue.splice(id, 1);
            }
          }catch(e){
            console.log("error deleting song", e);
          }
        });
        
    }

    // When client successfully join the room
    onJoin (client, options, auth) { 
      client.userid = options.userid;
      client.username = options.username;
      console.log("client joined room: ", {room_session: this.sessionId, client_session:client.sessionId, client_userid: client.userid, client_username: client.username});
      
    }

    // When a client leaves the room
    onLeave (client, consented) { 
      console.log("client left", client.sessionId);
    }

    // Cleanup callback, called after there are no more clients in the room. (see `autoDispose`)
    onDispose () { 
      console.log("room disposed");
    }
}