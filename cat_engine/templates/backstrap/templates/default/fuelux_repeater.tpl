  <div class="repeater" id="{$repeater_id}" style="display:none">
    <div class="repeater-header">
      <div class="repeater-header-left">
        <span class="repeater-title">{translate($repeater_title)}</span>
        {if $repeater_search}
        <div class="repeater-search">
          <div class="search input-group">
            <input type="search" class="form-control" placeholder="Search" />
            <span class="input-group-btn">
              <button class="btn btn-default" type="button">
                <span class="fa fa-fw fa-search"></span>
                <span class="sr-only">{translate('Search')}</span>
              </button>
            </span>
          </div>
        </div>
        {/if}
      </div>
      {if $repeater_header_right}
      <div class="repeater-header-right">
        {if $repeater_filter_menu}
        <div class="btn-group selectlist repeater-filters" data-resize="auto">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="selected-label">&nbsp;</span>
            <span class="caret"></span>
            <span class="sr-only">{translate('Toggle Filters')}</span>
          </button>
          <ul class="dropdown-menu" role="menu">
            {foreach $repeater_filter_menu item}
            <li data-value="{$item}"><a href="#">{translate($item)}</a></li>
            {/foreach}
          </ul>
          <input class="hidden hidden-field" name="filterSelection" readonly="readonly" aria-hidden="true" type="text"/>
        </div>
        {/if}
        <div class="btn-group repeater-views" data-toggle="buttons">
          <label class="btn btn-default active">
            <input name="repeaterViews" type="radio" value="list"><span class="glyphicon glyphicon-list"></span>
          </label>
          <label class="btn btn-default">
            <input name="repeaterViews" type="radio" value="thumbnail"><span class="glyphicon glyphicon-th"></span>
          </label>
        </div>
      </div>
      {/if}
    </div>
    <div class="repeater-viewport">
      <div class="repeater-canvas"></div>
      <div class="loader repeater-loader"></div>
    </div>
    <div class="repeater-footer">
      <div class="repeater-footer-left">
        <div class="repeater-itemization">
          <span><span class="repeater-start"></span> - <span class="repeater-end"></span> {translate('of')} <span class="repeater-count"></span> {translate('items')}</span>
          <div class="btn-group selectlist" data-resize="auto">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
              <span class="selected-label">&nbsp;</span>
              <span class="caret"></span>
              <span class="sr-only">{translate('Toggle Dropdown')}</span>
            </button>
            <ul class="dropdown-menu" role="menu">
              <li data-value="5"><a href="#">5</a></li>
              <li data-value="10" data-selected="true"><a href="#">10</a></li>
              <li data-value="20"><a href="#">20</a></li>
              <li data-value="50" data-foo="bar" data-fizz="buzz"><a href="#">50</a></li>
              <li data-value="100"><a href="#">100</a></li>
            </ul>
            <input class="hidden hidden-field" name="itemsPerPage" readonly="readonly" aria-hidden="true" type="text"/>
          </div>
          <span>{translate('Per Page')}</span>
        </div>
      </div>
      <div class="repeater-footer-right">
        <div class="repeater-pagination">
          <button type="button" class="btn btn-default btn-sm repeater-prev">
            <span class="glyphicon glyphicon-chevron-left"></span>
            <span class="sr-only">{translate('Previous Page')}</span>
          </button>
          <label class="page-label" id="myPageLabel">{translate('Page')}</label>
          <div class="repeater-primaryPaging active">
            <div class="input-group input-append dropdown combobox">
              <input type="text" class="form-control" aria-labelledby="myPageLabel">
              <div class="input-group-btn">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                  <span class="caret"></span>
                  <span class="sr-only">{translate('Toggle Dropdown')}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right"></ul>
              </div>
            </div>
          </div>
          <input type="text" class="form-control repeater-secondaryPaging" aria-labelledby="myPageLabel">
          <span>of <span class="repeater-pages"></span></span>
          <button type="button" class="btn btn-default btn-sm repeater-next">
            <span class="glyphicon glyphicon-chevron-right"></span>
            <span class="sr-only">{translate('Next Page')}</span>
          </button>
        </div>
      </div>
    </div>
  </div>