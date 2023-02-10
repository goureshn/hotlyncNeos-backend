var agent_status = '';
function loadjsfile(filename){
    var jsElm = document.createElement("script");
    jsElm.type = "application/javascript";
    jsElm.src = filename;
    document.body.appendChild(jsElm);
}

function unloadjsfile(filename, filetype){
    var targetelement=(filetype=="js")? "script" : (filetype=="css")? "link" : "none" //determine element type to create nodelist from
    var targetattr=(filetype=="js")? "src" : (filetype=="css")? "href" : "none" //determine corresponding attribute to test for
    var allsuspects=document.getElementsByTagName(targetelement)
    for (var i=allsuspects.length; i>=0; i--){ //search backwards within nodelist for matching elements to remove
        if (allsuspects[i] && allsuspects[i].getAttribute(targetattr)!=null && allsuspects[i].getAttribute(targetattr).indexOf(filename)!=-1)
            allsuspects[i].parentNode.removeChild(allsuspects[i]) //remove element by calling parentNode.removeChild()
    }
}

function clickExtension(property_id, first_name, status, extension){
    if (status == 'Available') {
        //////////////gold code////
        agent_status = 'Available';
        var data = [];
        data.push({name: "property_id", value: property_id},
            {name: 'extension', value: extension});
        data = jQuery.param(data);
        $.ajax({
            type: "POST",
            url: "/call/getconfig",
            data: data,
            async: true,
            dataType: "json",
            error: function (jqXHR, textStatus, errorThrown) {
                alert(errorThrown);
            },
            success: function (response) {
                user.WSServer = response.ws_url;
                user.Realm = response.sip_ip;
                user.Pass = response.sip_pass;
                user.User = extension;
                user.Display = first_name;
                localStorage.setItem('SIPCreds', JSON.stringify(user));
                loadjsfile('js/sip/app.js');
            }
        });
        ///////////////
        //etssip.phoneCallButtonPressed();
    }
    if(status == 'Log out') {
        agent_status = 'Log out';
        if(typeof etssip != 'undefined' && etssip != null) {
            localStorage.removeItem('etsPhone');
            etssip.phone.stop();
            etssip.stopRingTone();
            etssip.stopRingbackTone();
            etssip.setCallSessionStatus('Rejected');
            etssip.callActiveID = null;
        }
        user = {};
        localStorage.clear();
        unloadjsfile('app.js','js');
    }

}