/**
 * DokuWiki Plugin TagSections (JavaScript Component) 
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author gamma
 */

(function(jQuery) {
    
    var currentNamespace = JSINFO['namespace'];
    var $currentButton = null;
    var init = function() {
        
        jQuery('form.sectiontag__form').submit(function(event){
            
            $currentButton = jQuery(this);
            request({availableTags: true, tagsForSection: true}, showTagSelection);
            return false;

        // Move the Tag button up to the header. Or close to it.
        }).parent().css('margin-top', function(){
            
            var $tgt = jQuery(this),
                nr = $tgt.attr('class').match(/(\s+|^)editbutton_(\d+)(\s+|$)/)[2],
                $highlight = jQuery();
            
            // Walk the dom tree in reverse to find the sibling which is or contains the section edit marker
            while($tgt.length > 0 && !($tgt.hasClass('sectionedit' + nr) || $tgt.find('.sectionedit' + nr).length)) {
                $tgt = $tgt.prev();
                $highlight = $highlight.add($tgt);
            }
            // insert the section highlight wrapper before the last element added to $highlight
            return ($tgt.offset()||{top:0}).top - ($highlight.filter(':last').offset()||{top:0}).top;
        });
    };
    
    var showTagSelection = function(data) {
        
        data = JSON.parse(data);
        var $dialog = getDialog('open').html('');
        var $accordeon = jQuery('<div class="tagsections__accordeon"/>').appendTo($dialog);
        data.availableTags = jQuery.extend( true, data.availableTags, data.tagsForSection);
        
        if ( typeof data.availableTags[''] == 'undefined' ) {
            data.availableTags[''] = {};
        }
        
        var needsEmptySection = true;
        jQuery.each(data.availableTags, function(namespace, entries){
            // namespaces
            
            var $accordeonContent = jQuery('<div/>');
            
            needsEmptySection = false;
            var checked = 0;
            jQuery.each(entries, function(tag){
                
                var check =     typeof data.tagsForSection != 'undefined' &&
                                typeof data.tagsForSection[namespace] != 'undefined' &&
                                typeof data.tagsForSection[namespace][tag] != 'undefined';
                creeateCheckBox(namespace, tag, check).appendTo($accordeonContent);
                checked += check ? 1 : 0;
            });
            
            // Add an input box to add new tags
            additionalRows(namespace, $accordeonContent);
            
            // Add new accordeon entry
            $accordeon.append(createHeader(namespace, checked, Object.keys(entries).length));
            $accordeonContent.appendTo($accordeon);
        });
        
        if ( needsEmptySection ) {
            $accordeon.append(createHeader(null, 0, 0));

            var $content = jQuery('<div/>').appendTo($accordeon);
            additionalRows(null, $content);
        }
        
        $accordeon.accordion({heightStyle: 'content',collapsible:true});
    };
    
    var createHeader = function(namespace, checked, entries) {
        return jQuery('<h3/>').text(((namespace||LANG.plugins.tagsections['empty namespace']) + ' ' + checked + '/'+entries).trim() );
    };
    
    var creeateCheckBox = function(namespace, tag, checked) {
        var tagName = (namespace||'').length > 0 ? namespace+':'+tag : tag;
        var $element = jQuery('<input type="checkbox" class="tagsections__tag"/>').attr('name', tagName).val('1').attr('id', tagName).prop('checked', checked);
        return jQuery('<label/>').attr('for', tagName).text(tag).append($element);
    };
    
    var additionalRows = function(namespace, $root) {
        
        var $newTagLine = jQuery('<hr/>').appendTo($root);
        var $element = jQuery('<input class="tagsections__tag__new"/>').attr('id', namespace + '_newTag');
        var $button  = jQuery('<input class="edit" type="submit"/>').val(LANG.plugins.tagsections['add']);
        var $form    = jQuery('<form/>').append($element).append($button);
        jQuery('<label class="newTag"/>').attr('for', namespace + '_newTag').text(LANG.plugins.tagsections['new tag']).append($form).appendTo($root);
        
        $form.submit(function(){
            
            var tag = $element.val();
            $newTagLine.before(creeateCheckBox(namespace, tag, true));
            $element.val('');

            return false;
        });
    };
    
    var request = function(data, success) {
        data['call'] = 'tagsections';
        data['id'] = JSINFO['id'];
        data['ns'] = currentNamespace;
        data['range'] = $currentButton.find('input.sectiontag_button').attr('range');
        return jQuery.post(DOKU_BASE + 'lib/exe/ajax.php', data, success);
    };
    
    var saveTags = function() {

        var newTags = [];
        var elements = getDialog().find(".tagsections__accordeon input:checked").toArray();
        for(var i=0;typeof(elements[i])!='undefined';newTags.push(elements[i++].getAttribute('name')));
        
        request({tags:newTags, saveTags:true}, function(){
            request({contentOfPage:true}, function(data){
    
                var $toRemove = $currentButton.parent().parent().children(),
                $tmpWrap = jQuery('<div style="display:none"></div>').html(data);  // temporary wrapper
                
                // insert the section highlight wrapper before the last element added to $tmpStore
                $toRemove.filter(':last').before($tmpWrap);
                // and remove the elements
                $toRemove.detach();
                
                // Now remove the content again
                $tmpWrap.before($tmpWrap.children().detach());
                // ...and remove the section highlight wrapper
                $tmpWrap.detach();
    
                // Close Dialog.
                getDialog('close').detach();
                // Re-Init the page for edit buttons.
                initJQuery();
            });
        });
    };
    
    var initJQuery = function() {
        
        jQuery('script[src]').each(function(){
            var $xchange = jQuery(this);
            var $new = jQuery('<script/>').attr('type', $xchange.attr('type')).attr('defer', 'true');
            $xchange.before($new).detach();
            $new.attr('src', $xchange.attr('src'));
        });
    };

    var getDialog = function(action) {
        
        if(!jQuery('#tagsections__dilaog').length){
            jQuery('body').append('<div id="tagsections__dilaog" position="absolute" border=1 height="800px"><div id="tagsections__dialog_div"></div></div>');
            jQuery( "#tagsections__dilaog" ).dialog({title:LANG.plugins.tagsections['choose tags'],
                height:600,
                width: Math.min(700,jQuery(window).width()-50),
                autoOpen:true,
                buttons:[
                    {text:LANG.plugins.tagsections['closeDialog'],click: function() { getDialog('close') }},
                    {text:LANG.plugins.tagsections['save'],click: saveTags},
                    ],
                });
        }
        
        if ( action ) {
            return jQuery('#tagsections__dilaog').dialog(action);
        }
        
        return jQuery('#tagsections__dilaog');
    };    
   
    jQuery(document).ready(init);
})(jQuery);
