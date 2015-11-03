Crconnect = Class.create();
Crconnect.prototype = {
    initialize: function() {

    
    },
    confirmMainKey: function(manual) {
        
        var key = $("crroot_crconnect_api_key").value;
        
        if (manual){
            $$(".simple-added-row").each(function(el){
                callRowDelete(el.id);
                savedDefaultListId = false;
                savedDefaultFormId = false;
            });
        }
        
        if (key){
            var url = baseConfirmKeyUrl + 'key/' + key;
            new Ajax.Request(url, {
                method: 'post',
                onSuccess: function(transport) {

                    var select = $('crroot_crconnect_list_id');
                    Crconnect.cleanSelect('crroot_crconnect_list_id');
                    
                    // If no results come, revert all group and form selects to default states
                    if (transport.responseText == "empty"){ 
                        Crconnect.resetGroupList();
                        Crconnect.resetFormList();
                        // Reload block with keys 
                        Crconnect.reloadKeysBlock();
                        Crconnect.confirmDisable();
                        return false;
                    }
                    
                    // Fill Main groups select with groups list
                    var editData = transport.responseText.evalJSON(true);
                    Crconnect.reloadKeysBlock(editData);
                    select.insert(new Element('option', {value: ''}).update(crconnectLang['please:select:group']));
                    for (var i = 0; i < editData.length; i++){
                        select.insert(new Element('option', {value: editData[i].id}).update(editData[i].name));
                    }
                    
                    if (savedDefaultListId){
                        select.value = savedDefaultListId;
                        savedDefaultListId = false;
                    }
                    Crconnect.changeGroupId();
                    
                    
                }
            });
        }else{
            Crconnect.resetGroupList();
        }
        Crconnect.confirmDisable();

    },
    reloadKeysBlock: function(editData){
      
        var select = '<select class="crconnect-groups-select" onchange="Crconnect.changeSubGroup(this)" id="#{_id}_crconnect" name="groups[crconnect][fields][groups_keys][value][#{_id}][crconnect]">';
        select += '<option value="">'+crconnectLang['please:select:group']+'</option>';
        if (editData){
            for (var i = 0; i < editData.length; i++){
                select += '<option value="'+editData[i].id+'">'+editData[i].name+'</option>';
            }
        }
        stringElements['crconnect'] = select + '</select>';  
        initRowTemplate();
    
    },
    changeGroupId: function(){
        
        var groupId = $("crroot_crconnect_list_id").value;
        var key = $("crroot_crconnect_api_key").value;
        
        if (key && groupId){
            var url = baseChangeGroupUrl + 'group/' + groupId + '/key/' + key;
            new Ajax.Request(url, {
                method: 'post',
                onSuccess: function(transport) {
                    
                    var select = $('crroot_crconnect_form_id');
                    
                    Crconnect.cleanSelect('crroot_crconnect_form_id');
                    if (transport.responseText == "empty"){                        
                        Crconnect.resetFormList();
                        return false;
                    }
                    var editData = transport.responseText.evalJSON(true);
                    select.insert(new Element('option', {value: ''}).update(crconnectLang['please:select:form']));
                    for (var i = 0; i < editData.length; i++){
                        select.insert(new Element('option', {value: editData[i].id}).update(editData[i].name));
                    }
                    
                    if (savedDefaultFormId){
                        select.value = savedDefaultFormId;
                        savedDefaultFormId = false;
                    }
                    
                }
            });
        }else{
            Crconnect.resetFormList();
        }
    },
    changeSubGroup: function(element){
      
        var selectedValue = element.value;
        var id = element.id;
        id = id.replace("_crconnect", "");
        
        var key = $("crroot_crconnect_api_key").value;
        var url = baseChangeGroupUrl + 'group/' + selectedValue + '/key/' + key;
        
        new Ajax.Request(url, {
            method: 'post',
            onSuccess: function(transport) {

                var select = $(id+'_formid');

                Crconnect.cleanSelect(id+'_formid');
                if (transport.responseText == "empty"){                        
                    Crconnect.resetFormList(id+'_formid');
                    return false;
                }
                var editData = transport.responseText.evalJSON(true);
                select.insert(new Element('option', {value: ''}).update(crconnectLang['please:select:form']));
                for (var i = 0; i < editData.length; i++){
                    select.insert(new Element('option', {value: editData[i].id}).update(editData[i].name));
                }

                if (savedFormsKeys){
                    
                    if (savedFormsKeys[id]){
                        select.value = savedFormsKeys[id];
                    }
                    
                }    
//                if (savedDefaultFormId){
//                    select.value = savedDefaultFormId;
//                    savedDefaultFormId = false;
//                }

            }
        });
            
//        console.log(id);
      
        
    },
    confirmEnable: function(){
        
        $("confirm-key-button").removeClassName('disabled');
        $("confirm-key-button").disabled = false;
        
    },
    confirmDisable: function(){
        
        $("confirm-key-button").addClassName('disabled');
        $("confirm-key-button").disabled = true;
        
    },
    resetFormList: function(formId){
        
        if (!formId){
            formId = 'crroot_crconnect_form_id';
        }
        Crconnect.cleanSelect(formId);
        $(formId).insert(new Element('option', {value: ''}).update(crconnectLang['please:select:form:default']));
        
    },
    resetGroupList: function(formId){
        
        if (!formId){
            formId = 'crroot_crconnect_list_id';
        }
        
        Crconnect.cleanSelect(formId);
        $(formId).insert(new Element('option', {value: ''}).update(crconnectLang['please:select:group:default']));
        
    },
    cleanSelect: function (selectId){

        var options = $$('select#'+selectId+' option');
        var select = $(selectId);
        for (var i = 0; i < options.length; i++) {
            options[i].remove();
        }
        
    },
    fillSelectedGroups: function(){
        
        if (typeof editedSelects != "undefined"){
            if (editedSelects.length > 0){
                for (var i = 0; i < editedSelects.length; i++){
                    Crconnect.changeSubGroup($(editedSelects[i]+"_crconnect"));
                }
            }
        }
        
    }

}
Crconnect = new Crconnect();

function initCleverReach(){
    
    try{
        Crconnect.confirmMainKey();
        Event.observe('crroot_crconnect_list_id', 'change', Crconnect.changeGroupId);
        Event.observe('crroot_crconnect_api_key', 'keyup', Crconnect.confirmEnable);
        Crconnect.fillSelectedGroups();
    }catch(e){
        
    }

}
Event.observe(window, 'load', initCleverReach);