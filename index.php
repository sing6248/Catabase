<?php

ini_set('display_errors', 1);

//start managing the user session.
session_start();

if( isset($_REQUEST['logout'])){
  session_destroy(); // logout
  header("Location: ?login_form"); //redirect to another page.
}

// autoload all required classes
// DB, User, Post, Like, Friendship, Layout
spl_autoload_register(function ($class) {
    include 'classes/'.$class . '.php';
});

$db = new DB();
$page = new Layout($db);  // pass $db along so layout can run queries!

//if the user is not logged in, onboard them.
if ( !isset($_SESSION['user_id']) ){
	if ( isset($_REQUEST['reg_button'])){
		$status = $db->register();
		$page->addWarning($status); //warnings show up in red
	}
	if( isset($_REQUEST['login_button'])){
		$status = $db->login();
		$page->addWarning($status);  //warnings show up in red
    $_REQUEST['login_form'] = true; //if the login fails, try again.
	}
	if (isset($_REQUEST['reg_success'])){
    //notices show up in green
		$page->addNotice ('Thanks '.$_REQUEST['reg_success'].', you are now registered!');
	}
	$page->onboarding();
}

else{

  // if the user is logged in track them and provide options.
  // set a global variable to contain the logged in user.
  // this variable can be accessed anywhere via $GLOBALS['user']
	global $user;
	$user = new User($db, $_SESSION['user_id']);

  // manage bio
  if (isset($_REQUEST['bio_save_button'])){
		$user->saveBio();
	}
  // deal with like button.
	if (isset($_REQUEST['like'])){
		  $post = new Post ($db, $_REQUEST['like']);
		  $like = new Like ($db, $user, $post);
		  $like->toggle();
		  echo $post->likes();
      exit; // a like is requested via ajax. no need to render anything else
	}
  // process new meows (i.e. posts)
	if(isset($_REQUEST['meow_button'])){
		$post = new Post($db);
		$post->uploadFile($_FILES['meow_file']);
		$post->submit($_REQUEST['meow_text']);
	}
  // manage friend requests
	if(isset($_REQUEST['friend_button'])){
		$friends = [ $user->id, $_REQUEST['profile'] ];
		$friendship = new Friendship( $db, $friends );
		$friendship->request();
	}
  // approve friend requests
	if(isset($_REQUEST['approve_button'])){
		$friends = [ $user->id, $_REQUEST['profile'] ];
		$friendship = new Friendship( $db, $friends );
		$friendship->approve($user);
	}

  $page->navigation($user);

  // look for new friends
	if (isset($_REQUEST['explore'])){
		$page->explore($user);
	}
  // show a profile of a cat
	elseif (isset($_REQUEST['profile'])){
		//if a profile is requested, show it.
		$someone = new User($db, $_REQUEST['profile']);
		$page->profile($someone);
	}
	else{
		// otherwise show the logged-in user's profile.
		$page->newsfeed($user);
	}

}



$page->header();
$page->content();
$page->footer();

?>
