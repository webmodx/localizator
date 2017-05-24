# localizator
MODx Revolution component for multilanguage sites with auto translate


header.tpl
```
{'!pdoMenu' | snippet : [
'parents' => 0,
'level' => 2,
'startId' => 0,
'leftJoin' => '{
"localizator" : {
"class" : "localizatorContent",
"alias" : "localizator",
"on" : "localizator.resource_id = modResource.id"
}
}',
'select' => '{ "localizator" : "modResource.*, localizator.*, modResource.id" }',
'where' => '{ "localizator.key" : "' ~ ('localizator_key' | option) ~ '"}',
'tplParentRow' => '@INLINE
<li class="[[+classnames]] dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown" [[+attributes]]>[[+menutitle]]<b class="caret"></b></a>
<ul class="dropdown-menu">{$wrapper}</ul>
</li>'
'tplOuter' => '@INLINE {$wrapper}'
]}
```

main.tpl
```
<h1>{$_modx->resource.longtitle ?: $_modx->resource.pagetitle}</h1>


<span style="color:red;">&#x7B;$_modx->resource->localizator_content&#x7D;</span><br>
<br>{$_modx->resource.localizator_content}
<hr>

<span style="color:red;">&#x7B;$_modx->resource.pagetitle&#x7D;</span> = {$_modx->resource.pagetitle}
<br><span style="color:red;">&#x7B;$_modx->resource.longtitle&#x7D;</span> = {$_modx->resource.longtitle}
<br>
<hr>
<br>
<ul>
{'!pdoResources' | snippet : [
  'parents' => 4,
  'leftJoin' => '{
    "localizator" : {
      "class" : "localizatorContent",
      "alias" : "localizator",
      "on" : "localizator.resource_id = modResource.id"
    }
  }',
  'select' => '{ "localizator" : "modResource.*, localizator.*, modResource.id" }',
    'where' => '{ "localizator.key" : "' ~ ('localizator_key' | option) ~ '"}',
    'tpl' => '@INLINE <li><a href="{$uri}">{$pagetitle}</a></li>'
]}
</ul>
<span style="color: #bbbbbb; font-size: 12px; margin-left: 41px;">Выводятся только переведенные документы</span>
<br>
<hr>
<span style="color:red;">&#x7B;'localizator_key' | option&#x7D;</span> = {'localizator_key' | option}
<br><span style="color:red;">&#x7B;'cultureKey' | option&#x7D;</span> = {'cultureKey' | option}
<br><span style="color:red;">&#x7B;'cache_resource_key' | option&#x7D;</span> = {'cache_resource_key' | option}
<br><span style="color:red;">&#x7B;'site_url' | option&#x7D;</span> = {'site_url' | option}
<hr>
{'!getLanguages' | snippet}
```

getLanguages snippet 
```
<?php
$output = "";

// определяем есть ли языки через "папки"
$uri = $_SERVER['REQUEST_URI'];
if(substr($uri, 0, 1)) {
    $uri = mb_substr($uri, 1);
    $tmp = explode('/', $uri);
    if($path = $tmp[0]) {
        $tmp = $modx->getObject('localizatorLanguage', array('http_host:LIKE' => "%/{$path}/"));
        if($tmp) {
            $uri = str_replace("{$path}/", "", $uri);
        }
    }
}

$languages = $modx->getIterator('localizatorLanguage');
foreach($languages as $language) {
    if(mb_substr($language->http_host, -1) == '/') {
        $link = $language->http_host . $uri;
    } else { 
        $link = $language->http_host . '/' . $uri;
    }
    $output .= "<br><a href=\"http://{$link}\">{$language->name}</a>"; 
}
    
return $output;
```
