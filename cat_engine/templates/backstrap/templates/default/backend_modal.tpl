<div class="modal fade" id="{$modal_id}" tabindex="-1" role="dialog" aria-labelledby="bs{$modal_id}Label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="bs{$modal_id}Label">{translate($modal_title)}</h4>
      </div>
      <div class="modal-body">
        {if $modal_text}<div class="info">{translate($modal_text)}</div><br /><br />{/if}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{translate('Close')}</button>
        {if $modal_savebtn}
        <button type="button" class="btn btn-primary">{translate('Save changes')}</button>
        {/if}
      </div>
    </div>
  </div>
</div>