<?php
/**
 * DokuWiki Plugin tagsections (Helper Component) 
 * Based up on the tagfilter helper component
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  lisps
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");

require_once(DOKU_INC.'inc/indexer.php');

class helper_plugin_tagsections extends DokuWiki_Plugin {
	
	protected $Htag;
	/**
	* Constructor gets default preferences and language strings
	*/
	function __construct() {
		if (plugin_isdisabled('tag') || (!$this->Htag = plugin_load('helper', 'tag'))) {
			msg('tag plugin is missing', -1);
			return false;
		}
	}
	
	function getMethods() {
		$result = array();
		$result[] = array(
	                'name'   => 'getTagsByRegExp',
	                'desc'   => 'returns tags for given Regular Expression',
					'params' => array(
			                    'tags (required)' => 'string',
			                    'namespace (optional)' => 'string',),
	                'return' => array('tags' => 'array'),
		);
		$result[] = array(
	                'name'   => 'getTagsByNamespace',
	                'desc'   => 'returns tags for given namespace',
					'params' => array(
			                    'namespace' => 'string',),
	                'return' => array('tags' => 'array'),
		);
		$result[] = array(
	                'name'   => 'getTagsBySiteID',
	                'desc'   => 'returns tags for given siteID',
					'params' => array(
			                    'siteID' => 'string',),
	                'return' => array('tags' => 'array'),
		);

		return $result;
	}
	
	/**
     * Search index for tags using preg_match
     * @param tags
     * @param $ns
     * return tags
     */
	function getTagsByRegExp($tag_expr = null, $ns = '',$acl_safe = false){
		$Htag = $this->Htag;
		if(!$Htag) return false;
		$tags = array_map('trim', idx_getIndex('subject','_w'));
		$tag_label_r = array();
		foreach($tags  as  $tag){
			if( (is_null($tag_expr) || @preg_match('/^'.$tag_expr.'$/i',$tag)) && $this->_checkTagInNamespace($tag,$ns,$acl_safe)){
				//$label =stristr($tag,':');
				$label = strrchr($tag,':');
				$label = $label !=''?$label:$tag;
				$tag_label_r[$tag] = ucwords(trim(str_replace('_',' ',trim($label,':'))));
			}
		}
		asort($tag_label_r);
		return $tag_label_r;
	}
	
	/*
	 * Return all tags for a defined namespace
	 * @param namespace
	 * @param acl_safe
	 * @return tags for namespace
	 */
	function getTagsByNamespace($ns = '',$acl_safe = true){
        return array_keys($this->getTagsByRegExp(null, $ns, $acl_safe));
	}
	
	/*
	 * Return all tags for a defined site
	 * @param siteID
	 * @return tags for site
	 */
	function getTagsBySiteID($siteID){
		$meta = p_get_metadata($siteID,'subject');
		if($meta === NULL) $meta=array();
		return $meta;
	}
	
	function _tagCompare ($tag1,$tag2){
		return $tag1==$tag2;
	}
	private function _checkTagInNamespace($tag,$ns,$acl_safe=true){
		$Htag = $this->Htag;
		if(!$Htag) return false;
		if($ns == '') return true;
		$indexer = idx_get_indexer();
		$pages = $indexer->lookupKey('subject', $tag, array($this, '_tagCompare'));
		foreach($page_r as $page){
			if($Htag->_isVisible($page,$ns)) {
				if (!$acl_safe) return true;
				$perm = auth_quickaclcheck($page);
				if (!$perm < AUTH_READ)
					return true;
			
			}

		}
		return false;
    }

    
    /**
     * Categorysize Tags by the first part before a ':'
     * @param array $tags Array of tags
     * <pre> 
     * array('category1:tag1','category1:tag2','category2:tag1','category2:tag2')
     * </pre>
     * @returns array multidimensional array
     * <pre> 
     * [category1] => 'category1:tag1'
     *             => 'category1:tag2'
     * [category2] => 'category2:tag1'
     *             => 'category2:tag2'
     * </pre>
     */
    public function categorysizeTags($tags)
    {
        $catTags = array();
        if ( empty($tags) ) return array();
        foreach($tags as $nsTag){
            list($category, $tag) = explode(':', $nsTag, 2);
            if ( empty($tag) ) {
                $tag = $category;
                $category = '';
            }
            
            $catTags[$category][$tag]++;
        }
        ksort($catTags);
        return $catTags;
    }
}
