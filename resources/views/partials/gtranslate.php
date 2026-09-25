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

  function getCookie(name){
    var prefix=name+"=";
    var parts=document.cookie.split(";");
    for(var i=0;i<parts.length;i++){
      var part=parts[i].trim();
      if(part.indexOf(prefix)===0){
        try{return decodeURIComponent(part.substring(prefix.length));}
        catch(e){return part.substring(prefix.length);}
      }
    }
    return "";
  }

  function setTranslateCookie(language){
    var value="/en/"+language;
    document.cookie="googtrans="+encodeURIComponent(value)+"; path=/; SameSite=Lax";
  }

  var previousCountry=null;
  try{previousCountry=localStorage.getItem("apex_gtranslate_country");}catch(e){}

  var currentTranslation=getCookie("googtrans");
  var currentLanguage=currentTranslation ? currentTranslation.split("/").pop() : "";

  /*
   * Country Pack changes should reset GTranslate to that market's default
   * language. Inside the same Country Pack, a visitor's manual language
   * selection is preserved.
   *
   * Also recover browsers affected by the previous implementation where the
   * dropdown changed visually but no googtrans cookie was actually created.
   */
  if(previousCountry!==countryId || !currentLanguage){
    setTranslateCookie(target);
    try{localStorage.setItem("apex_gtranslate_country",countryId);}catch(e){}
  }
})();

window.gtranslateSettings={
  default_language:"en",
  languages:["en","de","fr","it","es","nl","sv","no","da","ja","ko","zh-TW","hi","ar","pl","tl","pt"],
  wrapper_selector:".gtranslate_wrapper"
};
</script>
<script src="https://cdn.gtranslate.net/widgets/latest/dropdown.js" defer></script>
