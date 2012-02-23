<?php if (!defined('TL_ROOT')) 
    die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    semantic_html5
 * @license    GNU/GPL 2
 * @filesource
 */

/**
 * Class SemanticHTML5
 */
class SemanticHTML5 extends ContentElement
{
    /**
        * Template
        * @var string
        */
    protected $strTemplate = 'ce_semantic_html5';    
    
    /**
     * Initialize the object
     * 
     * @param Database_Result $objElement 
     */
    public function __construct(Database_Result $objElement)
    {
        parent::__construct($objElement);
    }

    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        $objElement = $this->Database
                ->prepare("SELECT * FROM `tl_content` WHERE id = ?")
                ->limit(1)
                ->execute($this->id);

        if (TL_MODE == 'BE')
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = "&lt;" . (($this->sh5_tag == 'end') ? '/' : '') . $objElement->sh5_type . "&gt;";

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Generate module
     */
    protected function compile(){}

}

?>