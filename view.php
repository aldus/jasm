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

/**	*************************
 *	Load module language file
 */
global $MOD_JASM;
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
		global $search_item;
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
			"SELECT `page_id`,`page_title`,`menu_title`,`link`,`description` from `".TABLE_PREFIX."pages` WHERE `visibility`='public' ORDER BY `parent`,`position`",
			true,
			$all_pages
		);
		
		$pages_found = 0;
		$absolute_hits = 0;
		
		require_once LEPTON_PATH."/modules/droplets/droplets.php";
		
		/**
		 *	Get the section "out of time" query part
		 */
		$now = time();
		$section_timetest_query = " AND ( (".$now." <= `publ_end` OR (`publ_end` = 0)) AND ( (".$now." >= `publ_start`) OR (`publ_start` = 0)))";
		
		
		foreach($all_pages as &$page) {
		
			$page_link = LEPTON_URL.PAGES_DIRECTORY.$page['link'].".php";

			/**
			 *	Page description?
			 */
			if($page["description"] != "")
			{
				$temp_list_result = array();
				if( preg_match_all('/('.$search_item.')/Ui', $page["description"], $temp_list_result, PREG_SET_ORDER ))
				{
					// found something inside the pagedesc
					$all_results[] = array(
						'link'	=> $page_link,
						'menu_title'	=> $page['menu_title'],
						'page_title'	=> $page['page_title'],
						'section_id'	=> $page['page_id'],
						'content'		=> "META (page) description: ".str_replace ($search_item, sprintf( $MOD_JASM['search_item_hilite'], $search_item),  $page["description"])
					);
			
					$num_of_hits++;
				}
			}
			
			/**
			 *	Get the sections of the page
			 */
			$all_page_sections = array();
			$database->execute_query(
				"SELECT `section_id`,`module` FROM `".TABLE_PREFIX."sections` WHERE `page_id`='".$page['page_id']."'".$section_timetest_query ,
				true,
				$all_page_sections,
				true
			);
			
			$num_of_hits = 0;
			
			/**
			 *	Look over the sections 
			 */
			foreach($all_page_sections as &$current_section) {

				switch( $current_section['module'] ) {
					
					/**
					 *	WYSIWYG section
					 */
					case 'wysiwyg':
					
						$section_content = array();
						$database->execute_query(
							"SELECT `content`,`text`,`section_id` FROM `".TABLE_PREFIX."mod_wysiwyg` WHERE `section_id`='".$current_section['section_id']."'",
							true,
							$section_content,
							false
						);

						if (count($section_content) == 0) {
							// continue;
						} else {
							
							processDroplets( $section_content['content'] );
							
							/**
							 *	Try to find something!
							 */
							$temp_list_result = array();

							
							if( preg_match_all('/('.$search_item.')/Ui', $section_content['content'], $temp_list_result, PREG_SET_ORDER ))
							{
								// found
								$num_of_hits += count($temp_list_result); // substr_count($section_content['content'], $search_item);
								
								$cont = preg_replace_callback(
									'/(<img.*src=.*'.$search_item.'[^>]*\/>)|('.$search_item.')/is',
										function ($treffer){
											global $MOD_JASM;
											global $search_item;
											// echo LEPTON_tools::display( $treffer, "code", "ui message");
											return $treffer[1] == ""
												? sprintf( $MOD_JASM['search_item_hilite'], $treffer[0])
												: $treffer[0].sprintf($MOD_JASM['found_inside_filename'], sprintf( $MOD_JASM['search_item_hilite'], $search_item ) )
												;
										},
									$section_content['content']
								);
								
								// content 2
								$s = explode ("%s", str_replace("/", "\/", $MOD_JASM['search_item_hilite']));
								$a = array();
								$cont = preg_match_all("/(.{0,30}".$s[0].".*".$s[1].".{0,30})/i", $cont, $a);
								//echo LEPTON_tools::display( $a, "div", "ui message red");
								$s2 = "";
								foreach($a[1] as $t) $s2 .= " ...".$t." - ";
								$cont = $s2;
								
								$all_results[] = array(
									'link'	=> $page_link,
									'menu_title'	=> $page['menu_title'],
									'page_title'	=> $page['page_title'],
									'section_id'	=> "sec: ".$section_content['section_id'],
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
							"SELECT `title`,`content_short`,`content_long`,`post_id` FROM `".TABLE_PREFIX."mod_news_posts` WHERE `section_id`='".$current_section['section_id']."'",
							true,
							$section_content
						);
						
						if (count($section_content) == 0) continue;
							
						foreach($section_content as &$result) {
						
							/**
							 *	Content_short or content_long?
							 */
							$text_ref = $result['content_long'];
							
							processDroplets( $text_ref );
							
							/**
							 *	Try to find something!
							 */
							$temp_list_result = array();
	
							if( preg_match_all('/('.$search_item.')/Ui', $text_ref, $temp_list_result, PREG_SET_ORDER ))
							{
								// found
								$num_of_hits += count($temp_list_result); // substr_count($section_content['content'], $search_item);
								
								$cont = preg_replace_callback(
									'/(<img.*src=.*'.$search_item.'[^>]*\/>)|('.$search_item.')/is',
										function ($treffer){
											global $MOD_JASM;
											global $search_item;
											// echo LEPTON_tools::display( $treffer, "code", "ui message");
											return $treffer[1] == ""
												? sprintf( $MOD_JASM['search_item_hilite'], $treffer[0])
												: $treffer[0].sprintf($MOD_JASM['found_inside_filename'], sprintf( $MOD_JASM['search_item_hilite'], $search_item ) )
												;
										},
									$text_ref
								);
							
								$all_results[] = array(
									'link'	=> $page_link,
									'menu_title'	=> $page['menu_title'],
									'page_title'	=> $page['page_title'],
									'section_id'	=> $current_section['section_id'],
									'content'		=> $cont
								);
							}
						}
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
		
		// echo LEPTON_tools::display( $all_results, "pre", "ui message red");
		
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