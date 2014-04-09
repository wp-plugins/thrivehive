<?php
//todo: get this to be configurable based on passed in array of inputs

function formGenerator($formId, $accountId){
    $env = get_option('th_environment');
    if($env == false){
        $env = "my.thrivehive.com";
    }
	return '<style> 
form.hiveform { 
    color: #000000; 
    text-align: left; 
    font-size: 1em; 
    font-family: inherit; 
    width: 350px; 
} 
form.hiveform label { 
    display:inline-block; 
    float: left; 
    clear: both; 
    margin-bottom: 5px; 
    width: 150px; 
} 
form.hiveform span.required-star { 
    color: red; 
} 
form.hiveform input[type=text], form.hiveform textarea { 
    display: inline-block; 
    float: right; 
    width: 195px; 
    margin-bottom: 5px; 
} 
form.hiveform textarea { 
    resize: vertical; 
    height: 3.5em; 
} 
form.hiveform input.hivesubmit { 
    color: white;     
    background-color: #28738f; 
    font-size: 1em; 
    font-weight: bold; 
    cursor: pointer; 
    padding: .5em .8em; 
    border-radius: 5px; 
    -moz-border-radius: 5px; 
    -webkit-border-radius: 5px; 
    -o-border-radius: 5px; 
    border: none; 
    display: block; 
    float: right; 
    clear: both; 
} 
.error-block {     
    min-height: 1.6em; 
    clear: both; 
    color: #E10707; 
} 
@media only screen and (max-width : 600px) { 
    form.hiveform input[type=text], form.hiveform textarea,  form.hiveform select {                 
        clear: both; 
        display: block; 
        float: none;  
        width: 220px; 
    } 
   form.hiveform input.hivesubmit {             
        clear: both; 
        float: none; 
        margin-top: 10px; 
    } 
} 
</style> 
 
<form class="hiveform" action="//'.$env.'/Webform/FormHandler" method="post" id="thrivehive-contactform"> 
      <label for="input1-contactform">First Name<span class="required-star">*</span></label><input type="text" name="list.first_name" id="input1-contactform" /> 
    <div id="input1-contactform-errors" class="error-block"></div> 
      <label for="input2-contactform">Last Name</label><input type="text" name="list.last_name" id="input2-contactform" /> 
    <div id="input2-contactform-errors" class="error-block"></div> 
      <label for="input3-contactform">Phone</label><input type="text" name="list.phone" id="input3-contactform" /> 
    <div id="input3-contactform-errors" class="error-block"></div> 
      <label for="input4-contactform">Email<span class="required-star">*</span></label><input type="text" name="list.email" id="input4-contactform" /> 
    <div id="input4-contactform-errors" class="error-block"></div>

    <label for="input5-contactform">Comments</label>
    <textarea name="list.comments" id="input5-contactform"></textarea>
    
    <input type="hidden" name="meta.redirectUrl" id="meta_redirectUrl" value="/thank-you" /> 
    <input type="submit" value="Submit" class="hivesubmit"/>
    <div style="clear: both;"></div>
</form> 
 
<script type="text/javascript"> 
    var domreadyScriptUrl = (("https:" == document.location.protocol) ? "https://" : "http://") + "'.$env.'/content/js/domready.js"; 
    document.write(unescape("%3Cscript src%3D%27" + domreadyScriptUrl + "%27 type%3D\'text/javascript\'%3E%3C/script%3E")); 
    var validateScriptUrl = (("https:" == document.location.protocol) ? "https://" : "http://") + "'.$env.'/content/js/validate.min.js"; 
    document.write(unescape("%3Cscript src%3D%27" + validateScriptUrl + "%27 type%3D\'text/javascript\'%3E%3C/script%3E")); 
</script> 
<script type="text/javascript"> 
    DomReady.ready(function () { 
        $util.SetFormHiddenID("CA-uid","thrivehive-contactform"); 
        $util.SetFormSessionID("CA-sess","thrivehive-contactform"); 
        $util.AddHiddenFieldInForm("meta.form-id","thrivehive-contactform","' . $formId . '"); 
        $util.AddHiddenFieldInForm("meta.trackerid","thrivehive-contactform","' . $accountId . '"); 
    
            new FormValidator("thrivehive-contactform", [{ 
                name: "input1-contactform", 
                display: "First Name", 
                rules: "required" 
            }], function (errors) { 
                var errorString = ""; 
                if (errors.length > 0) { 
                    for (var i = 0, errorLength = errors.length; i < errorLength; i++) { 
                        errorString += errors[i].message + "<br />"; 
                    } 
                } 
                document.getElementById("input1-contactform-errors").innerHTML = errorString; 
            }) 
     
            new FormValidator("thrivehive-contactform", [{ 
                name: "input3-contactform", 
                display: "Phone", 
                rules: "valid_phone_us" 
            }], function (errors) { 
                var errorString = ""; 
                if (errors.length > 0) { 
                    for (var i = 0, errorLength = errors.length; i < errorLength; i++) { 
                        errorString += errors[i].message + "<br />"; 
                    } 
                } 
                document.getElementById("input3-contactform-errors").innerHTML = errorString; 
            }) 
     
            new FormValidator("thrivehive-contactform", [{ 
                name: "input4-contactform", 
                display: "Email", 
                rules: "required|valid_email" 
            }], function (errors) { 
                var errorString = ""; 
                if (errors.length > 0) { 
                    for (var i = 0, errorLength = errors.length; i < errorLength; i++) { 
                        errorString += errors[i].message + "<br />"; 
                    } 
                } 
                document.getElementById("input4-contactform-errors").innerHTML = errorString; 
            }) 
     
    }); 
</script>';
}