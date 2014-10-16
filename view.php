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
		$search_item = trim( $_POST['search_string'] );
		
		if (strlen($search_item) == 0) return NULL;
		
		/**
		 *	How to display/hilite the founded items.
		 *	For details please take a look in the languagefiles, e.g. EN.php.
		 */
		$search_item_hilite = sprintf( $MOD_JASM['search_item_hilite'], $search_item );
		
		/**
		 *	Storage for all results.
		 */
		$all_results = array();
		
		/**
		 *	First: get all visible pages
		 */
		$all_pages = array();
		$database->execute_query(
			"SELECT `page_id`,`page_title`,`menu_title`,`link` from `".TABLE_PREFIX."pages` WHERE `visibility`='public' ORDER BY `parent`,`position`",
			true,
			$all_pages
		);
		
		$pages_found = 0;
		$absolute_hits = 0;
		
		foreach($all_pages as &$page) {
		
			$page_link = LEPTON_URL.PAGES_DIRECTORY.$page['link'].".php";

			/**
			 *	Get the sections of the page
			 */
			$all_sections = array();
			$database->execute_query(
				"SELECT `section_id`,`module` FROM `".TABLE_PREFIX."sections` WHERE `page_id`='".$page['page_id']."'",
				true,
				$all_sections,
				true
			);
			
			$num_of_hits = 0;
			
			/**
			 *	Look over the sections 
			 */
			foreach($all_sections as &$current_section) {

				switch( $current_section['module'] ) {
					
					/**
					 *	WYSIWYG section
					 */
					case 'wysiwyg':
						$section_content = array();
						$database->execute_query(
							"SELECT `content`,`text`,`section_id` FROM `".TABLE_PREFIX."mod_wysiwyg` WHERE `section_id`='".$current_section['section_id']."' AND `text` LIKE '%".$search_item."%'",
							true,
							$section_content
						);
						
						if (count($section_content) == 0) {
							continue;
						} else {
							/**
							 *	Found something!
							 */
							foreach($section_content as &$result) {
							
								$num_of_hits += substr_count($result['content'], $search_item);
								
								$cont = preg_replace("/".$search_item."/i", $search_item_hilite, $result['content']);
								
								$all_results[] = array(
									'link'	=> $page_link,
									'menu_title'	=> $page['menu_title'],
									'page_title'	=> $page['page_title'],
									'section_id'	=> $result['section_id'],
									'content'		=> $cont
								);
							}
						}
						
						break;
					
					/**
					 *	News section
					 */
					case 'news':
						$section_content = array();
						$database->execute_query(
							"SELECT `title`,`content_short`,`content_long`,`post_id` FROM `".TABLE_PREFIX."mod_news_posts` WHERE `section_id`='".$current_section['section_id']."' AND `content_short` LIKE '%".$search_item."%' OR `content_long` LIKE '%".$search_item."%'",
							true,
							$section_content
						);
						
						if (count($section_content) == 0) continue;
							
						foreach($section_content as &$result) {
						
							/**
							 *	Content_short or content_long?
							 */
							$text_ref = ( false !== stripos( $result['content_short'], $search_item ) )
								? $result['content_short']
								: $result['content_long']
								;
							
							$num_of_hits += substr_count($text_ref, $search_item);
							
							$cont = preg_replace("/".$search_item."/i", $search_item_hilite, $text_ref);

							$all_results[] = array(
								'link'	=> $page_link,
								'menu_title'	=> $page['menu_title'],
								'page_title'	=> $page['page_title'],
								'section_id'	=> $current_section['section_id'],
								'content'		=> $cont
							);
						}
						#$num_of_hits++;
						break;
						
					default:
						// nothing
						
				} // end switch
			} // end forall sections
			
			if ($num_of_hits > 0) {
				$pages_found++;
				$absolute_hits += $num_of_hits;
			}
			
		} // end forall pages
		
		$display_results = array(
			"num_of_results" => $absolute_hits,
			"search_results_info"	=> sprintf($MOD_JASM['search_results_info'], $absolute_hits, $pages_found),
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