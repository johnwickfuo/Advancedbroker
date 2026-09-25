<?php
$gtContext=country();
$gtTarget=match(strtolower($gtContext->languageCode)){
    'fil'=>'tl',
    'zh'=>'zh-TW',
    default=>strtolower($gtContext->languageCode),
};
$gtCountryId=$gtContext->id();
?>
<div class="gtranslate_wrapper notranslate" translate="no" aria-label="Language selector"></div>
<script>
(function(){
  var target=<?= json_encode($gtTarget,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;
  var countryId=<?= json_encode((string)$gtCountryId) ?>;
  var desired="/en/"+target;

  function getCookie(name){
    var prefix=name+"=";
    var parts=document.cookie.split(";");
    for(var i=0;i<parts.length;i++){
      var part=parts[i].trim();
      if(part.indexOf(prefix)===0){
        var value=part.substring(prefix.length);
        try{return decodeURIComponent(value);}catch(e){return value;}
      }
    }
    return "";
  }

  function setTranslateCookie(language){
    var value="/en/"+language;
    /* GTranslate expects the raw /source/target cookie value. */
    document.cookie="googtrans="+value+"; path=/; SameSite=Lax";
  }

  function fireHtmlEvent(element,eventName){
    try{
      var event=document.createEvent("HTMLEvents");
      event.initEvent(eventName,true,true);
      element.dispatchEvent(event);
    }catch(e){}
  }

  function activateHiddenTranslator(){
    var combo=document.querySelector("select.goog-te-combo");
    if(!combo)return false;

    if(combo.value!==target)combo.value=target;
    fireHtmlEvent(combo,"change");
    fireHtmlEvent(combo,"change");
    return true;
  }

  var previousCountry=null;
  try{previousCountry=localStorage.getItem("apex_gtranslate_country");}catch(e){}

  var current=getCookie("googtrans");
  var shouldAutoApply=previousCountry!==countryId || current!==desired;

  if(shouldAutoApply){
    setTranslateCookie(target);
    try{localStorage.setItem("apex_gtranslate_country",countryId);}catch(e){}
  }

  window.apexGTranslateAutoApply=shouldAutoApply;
  window.apexGTranslateTarget=target;
  window.apexActivateGTranslate=activateHiddenTranslator;
})();

window.gtranslateSettings={
  default_language:"en",
  languages:["en","de","fr","it","es","nl","sv","no","da","ja","ko","zh-TW","hi","ar","pl","tl","pt"],
  wrapper_selector:".gtranslate_wrapper"
};
</script>
<script src="https://cdn.gtranslate.net/widgets/latest/dropdown.js" defer></script>
<script>
(function(){
  if(!window.apexGTranslateAutoApply)return;

  var attempts=0;
  function apply(){
    attempts++;

    if(typeof window.apexActivateGTranslate==="function" && window.apexActivateGTranslate()){
      return;
    }

    /*
     * The current dropdown widget lazy-loads its translation library after
     * interaction. Nudge the visible selector so that library is initialised,
     * then retry the hidden translator selector.
     */
    var visible=document.querySelector(".gtranslate_wrapper select");
    if(visible){
      try{
        visible.dispatchEvent(new MouseEvent("mouseover",{bubbles:true}));
        visible.dispatchEvent(new Event("focus",{bubbles:true}));
      }catch(e){}
    }

    if(attempts<60)setTimeout(apply,200);
  }

  if(document.readyState==="loading"){
    document.addEventListener("DOMContentLoaded",function(){setTimeout(apply,100);},{once:true});
  }else{
    setTimeout(apply,100);
  }
})();
</script>
