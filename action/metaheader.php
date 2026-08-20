<?php
/**
 * DokuWiki Plugin tagsections (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  i-net software <tools@inetsoftware.de>
 */

// must be run within DokuWiki
if (!defined('DOKU_INC')) die();

class action_plugin_tagsections_metaheader extends DokuWiki_Action_Plugin {

    /**
     * Register after the tag and keywords plugins have prepared the header.
     *
     * @param Doku_Event_Handler $controller Event controller
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'filterMetaKeywords', null, 100);
    }

    /**
     * Keep only tags from the first H1 in the page-wide meta keywords.
     * Section tags remain in subject metadata and the tag index.
     *
     * @param Doku_Event $event Meta header event
     * @param mixed      $param Event parameter (unused)
     * @return void
     */
    public function filterMetaKeywords(Doku_Event $event, $param) {
        global $ID;

        if (empty($event->data['meta']) || !is_array($event->data['meta'])) return;

        $allTags = p_get_metadata($ID, 'tagsections all_tags', METADATA_DONT_RENDER);
        if (!is_array($allTags) || empty($allTags)) return;

        $pageTags = p_get_metadata($ID, 'tagsections page_tags', METADATA_DONT_RENDER);
        if (!is_array($pageTags)) $pageTags = array();

        $sectionOnlyTags = array_diff($allTags, $pageTags);
        if (empty($sectionOnlyTags)) return;

        $subject = p_get_metadata($ID, 'subject', METADATA_DONT_RENDER);
        if (!is_array($subject) || empty($subject)) return;

        $pageSubject = array_values(array_filter($subject, function($tag) use ($sectionOnlyTags) {
            return !in_array($tag, $sectionOnlyTags, true);
        }));

        // The tag plugin beautifies underscores before this sequenced hook.
        $renderedSubject = array_map(array($this, 'beautifyTag'), $subject);
        $renderedPageSubject = array_map(array($this, 'beautifyTag'), $pageSubject);
        $subjectPrefix = implode(',', $renderedSubject);

        foreach ($event->data['meta'] as &$meta) {
            if (!isset($meta['name']) || $meta['name'] !== 'keywords') continue;
            if (!isset($meta['content'])) continue;
            if (strncmp($meta['content'], $subjectPrefix, strlen($subjectPrefix)) !== 0) return;

            $suffix = substr($meta['content'], strlen($subjectPrefix));
            $base = empty($renderedPageSubject)
                ? str_replace(':', ',', $ID)
                : implode(',', $renderedPageSubject);
            $meta['content'] = $base . $suffix;
            return;
        }
        unset($meta);
    }

    /**
     * Match the tag plugin's keyword presentation.
     *
     * @param string $tag Normalized tag
     * @return string Displayed meta keyword
     */
    private function beautifyTag($tag) {
        return str_replace('_', ' ', $tag);
    }
}

// vim:ts=4:sw=4:et:
