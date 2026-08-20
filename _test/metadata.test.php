<?php

/**
 * Tests page-wide and section-specific tag metadata.
 */
class plugin_tagsections_metadata_test extends DokuWikiTest {

    public function setUp() : void {
        $this->pluginsEnabled[] = 'tag';
        $this->pluginsEnabled[] = 'tagsections';
        $this->pluginsEnabled[] = 'keywords';
        parent::setUp();
    }

    public function testFirstH1TagRemainsPageKeyword() {
        $id = 'tagsections:first_h1';
        $this->saveAndRenderMetadata($id, "====== Page ======\n\n{{tag>product:pdfc}}");

        self::assertSame(array('product:pdfc'), p_get_metadata($id, 'subject', METADATA_DONT_RENDER));
        self::assertSame(array('product:pdfc'), p_get_metadata($id, 'tagsections page_tags', METADATA_DONT_RENDER));
        self::assertSame('product:pdfc', $this->filterKeywords($id));
    }

    public function testSectionTagIsNotPageKeyword() {
        $id = 'tagsections:section';
        $source = "====== Page ======\n\n===== Requirements =====\n\n==== Concurrent users and scaling ====\n\n{{tag>product:not:pdfc.standalone}}";
        $this->saveAndRenderMetadata($id, $source);

        self::assertSame(array('product:not:pdfc.standalone'), p_get_metadata($id, 'subject', METADATA_DONT_RENDER));
        self::assertNull(p_get_metadata($id, 'tagsections page_tags', METADATA_DONT_RENDER));
        self::assertSame('tagsections,section', $this->filterKeywords($id));
        self::assertStringContainsString('product_not_pdfc.standalone', p_wiki_xhtml($id));
    }

    public function testFirstHeadingMustBeH1() {
        $id = 'tagsections:first_h2';
        $source = "===== Section without page heading =====\n\n{{tag>product:pdfc}}";
        $this->saveAndRenderMetadata($id, $source);

        self::assertNull(p_get_metadata($id, 'tagsections page_tags', METADATA_DONT_RENDER));
        self::assertSame('tagsections,first_h2', $this->filterKeywords($id));
    }

    public function testTagAtLaterH1IsNotPageKeyword() {
        $id = 'tagsections:later_h1';
        $source = "====== First ======\n\nText\n\n====== Second ======\n\n{{tag>product:reporting}}";
        $this->saveAndRenderMetadata($id, $source);

        self::assertNull(p_get_metadata($id, 'tagsections page_tags', METADATA_DONT_RENDER));
        self::assertSame('tagsections,later_h1', $this->filterKeywords($id));
    }

    public function testPageTagAlsoUsedBySectionRemainsPageKeyword() {
        $id = 'tagsections:duplicate';
        $source = "====== Page ======\n\n{{tag>product:pdfc}}\n\n===== Section =====\n\n{{tag>product:pdfc option:advanced}}";
        $this->saveAndRenderMetadata($id, $source);

        self::assertSame(
            array('product:pdfc', 'option:advanced'),
            array_values(p_get_metadata($id, 'subject', METADATA_DONT_RENDER))
        );
        self::assertSame('product:pdfc', $this->filterKeywords($id));
    }

    public function testExplicitKeywordsArePreserved() {
        $id = 'tagsections:explicit';
        $source = "{{keywords>system requirements, scaling}}\n\n====== Page ======\n\n===== Section =====\n\n{{tag>product:not:pdfc.standalone}}";
        $this->saveAndRenderMetadata($id, $source);

        self::assertSame(
            'tagsections,explicit, system requirements, scaling',
            $this->filterKeywords($id, p_get_metadata($id, 'keywords', METADATA_DONT_RENDER))
        );
    }

    /**
     * @param string $id Page ID
     * @param string $source Wiki source
     * @return void
     */
    private function saveAndRenderMetadata($id, $source) {
        saveWikiText($id, $source, 'Test');
        p_get_metadata($id, '', METADATA_RENDER_UNLIMITED);
    }

    /**
     * Run the action component against the same keyword value DokuWiki creates.
     *
     * @param string $id Page ID
     * @param string $keywordSuffix Optional keywords-plugin suffix
     * @return string Filtered content
     */
    private function filterKeywords($id, $keywordSuffix = '') {
        global $ID;
        $ID = $id;

        $subject = p_get_metadata($id, 'subject', METADATA_DONT_RENDER);
        $content = implode(',', array_map(function($tag) {
            return str_replace('_', ' ', $tag);
        }, $subject));
        $content .= $keywordSuffix;

        $eventData = array('meta' => array(array('name' => 'keywords', 'content' => $content)));
        $event = new Doku_Event('TPL_METAHEADER_OUTPUT', $eventData);
        $plugin = plugin_load('action', 'tagsections_metaheader');
        $plugin->filterMetaKeywords($event, null);

        return $event->data['meta'][0]['content'];
    }
}
