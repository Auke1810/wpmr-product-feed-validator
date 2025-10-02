(function($){
  function api(path, options){
    options = options || {};
    options.headers = options.headers || {};
    options.headers['X-WP-Nonce'] = (window.WPMR_PFV_ADMIN && WPMR_PFV_ADMIN.nonce) || '';
    options.headers['Content-Type'] = 'application/json';
    options.credentials = 'same-origin';
    return fetch((WPMR_PFV_ADMIN.restBase.replace(/\/$/, '') + '/' + path.replace(/^\//,'' ) ), options)
      .then(function(res){ return res.json().then(function(data){ return {ok: res.ok, status: res.status, data: data};});});
  }

  function rowRuleCode($row){ return $row.data('code'); }

  function saveOverride($row){
    var code = rowRuleCode($row);
    var sev  = $row.find('.wpmr-pfv-rule-severity').val();
    var en   = $row.find('.wpmr-pfv-rule-enabled').is(':checked');
    var wStr = $row.find('.wpmr-pfv-rule-weight').val();
    var w    = (wStr === '' || wStr === null) ? null : parseInt(wStr, 10);
    $row.addClass('is-saving');
    var payload = { rule_code: code, severity: sev, enabled: en };
    if(!isNaN(w) && w !== null){ payload.weight_override = w; }
    return api('rules/overrides', { method: 'POST', body: JSON.stringify(payload) }).then(function(r){
      $row.removeClass('is-saving');
      if(!r.ok){ console.warn('Save failed', r); alert('Failed to save rule override: ' + (r.data && r.data.message || r.status)); }
      return r;
    });
  }

  function resetOverride($row){
    var code = rowRuleCode($row);
    $row.addClass('is-saving');
    return api('rules/overrides/' + encodeURIComponent(code), { method: 'DELETE' }).then(function(r){
      $row.removeClass('is-saving');
      if(!r.ok){ console.warn('Reset failed', r); alert('Failed to reset rule: ' + (r.data && r.data.message || r.status)); }
      // Clear the weight field UI
      $row.find('.wpmr-pfv-rule-weight').val('');
      // Refresh the row UI by fetching current effective rule (optional). For now do nothing; server will apply defaults.
      return r;
    });
  }

  function filterRows(term){
    term = (term || '').toLowerCase();
    $('#wpmr-pfv-rules-body tr').each(function(){
      var $row = $(this);
      var hay = ($row.data('code')+' '+$row.data('category')+' '+$row.data('message')).toLowerCase();
      var show = term === '' || hay.indexOf(term) !== -1;
      $row.toggle(show);
    });
  }

  $(function(){
    var $table = $('.wpmr-pfv-rules');
    if(!$table.length) return;

    // Change handlers
    $('#wpmr-pfv-rules-body').on('change', '.wpmr-pfv-rule-severity, .wpmr-pfv-rule-enabled, .wpmr-pfv-rule-weight', function(){
      var $row = $(this).closest('tr');
      saveOverride($row);
    });

    // Reset
    $('#wpmr-pfv-rules-body').on('click', '.wpmr-pfv-rule-reset', function(){
      var $row = $(this).closest('tr');
      resetOverride($row);
    });

    // Search
    $('#wpmr-pfv-rules-search').on('input', function(){
      filterRows($(this).val());
    });

    // Save global weights
    $('#wpmr-pfv-save-weights').on('click', function(){
      var $btn = $(this);
      var $status = $('#wpmr-pfv-weights-status');
      var payload = {};
      function numVal(sel){ var v = $(sel).val(); return (v === '' || v === null) ? null : parseInt(v, 10); }
      var e = numVal('#wpmr-pfv-weight-error');
      var w = numVal('#wpmr-pfv-weight-warning');
      var a = numVal('#wpmr-pfv-weight-advice');
      var c = numVal('#wpmr-pfv-weight-cap');
      if(e !== null) payload.error = e;
      if(w !== null) payload.warning = w;
      if(a !== null) payload.advice = a;
      if(c !== null) payload.cap_per_category = c;

      $btn.prop('disabled', true);
      $status.text('Saving…');
      api('rules/weights', {
        method: 'POST',
        body: JSON.stringify(payload)
      }).then(function(r){
        if(r.ok){
          $status.text('Saved');
        } else {
          console.warn('Save weights failed', r);
          $status.text('Error: ' + (r.data && r.data.message || r.status));
        }
      }).catch(function(err){
        console.error(err);
        $status.text('Error');
      }).finally(function(){
        $btn.prop('disabled', false);
      });
    });

    // Export JSON
    $('#wpmr-pfv-export-json').on('click', function(){
      api('rules/export', { method: 'GET' }).then(function(r){
        if(!r.ok){ alert('Export failed: ' + (r.data && r.data.message || r.status)); return; }
        var blob = new Blob([JSON.stringify(r.data, null, 2)], {type: 'application/json'});
        var a = document.createElement('a');
        var url = URL.createObjectURL(blob);
        a.href = url;
        var dt = new Date().toISOString().replace(/[:.]/g,'-');
        a.download = 'wpmr-pfv-rules-' + dt + '.json';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
      });
    });

    // Import JSON
    $('#wpmr-pfv-import-file').on('change', function(){
      var file = this.files && this.files[0];
      var $status = $('#wpmr-pfv-importexport-status');
      if(!file){ return; }
      var reader = new FileReader();
      reader.onload = function(){
        var text = reader.result;
        var data = null;
        try { data = JSON.parse(text); } catch(e){ alert('Invalid JSON'); return; }
        $status.text('Importing…');
        api('rules/import', { method: 'POST', body: JSON.stringify(data) }).then(function(r){
          if(r.ok){ $status.text('Imported'); location.reload(); } else { $status.text('Error: ' + (r.data && r.data.message || r.status)); }
        }).catch(function(){ $status.text('Error'); });
      };
      reader.readAsText(file);
      // reset input
      $(this).val('');
    });

    // Restore defaults
    $('#wpmr-pfv-restore-defaults').on('click', function(){
      if(!confirm('Restore all rule overrides and weights to defaults?')) return;
      var $status = $('#wpmr-pfv-importexport-status');
      $status.text('Restoring…');
      api('rules/restore', { method: 'POST', body: JSON.stringify({}) }).then(function(r){
        if(r.ok){ $status.text('Restored'); location.reload(); } else { $status.text('Error: ' + (r.data && r.data.message || r.status)); }
      }).catch(function(){ $status.text('Error'); });
    });

    // Run tests
    $('#wpmr-pfv-run-tests').on('click', function(){
      var $btn = $(this);
      var $status = $('#wpmr-pfv-tests-status');
      var $out = $('#wpmr-pfv-tests-output');
      $btn.prop('disabled', true);
      $status.text('Running…');
      $out.text('');
      api('tests', { method: 'GET' }).then(function(r){
        if(r.ok){
          $status.text(r.data && r.data.passed ? 'All tests passed' : 'Some tests failed');
          try { $out.text(JSON.stringify(r.data, null, 2)); } catch(e){ $out.text(String(r.data)); }
        } else {
          $status.text('Error: ' + (r.data && r.data.message || r.status));
          try { $out.text(JSON.stringify(r.data, null, 2)); } catch(e){ $out.text(String(r.data)); }
        }
      }).catch(function(err){
        console.error(err);
        $status.text('Error');
        $out.text(String(err));
      }).finally(function(){
        $btn.prop('disabled', false);
      });
    });
  });
})(jQuery);
