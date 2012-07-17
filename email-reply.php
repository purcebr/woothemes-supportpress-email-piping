<?php
/*
Plugin Name: SupportPress Email Reply
Plugin URI: http://plugins.aveight.com
Description: Allow reply-by-email for support tickets
Author: Bryan Purcell
Author URI: http://aveight.com
Version: 0.1
Tags: woothemes,email reply
License: GPL2
*/
//require('api.inc.php');
define('ROOT_DIR',str_replace('\\\\', '/', realpath(dirname(__FILE__))).'/'); #Get real path for root dir ---linux and windows
define('INCLUDE_DIR',ROOT_DIR.'include/'); //Change this if include is moved outside the web path.
define("DEFAULT_EMAIL_PRI", 4);
define("DEFAULT_TICKET_TYPE", '');
define("DEFAULT_RESPONSIBILITY", '');
define("DEFAULT_TICKET_STATUS", 0);
define("DEFAULT_EMAIL_TAGS", 'emailed');

//require_once(INCLUDE_DIR.'class.mailparse.php');
require_once(INCLUDE_DIR.'class.format.php');
require_once(INCLUDE_DIR.'MimeMailParser.class.php');
require_once(INCLUDE_DIR.'class.supportobject.php');

require_once(ABSPATH . "wp-admin" . '/includes/file.php');					
require_once(ABSPATH . "wp-admin" . '/includes/image.php');

$emailreply = new Email_Reply;

function map_ticket_to_hash($ticket_id) {

$ticket_id = get_post_meta($ticket_id, '_ticket_hash',true);

if($ticket_id != '' || $ticket != '0')
return $ticket_id;
else
return false;

}

function add_subject_number($subject, $comment_id) {

//Look up comment's parent

$comment = get_comment($comment_id);
$number = $comment->comment_parent;
return '#' .$number . ' ' . $subject;
}

add_filter('comment_notification_subject', '','');



class Email_Reply {


	function init(){
	remove_action('new_ticket', 'woo_supportpress_email_new_ticket', 1, 1);
remove_action('new_ticket', 'woo_supportpress_email_owner_of_new_ticket', 1, 1);
remove_action('new_ticket', 'woo_supportpress_email_assigned_to_new_ticket', 1, 1);
remove_action('comment_post', 'woo_supportpress_email_commented_item', 2);



remove_action('new_message', 'woo_supportpress_email_new_message', 1, 1);
remove_action('ticket_updated', 'woo_supportpress_email_updated_ticket', 1, 3);

	}
	function __construct()
	{
		new Email_Reply_Options;
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action('admin_menu', array( &$this, 'plugin_admin_add_page'));


		add_action( 'init', array( &$this, 'init' ) );
		# Place your add_actions and add_filters here
	} // function
	
	function plugin_admin_add_page() {
add_options_page('Email Reply Plugin Page', 'Email Reply Plugin Menu', 'manage_options', 'plugin', array( &$this, 'email_reply_options_page') );
	}
	
function email_reply_options_page() {

echo '<div>';
echo '<h2>Woothemes Supportpress Email Reply</h2>';
echo 'Options relating to the Woothemes Supportpress Email Reply Plugin.';
echo '<form action="options.php" method="post">';
settings_fields('email_reply_options');
do_settings_sections('plugin');

echo '<input name="Submit" type="submit" value="Save Changes" />';
echo '</form></div>';

}

function admin_init()
	{
register_setting( 'email_reply_options', 'email_reply_options', array( &$this, 'email_reply_options_validate' ));

add_settings_section('plugin_main', 'General', array( &$this, 'plugin_section_text_general'), 'plugin');
add_settings_field('email_reply_plugin_setting_general_checkbox_allow_attachments', 'Allow Attachments', array( &$this, 'email_reply_plugin_setting_general_checkbox_allow_attachments'), 'plugin', 'plugin_main');
add_settings_field('email_reply_plugin_setting_general_checkbox_allow_piping', 'Allow Email Piping', array( &$this, 'email_reply_plugin_setting_general_checkbox_allow_piping'), 'plugin', 'plugin_main');



add_settings_section('plugin_main', 'Email Piping', array( &$this, 'plugin_section_text_email_piping'), 'plugin');
add_settings_field('plugin_text_string_ip', 'Whitelisted IP SMTP Server', array( &$this, 'email_reply_plugin_setting_email_piping_string_ip'), 'plugin', 'plugin_main');
add_settings_field('plugin_text_string_key', 'API Key (must be 16 characters long)', array( &$this, 'email_reply_plugin_setting_email_piping_string_key'), 'plugin', 'plugin_main');

	} // function

 // add the admin settings and such
function email_reply_options_validate($input) {
		$newinput = $input;

if (preg_match( "/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $input['allowed_ip']))
{
        $newinput['allowed_ip'] = $input['allowed_ip'];
    }
    else
    {
        $newinput['allowed_ip'] = '';
        add_settings_error( 'plugin_text_string_ip', 'texterror', 'Invalid IP Address', 'error' );

    }


if (( strlen($input['api_key']) > 12 ) && (preg_match("/^.*(?=.{6,})(?=.*[a-zA-Z])[a-zA-Z0-9]+$/",$input['api_key'])))
{
        $newinput['api_key'] = $input['api_key'];
}
else
{
		$newinput['api_key'] ='';
		add_settings_error( 'plugin_text_string_key', 'texterror', 'API key must contain numbers and letters with a length of at least 6 characters.', 'error' );

}



return $newinput;


}

function plugin_section_text_general() {
echo '<p>General settings for the woothemes supportpress email ticket posting plugin</p>';
}

function email_reply_plugin_setting_general_checkbox_allow_attachments() {
$options = get_option('email_reply_options');
echo "<input id='email_reply_plugin_setting_general_checkbox_allow_attachments' name='email_reply_options[allow_attachments]' type='checkbox' value='{$options['allow_attachments']}' "; if (isset($options['allow_attachments'])) echo ' checked="checked" '; echo " />";

}
function email_reply_plugin_setting_general_checkbox_allow_piping() {
$options = get_option('email_reply_options');
echo "<input id='email_reply_plugin_setting_general_checkbox_allow_piping' name='email_reply_options[allow_piping]' type='checkbox' value='{$options['allow_attachments']}' "; if (isset($options['allow_piping'])) echo ' checked="checked" '; echo " />";

}

function plugin_section_text_email_piping() {
echo '<p>Remote SMTP email piping settings.</p>';
}
function email_reply_plugin_setting_email_piping_string_ip() {
$options = get_option('email_reply_options');
echo "<input id='email_reply_plugin_setting_email_piping_string_ip' name='email_reply_options[allowed_ip]' size='40' type='text' value='{$options['allowed_ip']}' />";


}

function email_reply_plugin_setting_email_piping_string_key() {
$options = get_option('email_reply_options');
echo "<input id='email_reply_plugin_setting_email_piping_string_key' name='email_reply_options[api_key]' size='40' type='text' value='{$options['api_key']}' />";


}
	
function do_parse ($data){
	global $uploads;

	$uploads = wp_upload_dir();

	$Parser = new MimeMailParser();
	$Parser->setText($data);
	//if(!$parser->decode()){ //Decode...returns false on decoding errors
	    //api_exit(EX_DATAERR,'Email parse failed ['.$parser->getError()."]\n\n".$data);    
	//}



	$t = new SupportObject();

	$t->loadObject($Parser);
	$t->saveObject();
	if($t->isRegNeeded())
	{
		$t->mail_registration_needed($t->getFrom());
	}

	else {
		$t->mail_created_success();
	}

	if($t->unauthorizedAttachments())
	{
		$t->mail_attachments_not_allowed();
	}






}
} // class

class Email_Reply_Options {

	function Email_Reply_Options()
	{
		$this->__construct();
	} // function

	function __construct()
	{
		# Place your add_actions and add_filters here
		
		//add_action('admin_menu', 'plugin_admin_add_page');
//$this->admin_init();
		
	} // function

	function email_reply()
	{
		$value = get_option('email_reply');
		# echo your form fields here containing the value received from get_option
	} // function



} // class



/*-----------------------------------------------------------------------------------*/
/* New Ticket Notices
/*-----------------------------------------------------------------------------------*/

function woo_supportpress_email_new_ticket_a( $ticket_id ) {
	
	$ticket = get_post($ticket_id);
	$auth_id = $ticket->post_author;
	$user = new WP_User($auth_id);

	if ($user->display_name !='') :
		$display_name = $user->display_name;
	else :
		$display_name = __('Guest', 'woothemes');
	endif;
	
	// Send new ticket notification to admin
	$subject = "#" . map_ticket_to_hash($ticket_id) . ' [' . get_bloginfo('name'). '] ' . __('New Ticket', 'woothemes');
	$content = __("Hi there,\n\nA new ticket has been submitted by &ldquo;%s&rdquo;. To view this ticket click the link below:\n\n%s\n\nRegards,\n%s", 'woothemes');
	
	$email_content = sprintf(
		$content
		, $display_name
		, '<a href="'.get_permalink($ticket->ID).'">'.$ticket->post_title.'</a>'
		, get_bloginfo('name')
	);
	
	woo_supportpress_send_mail( woo_supportpress_send_to( 'ticket' ), $subject, $email_content );
}

function woo_supportpress_email_owner_of_new_ticket_a( $ticket_id ) {
	
	$ticket = get_post($ticket_id);
	
	// Only send if ticket created on users behalf
	if ($ticket->post_author == get_current_user_id()) return;
	
	$user = get_user_by('id', $ticket->post_author);
	
	// Send new ticket notification to admin
	$subject = '#' . map_ticket_to_hash($ticket->ID) .' [' . get_bloginfo('name'). '] ' . __('New Ticket', 'woothemes');
	$content = __("Hi there,\n\nA ticket has been created for your support issue. To view this ticket click the link below:\n\n%s\n\nRegards,\n%s", 'woothemes');
	
	$email_content = sprintf(
		$content
		, '<a href="'.get_permalink($ticket->ID).'">'.$ticket->post_title.'</a>'
		, get_bloginfo('name')
	);
	
	// Generate Headers
	$header['From'] 		= get_bloginfo('name') . " <noreply@".$sitename.">";
	$header['X-Mailer'] 	= "PHP" . phpversion() . "";
	$header['Content-Type'] = get_option('html_type') . "; charset=\"". get_option('blog_charset') . "\"";

	foreach ( $header as $key => $value ) {
		$headers[$key] = $key . ": " . $value;
	}
	$headers = implode("\n", $headers);
	$headers .= "\n";
	
	// Filter
	$headers = apply_filters('woo_supportpress_send_mail_headers', $headers);
	
	wp_mail( $user->user_email, $subject, $email_content, $headers );
}

function woo_supportpress_email_assigned_to_new_ticket_a( $ticket_id ) {
	
	$ticket = get_post($ticket_id);
	$auth_id = $ticket->post_author;
	$user = new WP_User($auth_id);

	if ($user->display_name !='') :
		$display_name = $user->display_name;
	else :
		$display_name = __('Guest', 'woothemes');
	endif;
	
	// Send ticket notification to assigned user
	$assigned_user = get_user_by('id', get_post_meta( $ticket->ID, '_responsible', true));
	if ($assigned_user && !is_wp_error($assigned_user) && $assigned_user->ID!=get_current_user_id()) :
	
		$subject =  '#' . map_ticket_to_hash($ticket->ID) . ' [' . get_bloginfo('name'). '] ' . __('Assigned to ticket', 'woothemes');
		$content = __("Hi there %s,\n\nIt's your lucky day! A new ticket has been submitted by &ldquo;%s&rdquo; and assigned to you. To view this ticket click the link below:\n\n%s\n\nRegards,\n%s", 'woothemes');
		
		$email_content = sprintf(
			$content
			, $assigned_user->display_name
			, $display_name
			, '<a href="'.get_permalink($ticket->ID).'">'.$ticket->post_title.'</a>'
			, get_bloginfo('name')
		);
		
		// Generate Headers
		$header['From'] 		= get_bloginfo('name') . " <noreply@".$sitename.">";
		$header['X-Mailer'] 	= "PHP" . phpversion() . "";
		$header['Content-Type'] = get_option('html_type') . "; charset=\"". get_option('blog_charset') . "\"";
	
		foreach ( $header as $key => $value ) {
			$headers[$key] = $key . ": " . $value;
		}
		$headers = implode("\n", $headers);
		$headers .= "\n";
		
		// Filter
		$headers = apply_filters('woo_supportpress_send_mail_headers', $headers);
		
		wp_mail( $assigned_user->user_email, $subject, $email_content, $headers );
	
	endif; 
}


function apply_hash($ticket_id) {
	$hash_val = uniqid();
	
	$sql = "INSERT INTO wp_postmeta VALUES ('','" . $ticket_id . "','_ticket_hash','" . $hash_val . "')";
	mysql_query($sql);
}


add_action('new_ticket', 'apply_hash', 0, 1);

add_action('new_ticket', 'woo_supportpress_email_new_ticket_a', 1, 1);
add_action('new_ticket', 'woo_supportpress_email_owner_of_new_ticket_a', 1, 1);
add_action('new_ticket', 'woo_supportpress_email_assigned_to_new_ticket_a', 1, 1);


add_action('new_message', 'woo_supportpress_email_new_message_a', 1, 1);
add_action('ticket_updated', 'woo_supportpress_email_updated_ticket_a', 1, 3);

/*-----------------------------------------------------------------------------------*/
/* Updated ticket Notice
/*-----------------------------------------------------------------------------------*/

function woo_supportpress_email_updated_ticket_a( $ticket_id, $updates = array(), $comment_id = '' ) {
	
	global $wpdb, $post;
	
	$ticket = get_post($ticket_id);
	$auth_id = $ticket->post_author;
	$user = new WP_User($auth_id);

	if ($user->display_name !='') :
		$display_name = $user->display_name;
	else :
		$display_name = __('Guest', 'woothemes');
	endif;
	
	$comment = get_comment( $comment_id ); 
	if ($comment->comment_content && $comment->comment_content!=='[UPDATE]') $comment_content = "\n\n".__('Comment', 'woothemes').":\n\n<quote>" . wptexturize(strip_tags($comment->comment_content)) . "</quote>"; else $comment_content = '';
	
	$subject = '#' . map_ticket_to_hash($ticket_id) . ' [' . get_bloginfo('name'). '] ' . __('Ticket Updated', 'woothemes');
	$content = __("Hi there,\n\nTicket #%s has been updated by &ldquo;%s&rdquo;. To view this ticket click the link below:\n\n%s\n\nTicket updates: %s%s\n\nRegards,\n%s", 'woothemes');
	
	$email_content = sprintf(
		$content
		, $ticket->ID
		, $display_name
		, '<a href="'.get_permalink($ticket->ID).'">'.$ticket->post_title.'</a>'
		, implode(', ', $updates)
		, $comment_content
		, get_bloginfo('name')
	);
	
	// Send notification to users watching the ticket
	$watchers = woo_supportpress_get_item_watchers( $ticket->ID );
	
	// Email admin if no-one is assigned
	if (!get_post_meta( $ticket->ID, '_responsible', true)) $watchers[] = get_option('admin_email');

	if (sizeof($watchers)>0) woo_supportpress_send_mail( $watchers, $subject, $email_content );
}


/*-----------------------------------------------------------------------------------*/
/* New Message Notice
/*-----------------------------------------------------------------------------------*/

function woo_supportpress_email_new_message_a( $item_id ) {
	
	$ticket = get_post($ticket_id);
	$auth_id = $ticket->post_author;
	$user = new WP_User($auth_id);

	if ($user->display_name !='') :
		$display_name = $user->display_name;
	else :
		$display_name = __('Guest', 'woothemes');
	endif;
	
	// Send new item notification to admin
	$subject = '#' . map_ticket_to_hash($item_id) . ' [' . get_bloginfo('name'). '] ' . __('New Message', 'woothemes');
	$content = __("Hi there,\n\nA new message has been submitted by &ldquo;%s&rdquo;. To view this message click the link below:\n\n%s\n\nRegards,\n%s", 'woothemes');
	
	$email_content = sprintf(
		$content
		, $display_name
		, '<a href="'.get_permalink($item->ID).'">'.$item->post_title.'</a>'
		, get_bloginfo('name')
	);
	
	woo_supportpress_send_mail( woo_supportpress_send_to( 'message' ), $subject, $email_content );
}



function woo_supportpress_email_commented_item_a( $comment_id ) {
	
	global $wpdb, $post, $ticket_updated;
	
	if ($ticket_updated) return;
	
	$comment = get_comment( $comment_id );
	$item = get_post($comment->comment_post_ID);
	
	if ($item->post_type!=='ticket' && $item->post_type!=='message') return;
	
	$comment_content = "\n\n".__('Comment', 'woothemes').":\n\n<quote>" . wptexturize(strip_tags($comment->comment_content)) . "</quote>"; 
	
	if ($comment->user_id > 0) :
		$user = new WP_User(get_current_user_id());
		$display_name = $user->display_name;
	else :
		$display_name = $comment->comment_author;
	endif;
	
	if ($item->post_type=='ticket') :
		$content = __("Hi there,\n\nTicket #%s has been commented on by &ldquo;%s&rdquo;. To view this ticket click the link below:\n\n%s%s\n\nRegards,\n%s", 'woothemes');
		
		$email_content = sprintf(
			$content
			, $item->ID
			, $display_name
			, '<a href="'.get_permalink($item->ID).'">'.$item->post_title.'</a>'
			, $comment_content
			, get_bloginfo('name')
		);
	else :
		$content = __("Hi there,\n\nA message you are watching has been commented on by &ldquo;%s&rdquo;. To view this message click the link below:\n\n%s%s\n\nRegards,\n%s", 'woothemes');
		
		$email_content = sprintf(
			$content
			, $display_name
			, '<a href="'.get_permalink($item->ID).'">'.$item->post_title.'</a>'
			, $comment_content
			, get_bloginfo('name')
		);
	endif;
	
	$subject = '#' . map_ticket_to_hash($comment->comment_post_ID) . ' [' . get_bloginfo('name'). '] ' . __('Comment on watched item', 'woothemes');
	
	// Send notification to users watching the ticket
	$watchers = woo_supportpress_get_item_watchers( $item->ID );
	
	if (sizeof($watchers)>0) woo_supportpress_send_mail( $watchers, $subject, $email_content );
}

add_action ('comment_post', 'woo_supportpress_email_commented_item_a', 2);

function custom_moderation_subject($subject, $comment_id) {

	$site_title = get_bloginfo();
	$co = get_comment($comment_id);
	return "[ #" . map_ticket_to_hash($co->comment_post_ID) . " ] - $subject";

}
