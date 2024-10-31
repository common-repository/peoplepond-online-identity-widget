<?php
/*
Plugin Name: PeoplePond Online Identity Widget
Version: 0.1
Plugin URI: http://www.peoplepond.com/adam.php
Description: Extend the reach and usability of your PeoplePond profile, including your verified identity, with the PeoplePond Identity Widget in the sidebar of your WordPress blog. It will display a badge to your blog readers detailing who you are and what other services they can follow you on.

(Also see the PeoplePond WordPress plugin to create an always up-to-date "About Me" page).

A PeoplePond profile is the hub of your online identity. It serves to train the search engines about you and where to find all your best online assets. It also provides a single location for your followers from where you can prove your identity and exert ownership over your online assets. This provides your followers with the assurance that you are who you say you are and that your accounts, blogs, etc. are really yours and not someone pretending to be you.

When you update your profile, your widget will be automatically updated keeping everything up-to-date with a minimum of effort.

Your name, title/tagline, picture, and identity verification indicator are all displayed over a graphical display of all the services that you have included in the online identity section of your PeoplePond profile.

Author: Al Castle
Author URI: http://www.peoplepond.com/AlCastle
*/

define( '__PP_BADGE_WIDGET_TITLE__', 'PeoplePond Online Identity Widget');
define( '__PP_BADGE_WIDGET_TITLE_SHORT__', 'PeoplePond Online Identity');
define( '__PP_BADGE_BASE_URL__', 'http://adam.peoplepond.com/peeps.php?');
define( '__PP_BADGE_ADAM_ID__', 'pp_adam_id');
define( '__PP_BADGE_ADAM_HEADER__', 'pp_adam_header');
define( '__PP_SITE_URL__', 'http://www.peoplepond.com');
define( '__PP_SITE_URL_REGISTER__', 'http://www.peoplepond.com/register.php');
define( '__PP_BADGE_MESG__', 'You must have an active <a href="'.__PP_SITE_URL__.'">PeoplePond</a> account. <a href="'.__PP_SITE_URL_REGISTER__.'">Register</a> for free.');


// Determine if the identifier is a displayName or an email
function pp_IdByType($id=null)
{
	$type = null;	
	if ( !empty($id) )
	{
		if ( preg_replace('/[_A-Z0-9]/i', '', $id) == ''  )
		{
			$type = 'displayName';
		} elseif ( strstr($id, '@') ) {
			$type = 'email';
		} 
	}
	
	
	return $type;
}


// Construct the URL required by the ADAM API
// See http://www.peoplepond.com/adam-api.php for more details 
function pp_BuildUrl( $id=null )
{
	$url = null;
	
	if (!empty($id) )
	{
		$params = null;
		$type = pp_IdByType($id);
	
		switch( $type )
		{
			case 'email':
			$params = $type .'='.md5($id);	
			break;
			
			case 'displayName':
			$params = $type.'='.$id;
			break;
		}
		
		if( !empty($params) )
		{
			$url = __PP_BADGE_BASE_URL__ . $params . "&widget=yes&site=" .$_SERVER ['SERVER_NAME'];
		}
	}
	
	
	return $url;
}


// Make the call to the API and return the results
function pp_GrabAdam($id=null)
{
	$pp_profile = null;
	
	$url = pp_BuildUrl($id);
	if ( !empty($url) )
	{
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, "$url");
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURL_USERAGENT, "PeoplePond WP Widget");
		$pp_profile = curl_exec($curl_handle);
		curl_close($curl_handle);
	}
	
	if ( empty($pp_profile) )
	{
		$pp_profile = __PP_BADGE_MESG__;
	}
	
	
	return $pp_profile;
}


// Display the final public product
function pp_Load($args)
{
	extract($args);
	echo $before_widget;
	
	// Display the header for this sidebar if they've optioned for that
	$option_header = get_option( __PP_BADGE_ADAM_HEADER__ );
	if( $option_header[__PP_BADGE_ADAM_HEADER__] != '' )
	{
		echo $before_title . __PP_BADGE_WIDGET_TITLE_SHORT__ . $after_title;	
	}
	// Display the badge
	$options = get_option( __PP_BADGE_ADAM_ID__ ) ;
	echo pp_GrabAdam( $options[__PP_BADGE_ADAM_ID__] );
}


// Configuration screen with some defaults
function pp_Control()
{
	$option_id = get_option( __PP_BADGE_ADAM_ID__ );
	$option_header = get_option( __PP_BADGE_ADAM_HEADER__ );
	
	if( !is_array($option_id) )
	{
		$option_id = array( __PP_BADGE_ADAM_ID__ => 'AlCastle');
	}
	if ( !is_array($option_header) ) 
	{
		$option_header = array(__PP_BADGE_ADAM_HEADER__ => 'checked="checked"' );
	}
	
	if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
	{
		if ( $_POST[__PP_BADGE_ADAM_ID__] )
		{
			$option_id[__PP_BADGE_ADAM_ID__] = htmlspecialchars($_POST[__PP_BADGE_ADAM_ID__]);
			update_option(__PP_BADGE_ADAM_ID__, $option_id);
		}
	
		if ( $_POST[__PP_BADGE_ADAM_HEADER__] == 1 )
		{
			$option_header[__PP_BADGE_ADAM_HEADER__] = 'checked="checked"';
		} else {
			$option_header[__PP_BADGE_ADAM_HEADER__] = null;
		}
		update_option(__PP_BADGE_ADAM_HEADER__, $option_header);
	} 

	?>
	  <p>
	    <label for="<?php echo __PP_BADGE_ADAM_ID__;?>">PeoplePond email or username:</label>
	    <input type="text" id="<?php echo __PP_BADGE_ADAM_ID__;?>" name="<?php echo __PP_BADGE_ADAM_ID__;?>" value="<?php echo $option_id[__PP_BADGE_ADAM_ID__] ;?>" />
	  </p>
	  
	  <p> 
	    <label for="<?php echo __PP_BADGE_ADAM_HEADER__;?>">Show the header for this sidebar?:</label>
	    <input type="checkbox" id="<?php echo __PP_BADGE_ADAM_HEADER__;?>" name="<?php echo __PP_BADGE_ADAM_HEADER__;?>" value="1" <?php echo $option_header[__PP_BADGE_ADAM_HEADER__];?>" />
	    
	    <input type="hidden" id="pp_adam_id-Submit" name="pp_adam-Submit" value="1" />
	  </p>
	  <p>
		<?php echo __PP_BADGE_MESG__; ?>
	  </p>
	<?php
	
}


// Initialize Internet Identification 
function pp_Init()
{
	register_sidebar_widget(_(__PP_BADGE_WIDGET_TITLE__), 'pp_Load');
	register_widget_control( __PP_BADGE_WIDGET_TITLE__, 'pp_Control' );
}


add_action("plugins_loaded", "pp_Init");


?>