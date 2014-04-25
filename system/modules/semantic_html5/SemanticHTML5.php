<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013
 * @package    semantic_html5
 * @license    GNU/LGPL
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
     * @param $objElement
     */
    public function __construct($objElement)
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

        $strAdditional = '';
        if($this->sh5_additional)
        {
            foreach(deserialize($this->sh5_additional) as $arrAdditional) {
                if($arrAdditional['property'])
                {
                    $strAdditional .= ' ' . $arrAdditional['property'] . ((strlen($arrAdditional['value'])>0) ? '="' . specialchars($arrAdditional['value']) . '"' : '');
                }
            }
        }
        $this->sh5_additional = $strAdditional;

        if (TL_MODE == 'BE')
        {
            $objTemplate           = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = vsprintf("&lt;%s%s%s%s&gt;", array(
                (($this->sh5_tag == 'end') ? '/' : '') . $objElement->sh5_type,
                (($this->sh5_tag == 'start' && strlen($this->cssID[0])) ? ' id="' . $this->cssID[0] . '"' : ''),
                (($this->sh5_tag == 'start' && strlen($this->cssID[1])) ? ' class="' . $this->cssID[1] . '"' : ''),
                $this->sh5_additional
            ));

            $strReturn = $objTemplate->parse();

            // Add script to toggle wrong wrapper class in backend
            $strReturn .= (($this->sh5_tag == 'end' && version_compare(VERSION, 3, '>=')) ? '<script>
                if(document.getElementById("li_' . $this->id . '")) {
                    var elem = document.getElementById("li_' . $this->id . '").firstElementChild;
                    elem.className = elem.className.replace("wrapper_start", "wrapper_stop");
                }
            </script>' : '');

            // Add script to remove all indent classes as quick workaround
            $strReturn .= ((version_compare(VERSION, 3, '>=')) ? '<script>
                var el = document.getElementById("li_' . $this->id . '");
                if(el) {
                    var elem = el.firstElementChild;
                    elem.className = elem.className.replace(" indent ", "");
                }
            </script>' : '');

            return $strReturn;
        }

        return parent::generate();
    }

    /**
     * Generate module
     */
    protected function compile(){}

}

?>