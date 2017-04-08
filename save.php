<?php

/**
 *  @module         jasm
 *  @version        see info.php of this module
 *  @authors        Dietrich Roland Pehlke
 *  @copyright      2017 The LEPTON-CMS development team - Dietrich Roland Pehlke
 *  @license        GNU General Public License
 *  @license terms  see info.php of this module
 *  @platform       see info.php of this module
 *
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('LEPTON_PATH')) {	
	include(LEPTON_PATH.'/framework/class.secure.php'); 
} else {
	$oneback = "../";
	$root = $oneback;
	$level = 1;
	while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
		$root .= $oneback;
		$level += 1;
	}
	if (file_exists($root.'/framework/class.secure.php')) { 
		include($root.'/framework/class.secure.php'); 
	} else {
		trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
	}
}
// end include class.secure.php
/**
 *	Include WB admin wrapper script
 *
 */
$update_when_modified = true; // Tells script to update when this page was last updated
require(LEPTON_PATH.'/modules/admin.php');


	/** 
	 *	Check if there is a database error, otherwise say successful
	 *
	 */
	if ( true === $database->is_error() ) {
		$admin->print_error($database->get_error(), $js_back, true );
	} else {
		$admin->print_success($MESSAGE['PAGES']['SAVED'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id);
	}

/**
 *	Print admin footer
 *
 */
$admin->print_footer();

