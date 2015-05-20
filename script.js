/**
 * DokuWiki Plugin TagSections (JavaScript Component) 
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author gamma
 */

(function() {
    
    var currentNamespace = JSINFO['namespace'];
    var currentButton = null;
    var init = function() {
        
        if ( typeof tagadd__loadForm === 'function' ) {
            jQuery('form.sectiontag__form').submit(function(event){
                
                currentButton = jQuery(this).find('input.sectiontag_button');
                request({availableTags: true, tagsForSection: true}, showTagSelection);
                return false;
            });
        } else {
            jQuery('form.sectiontag__form').hide();
        }
    };
    
    var showTagSelection = function(data) {
        
        data = JSON.parse(data);
        var $dialog = getDialog('open').html('');
        var $accordeon = jQuery('<div class="tagsections__accordeon"/>').appendTo($dialog);
        data.availableTags = jQuery.extend( true, data.availableTags, data.tagsForSection);
        
        jQuery.each(data.availableTags, function(namespace, entries){
            // namespaces
            
            jQuery('<h3/>').text((namespace + ' ' + 'x/x').trim() ).appendTo($accordeon);
            var $accordeonContent = jQuery('<div/>').appendTo($accordeon);
            
            jQuery.each(entries, function(tag){
                
                var tagName = namespace.length > 0 ? namespace+':'+tag : tag;
                var $element = jQuery('<input type="checkbox" class="tagsections__tag"/>').attr('name', tagName).val('1').attr('id', tagName);
                jQuery('<label/>').attr('for', tagName).text(tag).append($element).appendTo($accordeonContent);
                
                if ( typeof data.tagsForSection != 'undefined' &&
                     typeof data.tagsForSection[namespace] != 'undefined' &&
                     typeof data.tagsForSection[namespace][tag] != 'undefined' ) {
                    $element.prop( "checked", true );
                }
            });
        });
        
        $accordeon.accordion({heightStyle: 'content',collapsible:true});
    };
    
    var request = function(data, success) {
        data['call'] = 'tagsections';
        data['id'] = JSINFO['id'];
        data['ns'] = currentNamespace;
        data['range'] = currentButton.attr('range');
        return jQuery.post(DOKU_BASE + 'lib/exe/ajax.php', data, success);
    }
    
    var saveTags = function() {

        var newTags = [];
        var elements = getDialog().find(".tagsections__accordeon input:checked").toArray();
        for(var i=0;typeof(elements[i])!='undefined';newTags.push(elements[i++].getAttribute('name')));
        console.log(newTags);
        
        request({tags:newTags, saveTags:true}, function(){ getDialog('close'); window.location.reload(); });
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
})();