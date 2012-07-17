<?php


class SupportObject {

//global uploads;

private $_to;
private	$_delivered_to;
private	$_from;
private	$_subject;
private	$_text;
private	$_html;
private	$_attachments;
private	$_attachment_denied;

private	$_ticket_id;
private	$_owner;
private $_files;


	function __construct()
	{
	$_files = array();
	$_attachments = array();
	} // function

	function init(){
	
	}

	function loadObject($Parser){
		$this->_to = $Parser->getHeader('to');
		$this->_delivered_to = $Parser->getHeader('delivered-to');
		$this->_from = $this->getOwnerEmail($Parser->getHeader('from'));
		$this->_owner = $this->setOwnerID($this->_from);
		//echo "OWNER:".$this->_from;
		$this->_subject = $Parser->getHeader('subject');
		$this->_text = $Parser->getMessageBody('text');
		$this->_html = $Parser->getMessageBody('html');
		$this->_attachments = $Parser->getAttachments();
		$this->_ticket_id = $this->getTicketID();
		echo 'tixid:' . $this->_ticket_id;
		$this->parseAttachments();
		return true;
	}
	
	function saveObject() {
	
	
		$posted = array();

	if($this->_owner > 0 && ($this->_ticket_id == 0 || $this->_ticket_id < 0)) {

	$posted['title'] = mb_decode_mimeheader($this->getSubject());
	$posted['comment'] = mb_decode_mimeheader($this->getText());
	$posted['ticket_type'] = DEFAULT_TICKET_TYPE;
	$posted['priority'] = DEFAULT_EMAIL_PRI;
	$posted['responsible'] = DEFAULT_RESPONSIBILITY;
	$posted['status'] = DEFAULT_TICKET_STATUS;
	$posted['ticket_owner'] = $this->_owner;
	$posted['tags'] = DEFAULT_EMAIL_TAGS ;

	if( function_exists(woo_supportpress_process_new_ticket) ) {
		$return = $this->woo_supportpress_process_new_ticket_pipe($posted,$_attachment_files);
	}
	if ( is_wp_error($return) )
   			echo '<div class="notice red delete"><span><p>'.$return->get_error_message().'</p></span></div>';//}
	
	$_ticket_number = $return;
	
	if($return > 0)
		$_is_new_ticket_inserted =true;
	else
		$_is_new_ticket_inserted = false;
	
	return $_is_new_ticket_inserted;
	
	}
	
	else if($this->_owner > 0)
	//Is comment
	{
	
			$time = current_time('mysql');

			$tick_mapped = $this->map_hash_to_ticket($this->_ticket_id);
			
			if($tick_mapped >0 ) {

			$comment_data = array(
    			'comment_post_ID' =>$tick_mapped, 
			    'comment_author' => $this->getOwner()->user_login,
			    'comment_author_email' => $this->getOwner()->user_email,
			    'comment_author_url' => '',
			    'comment_content' => $this->getText(),
			    'comment_type' => '',
			    'comment_parent' => 0,
			    'user_id' => 1,
			    'comment_author_IP' => '127.0.0.1',
			    'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
			    'comment_date' => $time,
			    'comment_approved' => 1
			);
	
	
	
			$comment_id = wp_insert_comment($comment_data);
			do_action('comment_post', $comment_id, $commentdata['comment_approved']);

	}
			$this->add_attachment_meta($this->getFiles(),$uploads,$this->_ticket_id,$this->_owner);

			if($comment_id > 0)
				return true;
			else
				return false;
	
	}

	}
	
	function getTo()
	{
		return $this->_to;
	}
	function getDeliveredTo()
	{
		return $this->_delivered_to;
	}

	function getFrom()
	{
		return $this->_from;
	}
	function getSubject()
	{
		return $this->_subject;
	}
	
	function getText()
	{
		return $this->_text;
	}
	function getHtml()
	{
		return $this->_html;
	}
	
	function getFiles()
	{
		if(isset($this->_files))
		return $this->_files;
		else
		return array();
	}
	
	function parseAttachments()
	{
	
	$i=0;
$files=array();

global $uploads;
	$uploads = wp_upload_dir();
foreach($this->_attachments as $attachment) {

	$tmp_name = rand(1, 500);

  // get the attachment name
  $filename =  mb_decode_mimeheader($attachment->getFilename());
  
    $filen=$this->sanitize($tmp_name .'_'. $filename .'.'. end(explode(".", $filename)), true);
  $abs_file_path = $uploads['path'].'/' . $filen;
  
  // write the file to the directory you want to save it in
  if ($fp = fopen($abs_file_path, 'w')) {
    while($bytes = $attachment->read()) {
      fwrite($fp, $bytes);
    }
    fclose($fp);
  }
  
  $files[$i] = array();

$files[$i]['filename'] = $filen;
$files[$i]['name'] = $abs_file_path;
$files[$i]['type'] =  $attachment->getContentType();
$files[$i]['tmpname'] =  $filen;
$files[$i]['error'] =  '';
$files[$i]['size'] =  $byte_sz;

  $i++;

}

$this->_files = $files;

	
	}

function ifFilesAttached(){

if(isset($_attachments))
return true;
else
return false;
}

function getOwner()
	{
		$o = get_userdata($this->getOwnerID());
		return $o;
	}


	function getOwnerID()
	{
			return $this->_owner;
	}

	function getOwnerEmail($email)
	{
	
	//if($_owner >0)
	if ($email != '')
	{	
		$emails=  $this->extract_emails_from($email);
		
		return $emails[0];	
	}
	else 
	
	return -1;
	
	
	}

	//Modifier
	
	function checkOwnerID($user_id)
	{	
    global $wpdb;

    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = '$user_ID'"));

    if($count == 1){ return true; }else{ return false; }
	}
	
	function setOwnerID($email) {
		
	global $wpdb;
$from_addr = $email;
$wp_user_id = $wpdb->get_var("SELECT id FROM wp_users where user_email='" . $from_addr . "'");

//echo'yag:'.$wp_user_id;

return $wp_user_id;

	
	}
	
	function getTicketID() {
	//echo "test";
	$ticket_num = $this->detect_ticket_number($this->_subject);
	//if($ticket_num >0 && $this->is_real_post($ticket_num))
	//	{
		return $ticket_num;
	//}else
	//	return -1;
	
	}
	
	function detect_ticket_number($subject) {

$ticket_number = -1;
 /*preg_match_all("/\d+/", $subject, $text_nums);
$ticket_number = $text_nums[0][0];
*/

$rightofhash = explode('#',$subject);
$leftside = explode(' ',$rightofhash[1]);

$ticket_number =$leftside[0];

if($ticket_number =='')
	$ticket_number = -1;
	
	return $ticket_number;
	
	}
	
		function get_user_id_by_email($from) {
//todo: verify email formatting

}
	 
	 
	 
function woo_supportpress_process_new_ticket_pipe($posted,$files) {
	global $wpdb;
	global $uploads;
	$errors = new WP_Error();

	
	/* Validate Requried Fields */
	if (empty($posted['title'])) $errors->add('required-field', __('<strong>Error</strong> &ldquo;Title&rdquo; is a required field.', 'woothemes'));
	if (empty($posted['comment'])) $errors->add('required-field', __('<strong>Error</strong> Please describe the problem.', 'woothemes'));

	if (sizeof($errors->errors)>0) return $errors;

	/* Author */
	if ($posted['ticket_owner']>0) $post_author = $posted['ticket_owner']; else $post_author = get_current_user_id();
	
	/* Create ticket */
	
	$data = array(
		'post_content' => $wpdb->escape($posted['comment']),
		'post_title' => $wpdb->escape($posted['title']),
		'post_status' => 'publish',
		'post_author' => $post_author,
		'post_type' => 'ticket'
	);		
		
	$ticket_id = wp_insert_post($data);	
	
		
	$this->_ticket_id = $ticket_id;
	if ($ticket_id==0 || is_wp_error($ticket_id)) wp_die( __('Error: Unable to create ticket.', 'woothemes') );

	/* Set terms */

	$terms = array();
	if ($posted['priority']) $terms[] = get_term_by( 'id', $posted['priority'], 'ticket_priority')->slug;
	if (sizeof($terms)>0) wp_set_object_terms($ticket_id, $terms, 'ticket_priority');
	
	wp_set_object_terms($ticket_id, array(NEW_STATUS_SLUG), 'ticket_status');
	
	/* Type */
	
	$terms = array();
	if ($posted['ticket_type']) $terms[] = get_term_by( 'id', $posted['ticket_type'], 'ticket_type')->slug;
	if (sizeof($terms)>0) wp_set_object_terms($ticket_id, $terms, 'ticket_type');
	
	/* Responsible */
	
	if ($posted['responsible'] && $posted['responsible']>0) :
		update_post_meta($ticket_id, '_responsible', $posted['responsible']);
	else :
		update_post_meta($ticket_id, '_responsible', '');
	endif;
	
	/* Status */
	
	$terms = array();
	if ($posted['status']) $terms[] = get_term_by( 'id', $posted['status'], 'ticket_status')->slug;
	if (sizeof($terms)>0) wp_set_object_terms($ticket_id, $terms, 'ticket_status');
	
	/* Tags */
	
	if (isset($posted['tags']) && $posted['tags']) :
				
		$tags = explode(',', trim(stripslashes($posted['tags'])));
		$tags = array_map('strtolower', $tags);
		$tags = array_map('trim', $tags);

		if (sizeof($tags)>0) :
			wp_set_object_terms($ticket_id, $tags, 'ticket_tags');
		endif;
		
	endif;
	
	/* Attach file to ticket */

	$this->add_attachment_meta($this->getFiles(),$uploads,$ticket_id,$this->_owner);

	do_action('new_ticket', $ticket_id);
		/* Attach file to ticket */

	/* Successful, return ticket */
	return $ticket_id;
	
	
	
}	

 function extract_emails_from($string){
 $matches = array();
  preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $string, $matches);
  return $matches[0];
}

	function sanitize($string = '', $is_filename = FALSE)
{
 // Replace all weird characters with dashes
 $string = preg_replace('/[^\w\-'. ($is_filename ? '~_\.' : ''). ']+/u', '-', $string);

 // Only allow one dash separator at a time (and make string lowercase)
 return mb_strtolower(preg_replace('/--+/u', '-', $string), 'UTF-8');
}
	


function add_attachment_meta($files_loaded,$uploads,$parent,$owner) {
	$opt = get_option('email_reply_options');
	global $uploads;
	if ($files_loaded) :
		
	if(isset($opt['allow_attachments'])) {
		
	foreach ($files_loaded as $attachment){
		$attachment_data = array(
			'post_mime_type' => $attachment['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($attachment['filename'])),
			'post_content' => '',
			'post_status' => 'inherit',
			'post_author' => $owner
		);

		//remove starting slash from upload path
		$uploads_relative = substr($uploads['subdir'] .'/'. $attachment['filename'], 1);
		$attachment_id = wp_insert_attachment( $attachment_data, $uploads_relative, $parent );
		$attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $attachment['name'] );
		wp_update_attachment_metadata( $attachment_id,  $attachment_metadata );
	
	}
	$this->_attachment_denied = false;
	}
	else
	{
		$this->_attachment_denied = true;
	
	}
	endif;
	}
	
	
 function mail_created_success()
{

	$to = $this->getFrom();
	//wp_mail($to, 'Ticket Successfully Created #' . $this->getTicketID(), '<p>Thank you for contacting us. This is an automated response confirming the receipt of your ticket. One of our agents will get back to you as soon as possible. For your records, the details of the ticket are listed below. When replying, please make sure that the ticket ID is kept in the subject line to ensure that your replies are tracked appropriately.</p>');


}

 function mail_registration_needed()
{

	$to = $this->getFrom();
	wp_mail($to, 'Registration Required', '<p>I am Sorry.You must register at <a href="http://supportpress.aveight.com/wp-login.php?action=register">http://supportpress.aveight.com</a> to submit a trouble ticket</p>');

}

	


function mail_attachments_not_allowed() {

	$user_info = get_userdata($this->_owner);
	wp_mail($user_info->user_email, 'Attachments are not allowed at this time','<p>I am sorry, Email attachments are not currently supported. Please visit your ticket at ' . post_permalink($this->_ticket_id) . ' to send a file to the support team</p>');



}

 function is_real_post($ticket_num) {

	global $wpdb;
	$sql ="SELECT ID FROM wp_posts WHERE ID='" . $ticket_num ."'";
	$num = $wpdb->get_var($sql);

	if($num == $ticket_num)
		return true;
	else
		return false;
		
}

function map_hash_to_ticket($hash) {
	global $wpdb;
	$hash_stripped = mysql_real_escape_string($hash);
	$sql = "SELECT post_id FROM wp_postmeta WHERE meta_key = '_ticket_hash' and meta_value = '" . $hash_stripped . "'";
	error_log($sql . "TICKET:" . $hash);
	
$post_id = $wpdb->get_var($sql);
	if($post_id != 0)
		return $post_id;
	else
		return false;
}
function map_ticket_to_hash($ticket_id) {

$ticket_id = get_post_meta($ticket_id, '_ticket_hash');

if($ticket_id != '' || $ticket != '0')
return $ticket_id;
else
return false;

}


function isRegNeeded()
{
if($this->_owner ==0 || $this->_owner < 0) {
	return true;
}
else
{
	return false;
}
}

function unauthorizedAttachments()
{
if(isset($this->_attachments) && $this->_attachment_denied ==true) {
	return true;
}
else
{
	return false;
}
}


//class
}


?>
