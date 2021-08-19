{if isset($confirmation)}
<div class="alert alert-success">Settings saved/updated</div>
{/if}
<fieldset>
  <h2>Ricarica PostePay Module Configuration</h2>
  <div class="panel">
    <div class="panel-heading">
      <legend><img src="../img/admin/cog.gif" alt="Config-gif" width="16" />Configuration</legend>
    </div>
    <form action="" method="post">
      <div class="form-group clearfix">
        <div class="row">
          <label class="col-lg-3">Owner name:</label>
          <input class="col-lg-9" type="text" id="owner_name" name="owner_name" value="{$owner_name}">
        </div>
      </div>

      <div class="form-group clearfix">
        <div class="row">
          <label class="col-lg-3">Owner fiscal code:</label>
          <input class="col-lg-9" type="text" id="owner_cf" name="owner_cf" value="{$owner_cf}">
        </div>
      </div>

      <div class="form-group clearfix">
        <div class="row">
          <label class="col-lg-3">Poste Pay card #:</label>
          <input class="col-lg-9" type="text" id="ppnr" name="ppnr" value="{$ppnr}">
        </div>
      </div>
      <div class="panel-footer">
        <input class="btn btn-default pull-right" type="submit" name="ppp_pc_form" value="Save" />
      </div>
    </form>
  </div>
</fieldset>