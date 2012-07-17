#!/usr/bin/php -q
<?php
/*********************************************************************
    pipe.php

    Converts piped emails to ticket. Both local and remote!

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

@chdir(realpath(dirname(__FILE__)).'/'); //Change dir.
ini_set('memory_limit', '256M'); //The concern here is having enough mem for emails with attachments.
require_once('../../../../wp-blog-header.php');
require_once('api.inc.php');


//Get the input
$data=isset($_SERVER['HTTP_HOST'])?file_get_contents('php://input'):file_get_contents('php://stdin');
if(empty($data)){
    api_exit(EX_NOINPUT,'No data');
}

//Parse the email.
error_log('tester');
$emailreply->do_parse($data);

?>
