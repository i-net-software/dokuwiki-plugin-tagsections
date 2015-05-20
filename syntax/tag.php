<?php
/**
 * Tag Plugin: displays list of keywords with links to categories this page
 * belongs to. The links are marked as tags for Technorati and other services
 * using tagging.
 *
 * Usage: {{tag>category tags space separated}}
 *
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Esther Brunner <wikidesign@gmail.com>
 */
 
// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

if ( class_exists('syntax_plugin_tag_tag') || 1==1 ) {

    /**
     * Tag syntax plugin, allows to specify tags in a page
     */
    class syntax_plugin_tagsections_tag extends syntax_plugin_tag_tag {
    
        /**
         * @return string Syntax type
         */
        function getType() { return 'baseonly'; }
        /**
         * @return int Sort order
         */
        function getSort() { return 300; }
        
        /**
         * @param string $mode Parser mode
         */
        function connectTo($mode) {
            $this->Lexer->addSpecialPattern('\{\{tag>.*?\}\}', $mode, 'plugin_tagsections_tag');
        }
    
        /**
         * Render xhtml output or metadata
         *
         * @param string         $mode      Renderer mode (supported modes: xhtml and metadata)
         * @param Doku_Renderer  $renderer  The renderer
         * @param array          $data      The data from the handler function
         * @return bool If rendering was successful.
         */
        function render($mode, &$renderer, $data) {

            if ($data === false) return false;
    
            // XHTML output
            if ($mode == 'xhtml') {
                
                // If we are directly after an opening Tag of a section level. This only applies if the option is enbaled.
                $secLevelRegex = '/<h([1-9])(.*?)(>.*?)(<\/h\1>\s*?)(<div class=")(level\1)(">\s*?)$/s';
                $matches = array();
                if ( preg_match($secLevelRegex, $renderer->doc, $matches) ) {
                    $tags = implode(' ', array_map(array($this, '__clean'), $data));
                    $tagList = implode('', array_map(array($this, '__tagList'), $data));
                    
                    $matches[2] = preg_replace("/(class=\")(.*?)/", "$1$tags $2", $matches[2]);
                    $renderer->doc = preg_replace($secLevelRegex, "<h$1{$matches[2]}$3$tagList$4$5$tags $6$7", $renderer->doc);
                    
                    return true;
                }
            }
            return parent::render($mode, $renderer, $data);
        }
        
        function __clean($entry) {
            return cleanID(str_replace(':', '_', $entry));
        }
        
        function __tagList($entry) {
            $entries = explode(':', $entry);
            $list = array_unique(array_merge($entries, array($this->__clean($entry))));
            
            return '<span class="tagsections header tag '.implode(' ', $list).'">'.array_pop($entries).'</span>';
        }
    }
}
// vim:ts=4:sw=4:et: 
