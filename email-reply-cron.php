<?php
// since we will be asking out cron file to load directly, so lets ask wordpress header to come and help us
define('WP_USE_THEMES', false);
require('../../../wp-blog-header.php');

 if(isset($_SERVER['argv'][0]) and $_SERVER['argv'][0] == '--cron') //this will tell us whether the script is loaded via cron or not
$isCron = true;
else
$isCron = false;  

if($isCron) :


endif;
?>