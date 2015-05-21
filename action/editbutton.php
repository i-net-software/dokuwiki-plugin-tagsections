<?php
/**
 * DokuWiki Plugin sectiontag (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  i-net software <tools@inetsoftware.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_tagsections_editbutton extends DokuWiki_Action_Plugin {

    private $inited = null;

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
       $controller->register_hook('HTML_SECEDIT_BUTTON', 'AFTER', $this, 'handle_html_secedit_button');
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */

    public function handle_html_secedit_button(Doku_Event &$event, $param) {

        // Check if already inited
        if ( !$this->init() ) {
            return;
        }
        
        // Check for correct section
        if ( $event->data['target'] != 'section' ) {
            return;
        }
        
        // Add form for tags
        $form = new Doku_Form(array('class' => 'sectiontag__form btn_secedit'));
        $form->addElement(form_makeButton('submit', 'sectiontag', 'add tag', array( 'range' => $event->data['range'], 'class' => 'sectiontag_button' ) ));
        $event->result .= '<div class="sectiontag secedit editbutton_'.$event->data['secid'].'">' . $form->getForm() . '</div>';
    }

    private function init() {
        
        if ( is_null( $inited ) ) {
            $this->inited = (plugin_load('action', 'tag' ) != null);
        }
        
        return $this->inited;
    }
}

// vim:ts=4:sw=4:et:
