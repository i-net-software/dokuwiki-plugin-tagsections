<?php
/**
 * DokuWiki Plugin ajax (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  i-net software <tools@inetsoftware.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_tagsections_ajax extends DokuWiki_Action_Plugin {

    private $inited = null;

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
       $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call');
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */

    public function handle_ajax_call(Doku_Event &$event, $param) {
    
        global $INPUT, $ID, $INFO, $ACT;
    
        if ( $event->data != 'tagsections' ) return false;
        
        if ((!$filter = $this->loadHelper('tagsections'))) return false;
        $event->preventDefault();

        $result = array();        
        $ID = getID();
        $range = $INPUT->str('range');
        $ns = $INPUT->str('ns');

        if ( $INPUT->has('contentOfPage') ) {
            $ACT = 'show';
            $INFO = pageinfo();
            return tpl_content();
        }

        if ( $INPUT->has('saveTags') ) {
            return $this->__saveTags($INPUT->arr('tags'), $range);
        }

        if ( $INPUT->has('listOfPages') ) {
            $result['listOfPages'] = $this->__namespace_tree($ns);
        }
        
        
        if ( $INPUT->has('availableTags') ) {
            // Lets just use all tags for now.
            $availableTags = $filter->getTagsByNamespace('');
            $result['availableTags'] = $filter->categorysizeTags($availableTags);
        }

        if ( $INPUT->has('tagsForSection') ) {
            $tagsForSection = $this->__getTagsForSection($filter, $range);
            $result['tagsForSection'] = $filter->categorysizeTags($tagsForSection);
        }
        
        echo json_encode($result);
    }
    
    private function __getTagsForSection($filter, $RANGE) {
        global  $ID;
        
        if ($RANGE) {
            list($PRE,$TEXT,$SUF) = rawWikiSlices($RANGE,$ID);
            
            // Render for tags
            $instructions = p_get_instructions($TEXT);
            $renderer = new Doku_Renderer_metadata();
            
            // loop through the instructions
            foreach ($instructions as $instruction){
                // execute the callback against the renderer
                call_user_func_array(array(&$renderer, $instruction[0]), (array) $instruction[1]);
            }
            
            // Return subject Tags
            return $renderer->meta['subject'];
 
        } else {
            return $filter->getTagsBySiteID($ID);
        }        
    }
    
    private function __namespace_tree($ns) {
        global $conf;
        
        
        $ns_dir  = utf8_encodeFN(str_replace(':','/',$ns));
    
        $data = array();
        search($data,$conf['datadir'],'search_index',array('ns' => $ns_dir));
    
        // insert the current ns into the hierarchy if it isn't already part of it
        $ns_parts = explode(':', $ns);
        $tmp_ns = '';
        $pos = 0;
        foreach ($ns_parts as $level => $part) {
            if ($tmp_ns) $tmp_ns .= ':'.$part;
            else $tmp_ns = $part;
    
            // find the namespace parts or insert them
            while ($data[$pos]['id'] != $tmp_ns) {
                if ($pos >= count($data) || ($data[$pos]['level'] <= $level+1 && strnatcmp(utf8_encodeFN($data[$pos]['id']), utf8_encodeFN($tmp_ns)) > 0)) {
                    array_splice($data, $pos, 0, array(array('level' => $level+1, 'id' => $tmp_ns, 'open' => 'true')));
                    break;
                }
                ++$pos;
            }
        }
    
        // return $data;
        return html_buildlist($data,'idx','media_nstree_item','media_nstree_li');
    }
    
    private function __saveTags($tags, $RANGE) {
        global $ID, $PRE, $TEXT, $SUF;
        
        list($PRE,$TEXT,$SUF) = rawWikiSlices($RANGE,$ID);
        
        $newTags = '';
        if ( is_array($tags) && !empty($tags) ) {
            $newTags = "\n\n".'{{tag>' . implode(' ', $tags) . '}}';
        }

        $reg = '/([ \n\t]*{{tag>.*?}})/s';
        if ( preg_match($reg, $TEXT) ) {
            // Put tags in TEXT
            $TEXT = preg_replace($reg, $newTags, $TEXT);
        } else {
            
            $reg = '/([ \t]*={2,}[^\n]+={2,}[ \t]*(?=\n))/';
            if ( $this->getConf('tagsAfterHeading') == 1 && preg_match($reg, $TEXT) ) {
                // Not yet there. At the beginning.
                $TEXT = preg_replace($reg, "$1" . $newTags, $TEXT);
            } else {
                // Not yet there. Add at the end.
                $TEXT .= $newTags;
            }
        }

        //save it
        saveWikiText($ID,con($PRE,$TEXT,$SUF,true),'Update tags using tagsections in range ' . $range, true); //use pretty mode for con
        //unlock it
        unlock($ID);
    }
}

// vim:ts=4:sw=4:et:
