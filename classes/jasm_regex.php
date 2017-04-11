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


class jasm_regex
{

	/**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;
    
    // public $lookup = "/(%s)/Ui";
    
	/**
	 *	Return the »internal«
	 *
	 */
	public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        
        return static::$instance;
    }
    
}


