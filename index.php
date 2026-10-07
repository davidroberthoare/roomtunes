<?PHP 
  require("ini.php");
?>
<!DOCTYPE html>
<html>

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RoomTunes</title>
  <meta name="description" content="Super-simple shared playlists for group listening">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css">
  <link rel="stylesheet" href="/css/styles.css">
  <script src="/js/cookies.js" async defer></script>


  <!-- Global site tag (gtag.js) - Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?PHP echo $env['G_TAG_ID'];?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() {dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', '<?PHP echo $env['G_TAG_ID'];?>');
  </script>


  <link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png">
  <link rel="manifest" href="/img/site.webmanifest">
  <link rel="mask-icon" href="/img/safari-pinned-tab.svg" color="#5bbad5">
  <meta name="msapplication-TileColor" content="#2d89ef">
  <meta name="theme-color" content="#ffffff">

  <!-- register service worker - needed for PWA install-->
  <script>
    // if ('serviceWorker' in navigator) {
    //   console.log("Will the service worker register?");
    //   navigator.serviceWorker.register('/js/sw.js')
    //     .then(function (reg) {
    //       console.log("Yes, it did.");
    //     }).catch(function (err) {
    //       console.log("No it didn't. This happened:", err)
    //     });
    // }
  </script>


  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script>
    var requestedRoom = new URLSearchParams(window.location.search).get('room') || '';

    // decode a JWT's payload without verifying its signature (verification happens implicitly via the Google-hosted sign-in flow)
    function parseJwt(token) {
      var base64Url = token.split('.')[1];
      var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
      var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
      }).join(''));
      return JSON.parse(jsonPayload);
    }

    function handleCredentialResponse(response) {
      var profile = parseJwt(response.credential);
      console.log("ID: " + profile.sub);
      console.log('Full Name: ' + profile.name);
      console.log("Email: " + profile.email);

      var userdata = {
        id: profile.sub,
        name: profile.name,
        email: profile.email
      };
      var cookiestring = JSON.stringify(userdata);
      Cookies.set('user', cookiestring, {expires: 60000}); // Expires in 10 minutes

      if (requestedRoom) {
        gothere();
        return;
      }
      showChooser(userdata);
    }

    function showChooser(userdata) {
      $("#chooser").show();
      $(".intro_text").addClass('hidden');
      if (userdata) {
        $("#active_account")
          .text(userdata.email)
          .attr('title', userdata.email);
      }
    }

    window.onload = function() {
      if (requestedRoom) {
        $("#room_code").val(requestedRoom);
      }

      // already signed in to our app (cookie still valid) - skip straight to the room chooser
      var existing = Cookies.get('user');
      if (existing) {
        try {
          showChooser(JSON.parse(existing));
          if (requestedRoom) {
            gothere();
            return;
          }
        } catch (e) {
          Cookies.remove('user');
        }
      }

      google.accounts.id.initialize({
        client_id: "<?PHP echo $env['G_CLIENT_ID'];?>",
        callback: handleCredentialResponse
      });
      google.accounts.id.renderButton(
        document.getElementById("g_id_signin"),
        {type: "icon", theme: "outline", size: "large", shape: "circle"}
      );
    };


    function gothere() {
      if ($("#room_code").val() != '') {
        window.location.replace("/room.php?id=" + encodeURIComponent($("#room_code").val()));
      } else {
        alert("please enter a room code");
      }
    }
  </script>

</head>

<body>
  <section class="hero is-info">
    <div class="hero-body">
      <div class="columns">
        <div class="column">
          <h1 class="title">
            RoomTunes
          </h1>
          <h2 class="subtitle">
            Super-simple shared playlists
          </h2>
        </div>
        <div class="column is-narrow has-text-right">
          <div class='intro_text'>To get started, log-in with your Google account...</div>
          <div class="account_controls">
            <span id="active_account"></span>
            <div id="g_id_signin" title="Sign in or switch Google account"></div>
          </div>
        </div>
      </div>


    </div>
  </section>

  <section class='section' id='chooser' style='display:none;'>
    <div class='box has-text-centered'>
      <div class='title is-5'>Enter a room code:</div>
      <div class='field'>
        <div class='control'>
          <input id='room_code' class='input is-large has-text-centered' style='width:300px' placeholder='room_code'
            onchange='gothere()' />
        </div>
      </div>
      <div class='field'>
        <div class='control'>
          <button class='button is-primary' onclick='gothere()'>Enter Listening Room</button>
        </div>
      </div>
    </div>
  </section>

</body>

</html>