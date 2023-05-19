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


    <div id='room_title' class='subtitle'>Welcome to...</div>
    <div class='columns'>
      <!--PLAYER COLUMN-->
      <div class='column col_player is-half'>

        <!-- OWNER ONLY -->
        <div class='box' class="owner">
          <div id='player'></div>
          <div id='playing_title' class='subtitle is-6'></div>
          <div id='playing_username' class='title is-7'></div>
          <div class='queue_control'>
            <button class='button is-success' id='btn_play_next'>Play Next Song</button>
          </div>
        </div>

        <!-- PLAYER ONLY -->
        <div class='box' class="player">
          <div class='title is-7'>Now Playing</div>
          <img id='playing_thumbnail' />
          <div id='playing_title' class='subtitle is-5'></div>
          <div id='playing_username' class='title is-7'></div>
        </div>

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
    <script src="/js/client.js"></script>
</body>

</html>