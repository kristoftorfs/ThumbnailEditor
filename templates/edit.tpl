<div id="thumber-config"
     data-min-width="{$image.minwidth}" data-min-height="{$image.minheight}"
     data-max-width="{$image.maxwidth}" data-max-height="{$image.maxheight}"
     data-thumb-width="{$thumb->GetConfigWidth()}" data-thumb-height="{$thumb->GetConfigHeight()}"
     data-thumb-resize-width="{$thumb->GetCropWidth()}" data-thumb-resize-height="{$thumb->GetCropHeight()}"
     data-thumb-x="{$thumb->GetCropX()}" data-thumb-y="{$thumb->GetCropY()}"
></div>
{$form.start}
{$form.input}
<fieldset>
    <legend>{$lang.uploadtitle|escape}</legend>
    {$form.file}{$form.upload}
</fieldset>
<div class="pageoverflow">
    <p class="pageinput" id="thumber-user-config">
        {$form.submit}{$form.cancel}{$form.apply}
        {$lang.config|escape}: <span>{$thumb->GetCropWidth()}</span>x<span>{$thumb->GetCropHeight()}</span>; <span>{$thumb->GetCropX()}</span>x<span>{$thumb->GetCropY()}</span>
    </p>
</div>
<div class="pageoverflow">
    <p class="pageinput"></p>
</div>
<div class="pageoverflow">
    <div class="pagetext">{$lang.frame|escape}:</div>
    <div class="pageinput">
        <div id="thumber-admin">
            <img id="thumber-img" src="{$thumb->GetOriginalUrl()}" alt="">
            <div id="thumber-drag">
                <div id="thumber-size">{$thumb->GetConfigWidth()}x{$thumb->GetConfigHeight()}</div>
            </div>
        </div>
    </div>
</div>
{$form.end}