<?php

/**
 *  @module         jasm
 *  @version        see info.php of this module
 *  @authors        Dietrich Roland Pehlke
 *  @copyright      2014 The LEPTON-CMS development team - Dietrich Roland Pehlke
 *  @license        GNU General Public License
 *  @license terms  see info.php of this module
 *  @platform       see info.php of this module
 *
 */

/**	*************************
 *	Load module language file
 */
$lang = (dirname(__FILE__)) . '/languages/' . LANGUAGE . '.php';
require_once(!file_exists($lang) ? (dirname(__FILE__)) . '/languages/EN.php' : $lang );

/**	*******************************
 *	Try to get the template-engine.
 */
global $parser, $loader;
if (!isset($parser))
{
	require_once( LEPTON_PATH."/modules/lib_twig/library.php" );
}
$loader->prependPath( dirname(__FILE__)."/templates/", "jasm" );

require_once (LEPTON_PATH."/modules/lib_twig/classes/class.twig_utilities.php");

$twig_util = new twig_utilities( $parser, $loader, 
	dirname(__FILE__)."/templates/", 
	LEPTON_PATH."/templates/".DEFAULT_TEMPLATE."/frontend/jasm/"
);

$twig_util->template_namespace = "jasm";

/**	**********************************
 *	The search-form itself at the top.
 */

$search_form_values = array(
	'form_action' => LEPTON_URL.PAGES_DIRECTORY.$wb->page['link'].".php",
	'page_id'	=> $page_id,
	'section_id'	=> $section_id,
	'request_time'	=> TIME(),
	'submit'	=> $MOD_JASM['submit']
);

$twig_util->resolve_path("search_form.lte");

echo $parser->render(
	"@jasm/search_form.lte",
	$search_form_values
);

/**	*********************
 *	Any search-results to display?
 *
 */
if (isset($_POST['job'])) {
	if ($_POST['job'] == "display_results") {
		
		/**
		 *	Here we go ...
		 */
		$search_item = trim($_POST['search_string']);
		
		if (strlen($search_item) == 0) return NULL;
		
		$q = "SELECT `section_id`,`page_id`,`content`,`text` FROM `".TABLE_PREFIX."mod_wysiwyg` where `content` LIKE '%".$search_item."%'";
		$all_finds = array();
		$database->execute_query(
			$q,
			true,
			$all_finds,
			true
		);
		
		$all_results = array();
			
		foreach($all_finds as &$result) {
			
			$page_info = array();
			$database->execute_query(
				"SELECT `page_title`,`link`,`menu_title` FROM `".TABLE_PREFIX."pages` WHERE `page_id`='".$result['page_id']."'",
				true,
				$page_info,
				false
			);
			
			$replace = sprintf( $MOD_JASM['search_item_hilite'], $search_item );
			$cont = str_replace( $search_item, $replace, $result['text'] );
			
			$link = LEPTON_URL.PAGES_DIRECTORY.$page_info['link'].".php";
			
			#echo "<p><a href='".$link."'>".$page_info['menu_title']."</a></p><p>".$cont."</p>";
			
			$all_results[] = array(
				'link'	=> $link,
				'menu_title'	=> $page_info['menu_title'],
				'page_title'	=> $page_info['page_title'],
				'content'		=> $cont
			);
		}
		
		$display_results = array(
			"no_results" => count($all_finds) == 0 ? 1 : 0,
			"no_results_msg" => sprintf($MOD_JASM['no_results_msg'], $search_item) ,
			"search_results_head" => sprintf($MOD_JASM['search_results_head'], $search_item),
			'all_results' => $all_results
		);
		
		$twig_util->resolve_path("display_search_results.lte");

		echo $parser->render(
			"@jasm/display_search_results.lte",
			$display_results
		);

	}
}
?>