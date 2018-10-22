{$OnResourceTVFormPrerender}

{if $formcaption != ''}
    <h2>{$formcaption}</h2>
{/if} 

<input type="hidden" class="mulititems_grid_item_fields" name="mulititems_grid_item_fields" value='{$fields}' />
<input type="hidden" name="action" value='mgr/content/{$formAction}' />
<input type="hidden" name="resource_id" value='{$resource_id}' />

<div id="modx-window-mi-grid-update-{$win_id}-tabs">
{foreach from=$categories item=category name=cat}
{if count($category.tvs) > 0}

{if count($categories) < 2 OR ($smarty.foreach.cat.first AND $category.print_before_tabs)}
    <div id="modx-tv-tab{$category.id}" >
{else}
    <div id="modx-tv-tab{$category.id}" class="x-tab" title="{$category.category|default:$_lang.uncategorized|ucfirst}">
{/if}

{foreach from=$category.tvs item=tv name='tv'}

{if $tv->type EQ "description_is_code"}

    {$tv->get('formElement')}
   
{elseif $tv->type NEQ "hidden"}
    <div class="x-form-item x-tab-item {cycle values=",alt"} modx-tv" id="tv{$tv->id}-tr" style="padding: 10px 0 0 ;{if $tv->display EQ "none"}display:none;{/if} ">
        <label for="tv{$tv->id}" class="x-form-item-label modx-tv-label" style="width: auto;margin-bottom: 10px;">
            <div class="modx-tv-label-title"> 
                {if $showCheckbox}<input type="checkbox" name="tv{$tv->id}-checkbox" class="modx-tv-checkbox" value="1" />{/if}
                <span class="modx-tv-caption" id="tv{$tv->id}-caption">{$tv->caption}</span>
            </div>    
            <a class="modx-tv-reset" id="modx-tv-reset-{$tv->id}" onclick="MODx.resetTV({$tv->id});" title="{$_lang.set_to_default}"></a>
            
            {if $tv->description}<span class="modx-tv-label-description">{$tv->description}</span>{/if}
        </label>
        {if $tv->inherited}<br /><span class="modx-tv-inherited">{$_lang.tv_value_inherited}</span>{/if}
        <div class="x-form-clear-left"></div>
        <div class="x-form-element modx-tv-form-element" style="padding-left: 0px;">
            <input type="hidden" id="tvdef{$tv->id}" value="{$tv->default_text|escape}" />
            {$tv->get('formElement')}
        </div>

        <br class="clear" />
    </div>
    <script type="text/javascript">{literal}Ext.onReady(function() { new Ext.ToolTip({{/literal}target: 'tv{$tv->id}-caption',html: '[[*{$tv->name}]]'{literal}});});{/literal}</script>
{else}
    <input type="hidden" id="tvdef{$tv->id}" value="{$tv->default_text|escape}" />
    {$tv->get('formElement')}
{/if}
    {/foreach}

    </div>
    
{/if}
{/foreach}
</div>

{if count($categories) > 1}
{literal}
<script type="text/javascript">
// <![CDATA[
Ext.onReady(function() {    

    MODx.load({
        xtype: 'modx-tabs'
        ,applyTo: '{/literal}modx-window-mi-grid-update-{$win_id}-tabs{literal}'
        ,activeTab: 0
        ,autoTabs: true
        ,border: false
        ,plain: true
        ,width: '98%'
        ,hideMode: 'offsets'
        ,defaults: {
            bodyStyle: 'padding: 5px;'
            ,autoHeight: true
        }
        ,deferredRender: false
    });
	{/literal}{if $tvcount GT 0}{literal}
    {/literal}{/if}{literal}
});    
// ]]>
</script>
{/literal}
{/if}
{$OnResourceTVFormRender}

<br class="clear" />