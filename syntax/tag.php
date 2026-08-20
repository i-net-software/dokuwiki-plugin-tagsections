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

if ( class_exists('syntax_plugin_tag_tag') ) {

    /**
     * Tag syntax plugin, allows to specify tags in a page
     */
    class syntax_plugin_tagsections_tag extends syntax_plugin_tag_tag {

        /** @var helper_plugin_tag */
        protected $Htag;
    
        function __construct() {
            if (plugin_isdisabled('tag') || (!$this->Htag = plugin_load('helper', 'tag'))) {
                msg('tag plugin is required by tagsections plugin, but missing', -1);
                return false;
            }
        }

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
         * Parse tags and remember whether they belong to the page heading.
         *
         * A tag controls the whole page only when it follows the first heading
         * and that heading is an H1. All other tags belong to their section.
         *
         * @param string       $match   Matched syntax
         * @param int          $state   Lexer state
         * @param int          $pos     Byte position in the source
         * @param Doku_Handler $handler Parser handler
         * @return array|false Data for the renderer
         */
        function handle($match, $state, $pos, Doku_Handler $handler) {
            $tags = parent::handle($match, $state, $pos, $handler);
            if ($tags === false) return false;

            $headingCount = 0;
            $lastHeadingLevel = null;
            foreach ($handler->calls as $call) {
                if ($call[0] !== 'header') continue;
                $headingCount++;
                $lastHeadingLevel = $call[1][1];
            }

            return array(
                'tags' => $tags,
                'pageTag' => $headingCount === 1 && $lastHeadingLevel === 1,
            );
        }
    
        /**
         * Render xhtml output or metadata
         *
         * @param string         $mode      Renderer mode (supported modes: xhtml and metadata)
         * @param Doku_Renderer  $renderer  The renderer
         * @param array          $data      The data from the handler function
         * @return bool If rendering was successful.
         */
        function render($mode, Doku_Renderer $renderer, $data) {

            if ($data === false) return false;

            // Keep compatibility with parser instructions cached before this
            // plugin started recording the heading association.
            $pageTag = isset($data['pageTag']) && $data['pageTag'] === true;
            $tags = isset($data['tags']) ? $data['tags'] : $data;
    
            // XHTML output
            if ($mode == 'xhtml') {
                
                // If we are directly after an opening Tag of a section level. This only applies if the option is enabled.
                $secLevelRegex = '/<h([1-9])(.*?)(>.*?)(<\/h\1>\s*?)(<div class=")(level\1)(">\s*?)/s';
                $matches = array();
                
                if ( preg_match_all($secLevelRegex, $renderer->doc, $matches, PREG_SET_ORDER) ) {
                    
                    $matches = array_pop($matches);
                    $levelTags = $tagClasses = implode(' ', array_map(array($this, '__tags'), $tags));
                    $tagList = implode('', array_map(array($this, '__tagList'), $tags));
                    
                    $matches[2] = preg_replace("/(class=\")(.*?)/", "$1$tagClasses $2", $matches[2]);
                    
                    if ( !$this->getConf('addTagsToSectionElements') ) {
                        $levelTags = '';    
                    }

                    $renderer->doc = str_replace($matches[0], "<h{$matches[1]}{$matches[2]}{$matches[3]}$tagList{$matches[4]}{$matches[5]}$levelTags {$matches[6]}{$matches[7]}", $renderer->doc);
                    
                    return true;
                }
            }

            if ($mode == 'metadata') {
                $rendered = parent::render($mode, $renderer, $tags);
                if (!$rendered) return false;

                $cleanTags = array_map('cleanID', $tags);
                if (!isset($renderer->meta['tagsections']['all_tags'])) {
                    $renderer->meta['tagsections']['all_tags'] = array();
                }
                $renderer->meta['tagsections']['all_tags'] = array_values(array_unique(array_merge(
                    $renderer->meta['tagsections']['all_tags'],
                    $cleanTags
                )));

                if ($pageTag) {
                    if (!isset($renderer->meta['tagsections']['page_tags'])) {
                        $renderer->meta['tagsections']['page_tags'] = array();
                    }
                    $renderer->meta['tagsections']['page_tags'] = array_values(array_unique(array_merge(
                        $renderer->meta['tagsections']['page_tags'],
                        $cleanTags
                    )));
                }

                return true;
            }
            
            return parent::render($mode, $renderer, $tags);
        }
        
        function __clean($entry) {
            return cleanID(str_replace(':', '_', $entry));
        }
        
        function __tags($entry) {
            $entries = explode(':', $entry);
            return implode(' ', array_unique(array_merge($entries, array($this->__clean($entry)))));
        }
        
        function __tagList($entry) {
            
            $entries = explode(':', $entry);
            $list = array_unique(array_merge($entries, array($this->__clean($entry))));
            
            if ( ($my = $this->loadHelper('tag')) && $this->getConf('useTagLinks')) {
                return '<span class="tagsections header tag '.implode(' ', $list).'">'.$my->tagLink($entry, array_pop($entries)).'</span>';
            } else {
                
                $name = array_pop($entries);
                $format = $this->getConf('alternateLinkFormat');
                if ( !empty($format) ) {
                    $format = str_replace(array( "{TAG}", "{NAME}", "{RAW}" ), array( urlencode(cleanID($entry)), urlencode(cleanID($name)), cleanID($entry) ), $format);
                    $link = tpl_link($format, $name, 'rel="tag" title="'.$entry.'"', true);
                } else {
                    $link = $name;
                }
                
                return '<span class="tagsections header tag '.implode(' ', $list).'">'.$link.'</span>';
            }
        }
    }
}
// vim:ts=4:sw=4:et: 
