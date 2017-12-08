{assign var="adsProvider" value=$adsProvider|default:null}
{assign var="adsProviderParams" value=$adsProviderParams|default:null}

{if !$suggestNoBanner && empty($adult)}
  <section class="row banner-section" data-placement="{$cfg.banner.placement}">
    <div class="center-block text-center">
      {if $adsProvider == 'diverta'}
        {* TODO: edit revive.tpl to make this work *}
        {include "banner/revive.tpl" zoneId="" params=$adsProviderParams}
      {elseif $cfg.banner.type == 'revive'}
        {include "banner/revive.tpl"}
      {elseif $cfg.banner.type == 'adsense'}
        {** Expects corresponding values in the [banner] section of dex.conf. **}
        {$key="adsense_`$pageType`"}
        {if $cfg.banner.$key}
          {include "banner/adsense.tpl" adUnitId=$cfg.banner.$key}
        {/if}
      {elseif $cfg.banner.type == 'dfp'}
        {include "banner/dfp.tpl"}
      {elseif $cfg.banner.type == 'pubgalaxy'}
        {include "banner/pubGalaxy.tpl"}
      {elseif $cfg.banner.type == 'fake'}
        <div class="center-block fakeBanner">
          Banner fals
        </div>
      {/if}
    </div>
  </section>
{/if}
