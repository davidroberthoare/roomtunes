"use strict";
var __importDefault = (this && this.__importDefault) || function(mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};

Object.defineProperty(exports, "__esModule", { value: true });
const http = __importDefault(require("http"));
const path = __importDefault(require("path"));
const express = __importDefault(require("express"));
const cors = __importDefault(require("cors"));
const colyseus = require("colyseus");
// const monitor = require("@colyseus/monitor");

const myParser = require("body-parser"); //for 'posts'
const cookieParser = require('cookie-parser');

// for storing room ownership
const keyFileStorage = require("key-file-storage");
const kfs = keyFileStorage.default('./cache', true);

// IMPORT GAME ROOMS
const Room = require("./room"); // basic starter code



// const port = Number(process.env.PORT || 2568);
const port = Number(3123);
const app = express.default();


var engine = require('consolidate');

app.set('views', __dirname + '/views');
app.engine('html', engine.mustache);
app.set('view engine', 'html');


app.use(cors.default());
app.use(cookieParser());

app.get('/room/:roomid', function(req, res){
   console.log("google cookies?", req.cookies['g_state']);
   if(req.cookies['user']){
       var userdata = JSON.parse(req.cookies['user']);
       
       var is_owner = false;
    //   if there's a room with this id
        if(kfs[req.params.roomid]){
            // are we the owner?
            if(kfs[req.params.roomid]==userdata.id){
                is_owner = true;
            }
        }
        else{   //otherwise, set us as the new owner
          kfs[req.params.roomid]=userdata.id;
          is_owner = true;
        }
       
       console.log("got cookie, so showing room page. Userdata", userdata );
       res.render('room.html',  { roomid:req.params.roomid, userid: userdata.id, username: userdata.name , is_owner:is_owner});
   }else{
       console.log("no g_state cookie, so redirecting to login page...")
       res.redirect('/index.html');
   }
});

// default static public route
app.use(express.default.static(path.default.resolve(__dirname, "public")));

// listen for Google sign-in POST data
// app.use(myParser.urlencoded({ extended: true }));
// app.post("/google_login", function(request, response) {
//     console.log(request.body); //This prints the JSON document received (if it is a JSON document)
//     if (request.body.idtoken) {
//         verify(request.body.idtoken, response).catch(console.error);
//     }
//     else {
//         console.log('no credential to verify')
//     }
// });



// define the server
const server = http.default.createServer(app);
const gameServer = new colyseus.Server({
    server,
});


// TEST STARTER GAME
gameServer
    .define('room', Room.Room, {
        // maxPlayers: 8 
    })
    .filterBy(['roomid'])


gameServer.listen(port);
console.log(`Listening on ws://localhost:${port}`);



// GOOGLE VERIFICATION stuff
// const { OAuth2Client } = require('google-auth-library');
// const client = new OAuth2Client('617263820739-6q6d7g6402qqmfap0rrk6sr1alm9i3hr.apps.googleusercontent.com');
// async function verify(token, response) {
//     console.log("trying to verify token", token)
//     const ticket = await client.verifyIdToken({
//         idToken: token,
//         audience: '617263820739-6q6d7g6402qqmfap0rrk6sr1alm9i3hr.apps.googleusercontent.com', // Specify the CLIENT_ID of the app that accesses the backend
//     });
//     const payload = ticket.getPayload();
//     const userid = payload['sub'];
//     console.log("verified successfully. UserID", userid);
    
//     var userdata = {
//         id: payload['sub'], 
//         name: payload['name']
//     };
//     var cookiestring = JSON.stringify(userdata);
//     console.log("cookiestring", userdata);
//     response.cookie("user", userdata); 
    
//     response.redirect('/chooser.html');
// }
