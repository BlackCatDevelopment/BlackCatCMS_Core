{template pagetree data}
  {foreach $data item}
    <li>
      <span class="pull-left fa fa-fw{if $item.children} fa-caret-right" id="pg{$item.page_id}" data-toggle="collapse" data-target="#sub{$item.page_id}" aria-expanded="false{/if}"></span>
      <a href="{$_root.CAT_ADMIN_URL}/page/edit/{$item.page_id}" class="hasTooltip">{$item.menu_title}</a>
      <div class="hidden">
        {translate('Page ID')}: {$item.page_id}<br />
        {if count($_.sections[$item.page_id])}
          <strong>{translate('Sections')}:</strong><br />
          {foreach $_.sections[$item.page_id] secitem}{foreach $secitem section}{$section.module} (ID: {$section.section_id})<br />{/foreach}{/foreach}
        {/if}
        {if $item.template}<strong>{translate('Template')}:</strong> {$item.template}{/if}
      </div>
      {if $item.children}
      <ul class="nav collapse submenu" id="sub{$item.page_id}" role="menu" aria-labelledby="pg{$item.page_id}">
        {pagetree $item.children}
      </ul>{/if}
    </li>
    {/foreach}
{/template}
<div class="col-sm-3 col-md-2 sidebar-offcanvas" id="sidebar" role="navigation">
  <ul class="nav nav-sidebar">
    {pagetree $pages}
  </ul>
</div>

