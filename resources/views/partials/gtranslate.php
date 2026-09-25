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
window.gtranslateSettings={
  default_language:"en",
  languages:["en","de","fr","it","es","nl","sv","no","da","ja","ko","zh-TW","hi","ar","pl","tl","pt"],
  wrapper_selector:".gtranslate_wrapper"
};
window.apexGTranslateTarget=<?= json_encode($gtTarget,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;
window.apexGTranslateCountry=<?= json_encode((string)$gtCountryId) ?>;
</script>
<script src="https://cdn.gtranslate.net/widgets/latest/dropdown.js" defer></script>
<script>
(function(){
  function marker(){
    try{return localStorage.getItem("apex_gtranslate_country");}catch(e){return null;}
  }
  function saveMarker(){
    try{localStorage.setItem("apex_gtranslate_country",window.apexGTranslateCountry);}catch(e){}
  }
  function switchLanguage(){
    if(marker()===window.apexGTranslateCountry)return;

    var target=window.apexGTranslateTarget||"en";
    var wrapper=document.querySelector(".gtranslate_wrapper");
    var select=wrapper?wrapper.querySelector("select"):null;

    if(select){
      var wanted=null;
      for(var i=0;i<select.options.length;i++){
        var value=String(select.options[i].value||"");
        if(value===target||value==="en|"+target||value.endsWith("|"+target)){
          wanted=value;
          break;
        }
      }
      if(wanted!==null){
        select.value=wanted;
        select.dispatchEvent(new Event("change",{bubbles:true}));
        saveMarker();
        return true;
      }
    }

    if(typeof window.doGTranslate==="function"){
      window.doGTranslate("en|"+target);
      saveMarker();
      return true;
    }

    return false;
  }

  var attempts=0;
  function trySwitch(){
    if(switchLanguage())return;
    attempts++;
    if(attempts<40)setTimeout(trySwitch,250);
  }

  if(document.readyState==="loading"){
    document.addEventListener("DOMContentLoaded",trySwitch,{once:true});
  }else{
    trySwitch();
  }
})();
</script>
