(function(){
  function qs(form, name){ return form.querySelector('[name="'+name+'"]'); }

  function el(tag, cls, text){
    var e = document.createElement(tag);
    if(cls){ e.className = cls; }
    if(text !== undefined){ e.textContent = String(text); }
    return e;
  }

  // "How to Fix" configuration for error codes
  var HOW_TO_FIX = {
    'missing_xml_declaration': 'Add <?xml version="1.0" encoding="UTF-8"?> at the start of your XML file.',
    'invalid_xml_version': 'Change the XML version to "1.0" or "1.1" in your declaration.',
    'encoding_mismatch': 'Ensure your file encoding matches the declared encoding in the XML declaration.',
    'missing_google_namespace': 'Add xmlns:g="http://base.google.com/ns/1.0" to your root <rss> or <feed> element.',
    'invalid_root_element': 'Use <rss> for RSS feeds or <feed> for Atom feeds as the root element.',
    'bom_detected': 'Remove the BOM (Byte Order Mark) from your XML file or ensure it matches your declared encoding.',
    'missing_encoding': 'Add encoding="UTF-8" to your XML declaration.',
    'uncommon_encoding': 'Consider using UTF-8 encoding for better compatibility.',
    'required_price': 'Add a <g:price> element with the product price (e.g., <g:price>19.99 USD</g:price>).',
    'required_title': 'Add a <g:title> element with the product name.',
    'required_description': 'Add a <g:description> element with the product description.',
    'required_link': 'Add a <g:link> element with the product URL.',
    'required_image_link': 'Add a <g:image_link> element with the product image URL.',
    'duplicate_id': 'Ensure each product has a unique <g:id> value. Check for duplicates in your feed.',
    'missing_id': 'Add a <g:id> element to each product with a unique identifier.'
  };

  /**
   * Transform REST API response to display-friendly format
   * @param {Object} apiResponse - Response from /wpmr/v1/validate endpoint
   * @returns {Object} Transformed data for display
   */
  function transformValidationData(apiResponse) {
    var report = apiResponse && apiResponse.report;
    if (!report) return null;

    var diagnostics = Array.isArray(report.diagnostics) ? report.diagnostics : [];
    var issues = Array.isArray(report.issues) ? report.issues : [];
    var totals = report.totals || {};
    var itemsScanned = report.items_scanned || 0;

    // Calculate statistics
    var errorCount = totals.errors || 0;
    var warningCount = totals.warnings || 0;
    var validProducts = Math.max(0, itemsScanned - errorCount);

    // Determine overall status
    var status = 'success';
    var summary = 'Feed validated successfully!';
    if (errorCount > 0) {
      status = 'error';
      summary = 'Feed has ' + errorCount + ' critical error' + (errorCount !== 1 ? 's' : '') + ' that must be fixed.';
    } else if (warningCount > 0) {
      status = 'warning';
      summary = 'Feed validated with ' + warningCount + ' warning' + (warningCount !== 1 ? 's' : '') + '.';
    }

    // Group diagnostics and issues by error code
    var errorGroups = {};
    var warningGroups = {};

    // Process diagnostics (feed-level issues)
    diagnostics.forEach(function(diag) {
      var severity = (diag.severity || '').toLowerCase();
      var code = diag.code || 'unknown';
      var message = diag.message || 'No message provided';
      
      var group = severity === 'error' ? errorGroups : warningGroups;
      if (!group[code]) {
        group[code] = {
          title: formatErrorTitle(code),
          message: message,
          affected_items: [],
          affected_count: 0,
          how_to_fix: HOW_TO_FIX[code] || 'Please review your feed configuration.',
          code: code
        };
      }
    });

    // Process issues (product-level issues)
    issues.forEach(function(issue) {
      var severity = (issue.severity || '').toLowerCase();
      var code = issue.code || issue.rule_id || 'unknown';
      var itemId = issue.item_id || '';
      var message = issue.message || 'No message provided';
      
      var group = severity === 'error' ? errorGroups : warningGroups;
      if (!group[code]) {
        group[code] = {
          title: formatErrorTitle(code),
          message: message,
          affected_items: [],
          affected_count: 0,
          how_to_fix: HOW_TO_FIX[code] || 'Please review the affected products and fix the issue.',
          code: code
        };
      }
      
      if (itemId && group[code].affected_items.indexOf(itemId) === -1) {
        group[code].affected_items.push(itemId);
      }
      group[code].affected_count++;
    });

    // Convert groups to arrays
    var errors = Object.keys(errorGroups).map(function(code) { return errorGroups[code]; });
    var warnings = Object.keys(warningGroups).map(function(code) { return warningGroups[code]; });

    return {
      status: status,
      summary: summary,
      total_products: itemsScanned,
      error_count: errorCount,
      warning_count: warningCount,
      valid_products: validProducts,
      errors: errors,
      warnings: warnings,
      score: report.score,
      duplicates: report.duplicates || [],
      missing_id_count: report.missing_id_count || 0
    };
  }

  /**
   * Format error code into human-readable title
   * @param {string} code - Error code
   * @returns {string} Formatted title
   */
  function formatErrorTitle(code) {
    if (!code) return 'Unknown Issue';
    // Convert snake_case to Title Case
    return code
      .split('_')
      .map(function(word) { return word.charAt(0).toUpperCase() + word.slice(1); })
      .join(' ');
  }

  function readiness(score){
    var s = typeof score === 'number' ? score : -1;
    if(s >= 90) return { label: 'Ready', cls: 'ready', badgeBg: '#1d7a2e' };
    if(s >= 70) return { label: 'Needs improvements', cls: 'needs', badgeBg: '#946200' };
    return { label: 'Critical', cls: 'critical', badgeBg: '#b20000' };
  }

  function buildCsv(issues){
    var lines = ['item_id,code,severity,category,message'];
    (issues||[]).forEach(function(it){
      var row = [it.item_id||'', it.code||'', it.severity||'', it.category||'', it.message||''];
      row = row.map(function(c){ return '"'+String(c).replace(/"/g,'""')+'"'; });
      lines.push(row.join(','));
    });
    return lines.join('\n');
  }

  function renderReport(container, data){
    var report = data && data.report;
    if(!report){ return; }

    // Clear
    container.innerHTML = '';

    // Heading with score badge + readiness label
    var titleWrap = el('div');
    var title = el('h4', 'wpmr-pfv-title', 'Feed Validation Report');
    titleWrap.appendChild(title);
    var rdy = readiness(report.score);
    var badge = el('span', 'wpmr-pfv-score-badge', String(report.score != null ? report.score : '—'));
    badge.style.background = rdy.badgeBg;
    var label = el('span', 'wpmr-pfv-label '+rdy.cls, rdy.label);
    titleWrap.appendChild(badge);
    titleWrap.appendChild(label);
    container.appendChild(titleWrap);

    // Summary
    var summary = el('div', 'wpmr-pfv-summary');
    var score = el('div', 'wpmr-pfv-score', 'Score: ' + (report.score != null ? report.score : '—'));
    var totals = el('div', 'wpmr-pfv-totals',
      'Totals — Errors: ' + (report.totals && report.totals.errors || 0) + ', Warnings: ' + (report.totals && report.totals.warnings || 0)
      + (report.totals && report.totals.advice != null ? ', Advice: ' + report.totals.advice : '')
    );
    var scanned = el('div', 'wpmr-pfv-scanned', 'Items scanned: ' + (report.items_scanned != null ? report.items_scanned : '—'));
    summary.appendChild(score); summary.appendChild(totals); summary.appendChild(scanned);
    container.appendChild(summary);

    // Transport
    if(report.transport){
      var t = el('div', 'wpmr-pfv-transport');
      var tH = el('strong', '', 'Transport'); t.appendChild(tH);
      var tP = el('div', '',
        ' HTTP ' + (report.transport.http_code != null ? report.transport.http_code : '—')
        + ' · ' + (report.transport.content_type || 'unknown content-type')
        + ' · ' + (report.transport.bytes != null ? (report.transport.bytes + ' bytes') : 'unknown size')
      );
      t.appendChild(tP);
      container.appendChild(t);
    }

    // Top issues
    var issues = Array.isArray(report.issues) ? report.issues : [];
    if(issues.length){
      var maxShow = 5;
      var listWrap = el('div', 'wpmr-pfv-issues');
      listWrap.appendChild(el('strong', '', 'Top issues ('+Math.min(issues.length, maxShow)+' of '+issues.length+')'));
      var ul = el('ul', 'wpmr-pfv-issues-list');
      issues.slice(0, maxShow).forEach(function(it){
        var li = el('li', 'wpmr-pfv-issue');
        var suffix = '';
        if(it.item_id){
          if(/^\(missing:#\d+\)$/.test(it.item_id)){
            // Keep displaying item index if id is missing
            var idx = it.item_id.replace(/^\(missing:#(\d+)\)$/,'#$1');
            suffix = ' (item ' + idx + ')';
          } else {
            // Show actual product id
            suffix = ' (id ' + it.item_id + ')';
          }
        }
        var line = (it.severity ? '['+it.severity+'] ' : '') + (it.code || 'issue') + ': ' + (it.message || '') + suffix;
        li.textContent = line + ' ';
        if (it.docs_url) {
          var a = document.createElement('a');
          a.href = it.docs_url; a.target = '_blank'; a.rel = 'noopener'; a.className = 'wpmr-pfv-docs-link';
          a.textContent = 'Read Documentation';
          li.appendChild(a);
        }
        ul.appendChild(li);
      });
      listWrap.appendChild(ul);
      container.appendChild(listWrap);
    }

    // Actions
    var actions = el('div', 'wpmr-pfv-actions');
    var btnResend = el('button', '', 'Resend report');
    btnResend.type = 'button';
    btnResend.addEventListener('click', function(){
      if(window.__WPMR_LAST_REQ__){
        // Re-run validation to send email again
        fetch(window.__WPMR_LAST_REQ__.endpoint, {
          method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': WPMR_PFV_I18N.rest_nonce||'' }, credentials: 'same-origin',
          body: JSON.stringify({ url: window.__WPMR_LAST_REQ__.url, email: window.__WPMR_LAST_REQ__.email, consent: window.__WPMR_LAST_REQ__.consent, sample: window.__WPMR_LAST_REQ__.sample })
        });
      }
    });
    actions.appendChild(btnResend);

    var btnFullScan = el('button', '', 'Run full scan');
    btnFullScan.type = 'button';
    btnFullScan.addEventListener('click', function(){
      var last = window.__WPMR_LAST_REQ__;
      if(!last){ return; }
      var fullscanEndpoint = last.endpoint.replace(/validate$/, 'fullscan');
      // Re-use captcha from current form if present
      var form = document.querySelector('.wpmr-pfv-form');
      var captchaToken = '';
      if(form){
        var rec = form.querySelector('[name="g-recaptcha-response"]');
        var cft = form.querySelector('[name="cf-turnstile-response"]');
        if(rec && rec.value){ captchaToken = rec.value; }
        else if(cft && cft.value){ captchaToken = cft.value; }
      }
      fetch(fullscanEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': WPMR_PFV_I18N.rest_nonce||'' },
        credentials: 'same-origin',
        body: JSON.stringify({ url: last.url, email: last.email, consent: last.consent, captcha_token: captchaToken })
      }).then(function(res){ return res.json().then(function(data){ return { ok: res.ok, data: data }; }); })
      .then(function(r){
        var msg = (r.data && r.data.message) ? r.data.message : (r.ok ? 'Full scan queued.' : 'Failed to queue full scan.');
        var info = el('div','wpmr-pfv-note', msg);
        container.appendChild(info);
      }).catch(function(){
        var info = el('div','wpmr-pfv-note', 'Failed to queue full scan.');
        container.appendChild(info);
      });
    });
    actions.appendChild(btnFullScan);

    var btnDownload = el('button', '', 'Download CSV');
    btnDownload.type = 'button';
    if(WPMR_PFV_I18N && WPMR_PFV_I18N.is_logged_in){
      btnDownload.addEventListener('click', function(){
        var csv = buildCsv(issues);
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'feed-report.csv';
        document.body.appendChild(link);
        link.click();
        setTimeout(function(){ URL.revokeObjectURL(link.href); link.remove(); }, 0);
      });
    } else {
      btnDownload.disabled = true;
      btnDownload.title = 'CSV download is available for logged-in users; anonymous users receive CSV via email.';
    }
    actions.appendChild(btnDownload);
    container.appendChild(actions);

    // Table filters + table
    if(issues.length){
      var controls = el('div', 'wpmr-pfv-table-controls');
      // Severity select
      var sel = document.createElement('select');
      ;['all','error','warning','advice'].forEach(function(opt){ var o = document.createElement('option'); o.value=opt; o.textContent = opt.charAt(0).toUpperCase()+opt.slice(1); sel.appendChild(o); });
      sel.className = 'wpmr-pfv-filter'; sel.style.width = 'auto'; sel.style.display='inline-block';
      controls.appendChild(sel);
      // Code filter
      var codeFilter = document.createElement('input'); codeFilter.type='text'; codeFilter.placeholder = 'Filter by code…';
      codeFilter.className = 'wpmr-pfv-filter'; codeFilter.style.width='220px'; codeFilter.style.display='inline-block';
      controls.appendChild(codeFilter);
      // Item ID filter
      var idFilter = document.createElement('input'); idFilter.type='text'; idFilter.placeholder = 'Filter by item id…';
      idFilter.className = 'wpmr-pfv-filter'; idFilter.style.width='220px'; idFilter.style.display='inline-block';
      controls.appendChild(idFilter);
      // Grouping select
      var groupLabel = document.createElement('label'); groupLabel.textContent = ' Group by: ';
      var groupSel = document.createElement('select');
      [{v:'code', t:'Code'}, {v:'product', t:'Product'}, {v:'none', t:'None'}].forEach(function(it){ var o = document.createElement('option'); o.value = it.v; o.textContent = it.t; groupSel.appendChild(o); });
      groupSel.value = 'code';
      groupSel.style.width='auto'; groupSel.style.display='inline-block';
      groupLabel.className = 'wpmr-pfv-group-toggle wpmr-pfv-filter';
      groupLabel.appendChild(groupSel);
      controls.appendChild(groupLabel);
      container.appendChild(controls);

      // Where we render either flat table or grouped view
      var tableWrap = el('div', 'wpmr-pfv-table-wrap');
      container.appendChild(tableWrap);

      function getFilteredIssues(){
        var sev = sel.value; var cf = codeFilter.value.trim().toLowerCase(); var idf = idFilter.value.trim().toLowerCase();
        return issues.filter(function(it){
          if(sev!=='all' && (it.severity||'').toLowerCase()!==sev) return false;
          if(cf && String(it.code||'').toLowerCase().indexOf(cf)===-1) return false;
          if(idf && String(it.item_id||'').toLowerCase().indexOf(idf)===-1) return false;
          return true;
        });
      }

      function renderFlat(){
        tableWrap.innerHTML = '';
        var table = el('table', 'wpmr-pfv-table');
        var thead = document.createElement('thead'); var trh = document.createElement('tr');
        ['Severity','Code','Category','Message','Item ID','Docs'].forEach(function(h){ var th = document.createElement('th'); th.textContent = h; trh.appendChild(th); });
        thead.appendChild(trh); table.appendChild(thead);
        var tbody = document.createElement('tbody'); table.appendChild(tbody);
        tableWrap.appendChild(table);
        getFilteredIssues().forEach(function(it){
          var tr = document.createElement('tr');
          var tdSev = el('td','wpmr-pfv-sev ' + (it.severity||''),(it.severity||'')); tr.appendChild(tdSev);
          tr.appendChild(el('td','',it.code||''));
          tr.appendChild(el('td','',it.category||''));
          tr.appendChild(el('td','',it.message||''));
          tr.appendChild(el('td','',it.item_id||''));
          var tdDocs = document.createElement('td');
          if(it.docs_url){ var a = document.createElement('a'); a.href = it.docs_url; a.target = '_blank'; a.rel='noopener'; a.className='wpmr-pfv-docs-link'; a.textContent = 'Read Documentation'; tdDocs.appendChild(a);} else { tdDocs.textContent=''; }
          tr.appendChild(tdDocs);
          tbody.appendChild(tr);
        });
      }

      function normalizeId(id){ return (id==null||id==='') ? '(missing)' : String(id); }
      function prettyId(id){
        if(/^\(missing:#\d+\)$/.test(id)){ return 'Item index ' + id.replace(/^\(missing:#(\d+)\)$/,'#$1'); }
        if(id==="(missing)"){ return 'Item ID missing'; }
        return id;
      }

      function renderGroupedByProduct(){
        tableWrap.innerHTML = '';
        var filtered = getFilteredIssues();
        // Group by normalized item_id
        var groups = {};
        filtered.forEach(function(it){
          var key = normalizeId(it.item_id);
          (groups[key] = groups[key] || []).push(it);
        });
        // Sort groups by item_id alphanumeric for stability
        Object.keys(groups).sort().forEach(function(id){
          var list = groups[id];
          var errors = list.filter(function(x){return (x.severity||'').toLowerCase()==='error';}).length;
          var warnings = list.filter(function(x){return (x.severity||'').toLowerCase()==='warning';}).length;
          var advice = list.filter(function(x){return (x.severity||'').toLowerCase()==='advice';}).length;
          var details = document.createElement('details'); details.className = 'wpmr-pfv-product';
          var summary = document.createElement('summary');
          summary.textContent = prettyId(id) + ' — ' + errors + ' errors, ' + warnings + ' warnings' + (advice?(', '+advice+' advice'):'');
          details.appendChild(summary);
          // inner table for issues of this product
          var table = el('table', 'wpmr-pfv-table wpmr-pfv-table-inner');
          var thead = document.createElement('thead'); var trh = document.createElement('tr');
          ['Severity','Code','Category','Message','Docs'].forEach(function(h){ var th = document.createElement('th'); th.textContent = h; trh.appendChild(th); });
          thead.appendChild(trh); table.appendChild(thead);
          var tbody = document.createElement('tbody'); table.appendChild(tbody);
          list.forEach(function(it){
            var tr = document.createElement('tr');
            var tdSev = el('td','wpmr-pfv-sev ' + (it.severity||''),(it.severity||'')); tr.appendChild(tdSev);
            tr.appendChild(el('td','',it.code||''));
            tr.appendChild(el('td','',it.category||''));
            tr.appendChild(el('td','',it.message||''));
            var tdDocs = document.createElement('td'); if(it.docs_url){ var a = document.createElement('a'); a.href = it.docs_url; a.target = '_blank'; a.rel='noopener'; a.className='wpmr-pfv-docs-link'; a.textContent = 'Read Documentation'; tdDocs.appendChild(a);} else { tdDocs.textContent=''; } tr.appendChild(tdDocs);
            tbody.appendChild(tr);
          });
          details.appendChild(table);
          tableWrap.appendChild(details);
        });
      }

      function renderGroupedByCode(){
        tableWrap.innerHTML = '';
        var filtered = getFilteredIssues();
        // Group by issue code and compute metadata
        var byCode = {};
        filtered.forEach(function(it){
          var key = (it.code && String(it.code)) || '(uncoded)';
          (byCode[key] = byCode[key] || { list: [], counts: { e:0,w:0,a:0 }, ids: new Set() }).list.push(it);
          var sev = (it.severity||'').toLowerCase();
          if(sev==='error') byCode[key].counts.e++; else if(sev==='warning') byCode[key].counts.w++; else if(sev==='advice') byCode[key].counts.a++;
          var id = (it.item_id!=null && it.item_id!=='') ? String(it.item_id) : ('idx:'+byCode[key].list.length); // fallback unique-ish
          byCode[key].ids.add(id);
        });
        var totalItems = (report && typeof report.items_scanned==='number' && report.items_scanned>0) ? report.items_scanned : null;
        var entries = Object.keys(byCode).map(function(code){
          var meta = byCode[code];
          var uniqueCount = meta.ids.size;
          var global = totalItems ? (uniqueCount >= totalItems) : false;
          return { code: code, list: meta.list, counts: meta.counts, unique: uniqueCount, global: global };
        });
        // Sort: global first, then errors desc, then warnings desc, advice desc, then code asc
        entries.sort(function(a,b){
          if(a.global !== b.global) return (b.global?1:0) - (a.global?1:0);
          if(b.counts.e !== a.counts.e) return b.counts.e - a.counts.e;
          if(b.counts.w !== a.counts.w) return b.counts.w - a.counts.w;
          if(b.counts.a !== a.counts.a) return b.counts.a - a.counts.a;
          return a.code.localeCompare(b.code);
        });
        entries.forEach(function(entry){
          var code = entry.code; var list = entry.list; var errors = entry.counts.e; var warnings = entry.counts.w; var advice = entry.counts.a; var isGlobal = entry.global;
          var details = document.createElement('details'); details.className = 'wpmr-pfv-code' + (isGlobal ? ' global' : '');
          var summary = document.createElement('summary');
          var lead = code + ' — ' + errors + ' errors, ' + warnings + ' warnings' + (advice?(', '+advice+' advice'):'') + ' • ' + list.length + ' issues';
          if(isGlobal){ lead += ' • affects all products'; }
          summary.appendChild(document.createTextNode(lead + ' '));
          // Add one docs link at group level (use first issue's docs_url)
          var docUrl = list.find(function(x){ return !!x.docs_url; });
          if(docUrl && docUrl.docs_url){ var al = document.createElement('a'); al.href = docUrl.docs_url; al.target='_blank'; al.rel='noopener'; al.className='wpmr-pfv-docs-link'; al.textContent='Read Documentation'; summary.appendChild(al); }
          details.appendChild(summary);
          // inner table for this code — cap rows if global to keep UI small
          var table = el('table', 'wpmr-pfv-table wpmr-pfv-table-inner');
          var thead = document.createElement('thead'); var trh = document.createElement('tr');
          ['Item ID','Category','Message','Severity','Docs'].forEach(function(h){ var th = document.createElement('th'); th.textContent = h; trh.appendChild(th); });
          thead.appendChild(trh); table.appendChild(thead);
          var tbody = document.createElement('tbody'); table.appendChild(tbody);
          var limit = isGlobal ? 25 : list.length;
          for(var i=0;i<Math.min(limit, list.length); i++){
            var it = list[i];
            var tr = document.createElement('tr');
            tr.appendChild(el('td','',it.item_id||''));
            tr.appendChild(el('td','',it.category||''));
            tr.appendChild(el('td','',it.message||''));
            var tdSev = el('td','wpmr-pfv-sev ' + (it.severity||''),(it.severity||'')); tr.appendChild(tdSev);
            var tdDocs2 = document.createElement('td'); if(it.docs_url){ var a2 = document.createElement('a'); a2.href = it.docs_url; a2.target = '_blank'; a2.rel='noopener'; a2.className='wpmr-pfv-docs-link'; a2.textContent = 'Read Documentation'; tdDocs2.appendChild(a2);} else { tdDocs2.textContent=''; } tr.appendChild(tdDocs2);
            tbody.appendChild(tr);
          }
          if(isGlobal && list.length>limit){
            var more = el('div','wpmr-pfv-note','Showing first '+limit+' of '+list.length+' items affected.');
            details.appendChild(more);
          }
          details.appendChild(table);
          tableWrap.appendChild(details);
        });
      }

      function refresh(){
        var mode = groupSel.value;
        if(mode==='product'){ renderGroupedByProduct(); }
        else if(mode==='code'){ renderGroupedByCode(); }
        else { renderFlat(); }
      }

      sel.addEventListener('change', refresh);
      codeFilter.addEventListener('input', refresh);
      idFilter.addEventListener('input', refresh);
      groupSel.addEventListener('change', refresh);
      refresh();
    }

    // CTA panel
    if (WPMR_PFV_I18N && WPMR_PFV_I18N.docs_url) {
      var cta = el('div', 'wpmr-pfv-cta');
      var ctaTitle = el('div', 'wpmr-pfv-cta-title', 'Need help fixing issues?');
      var ctaLink = document.createElement('a');
      ctaLink.href = WPMR_PFV_I18N.docs_url;
      ctaLink.target = '_blank';
      ctaLink.rel = 'noopener';
      ctaLink.className = 'wpmr-pfv-cta-link';
      ctaLink.textContent = 'Open Help Center';
      cta.appendChild(ctaTitle);
      cta.appendChild(ctaLink);
      container.appendChild(cta);
    }

    // Footer note
    var note = el('div', 'wpmr-pfv-note', 'A full report has been prepared.');
    container.appendChild(note);
  }

  /**
   * Render new comprehensive validation results display
   * @param {HTMLElement} container - Container element
   * @param {Object} data - Transformed validation data
   */
  function renderNewValidationResults(container, data) {
    if (!data) return;

    // Clear container
    container.innerHTML = '';

    // Add inline styles
    var style = document.createElement('style');
    style.textContent = `
      .wpmr-pfv-new-results { max-width: 1200px; margin: 30px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
      .wpmr-pfv-status-banner { padding: 15px 20px; margin-bottom: 30px; border-left: 4px solid; display: flex; align-items: center; gap: 12px; }
      .wpmr-pfv-status-banner.success { background: #d7f4d7; border-color: #46b450; color: #1e4620; }
      .wpmr-pfv-status-banner.warning { background: #fff8e5; border-color: #f0b849; color: #614200; }
      .wpmr-pfv-status-banner.error { background: #fce4e4; border-color: #dc3232; color: #5b1515; }
      .wpmr-pfv-status-icon { font-size: 24px; line-height: 1; }
      .wpmr-pfv-status-message { flex: 1; font-size: 16px; font-weight: 500; }
      .wpmr-pfv-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
      .wpmr-pfv-stat-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
      .wpmr-pfv-stat-number { font-size: 32px; font-weight: bold; color: #2271b1; margin-bottom: 8px; }
      .wpmr-pfv-stat-label { font-size: 14px; color: #646970; }
      .wpmr-pfv-section { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
      .wpmr-pfv-section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid; }
      .wpmr-pfv-section-header.error { border-color: #dc3232; color: #dc3232; }
      .wpmr-pfv-section-header.warning { border-color: #f0b849; color: #946200; }
      .wpmr-pfv-section-header.info { border-color: #2271b1; color: #2271b1; }
      .wpmr-pfv-section-title { font-size: 20px; font-weight: bold; margin: 0; }
      .wpmr-pfv-issue-item { margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #dcdcde; }
      .wpmr-pfv-issue-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
      .wpmr-pfv-issue-title { font-size: 16px; font-weight: bold; margin: 0 0 8px 0; }
      .wpmr-pfv-issue-message { font-size: 14px; color: #646970; margin-bottom: 12px; }
      .wpmr-pfv-affected-products { margin: 12px 0; }
      .wpmr-pfv-affected-products summary { cursor: pointer; font-size: 14px; font-weight: 500; color: #2271b1; padding: 8px 0; }
      .wpmr-pfv-affected-products summary:hover { text-decoration: underline; }
      .wpmr-pfv-product-list { list-style: none; padding: 10px 0 0 0; margin: 0; }
      .wpmr-pfv-product-list li { padding: 6px 0; font-size: 13px; }
      .wpmr-pfv-product-list code { background: #f6f7f7; padding: 2px 6px; border-radius: 3px; font-family: Consolas, Monaco, 'Courier New', monospace; font-size: 12px; }
      .wpmr-pfv-how-to-fix { background: #e5f5fa; border-left: 3px solid #2271b1; padding: 12px 15px; margin-top: 12px; border-radius: 3px; }
      .wpmr-pfv-how-to-fix-title { font-size: 13px; font-weight: bold; color: #2271b1; margin: 0 0 6px 0; }
      .wpmr-pfv-how-to-fix-text { font-size: 13px; color: #1d2327; margin: 0; }
      .wpmr-pfv-tip-item { display: flex; gap: 15px; padding: 15px; background: #f6f7f7; border-radius: 4px; margin-bottom: 12px; }
      .wpmr-pfv-tip-item:last-child { margin-bottom: 0; }
      .wpmr-pfv-tip-icon { font-size: 20px; color: #2271b1; flex-shrink: 0; }
      .wpmr-pfv-tip-content { flex: 1; }
      .wpmr-pfv-tip-title { font-size: 14px; font-weight: 600; margin: 0 0 4px 0; }
      .wpmr-pfv-tip-description { font-size: 13px; color: #646970; margin: 0; }
      .wpmr-pfv-impact-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-left: 8px; }
      .wpmr-pfv-impact-badge.high { background: #dc3232; color: #fff; }
      .wpmr-pfv-impact-badge.medium { background: #f0b849; color: #614200; }
      .wpmr-pfv-impact-badge.low { background: #dcdcde; color: #646970; }
      .wpmr-pfv-export-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
      .wpmr-pfv-export-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #2271b1; color: #fff; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; text-decoration: none; }
      .wpmr-pfv-export-btn:hover { background: #135e96; }
      .wpmr-pfv-export-btn:disabled { background: #dcdcde; color: #a7aaad; cursor: not-allowed; }
      @media (max-width: 768px) {
        .wpmr-pfv-stats-grid { grid-template-columns: 1fr; }
        .wpmr-pfv-stat-number { font-size: 28px; }
        .wpmr-pfv-section { padding: 15px; }
      }
    `;
    container.appendChild(style);

    // Create wrapper
    var wrapper = el('div', 'wpmr-pfv-new-results');
    
    // 1. Status Banner
    wrapper.appendChild(renderStatusBanner(data.status, data.summary));
    
    // 2. Statistics Dashboard
    wrapper.appendChild(renderStatsDashboard(data));
    
    // 3. Errors Section (if errors exist)
    if (data.errors && data.errors.length > 0) {
      wrapper.appendChild(renderErrorsSection(data.errors));
    }
    
    // 4. Warnings Section (if warnings exist)
    if (data.warnings && data.warnings.length > 0) {
      wrapper.appendChild(renderWarningsSection(data.warnings));
    }
    
    // 5. Improvement Tips (always shown)
    wrapper.appendChild(renderImprovementTips());
    
    // 6. Export Section
    wrapper.appendChild(renderExportSection());
    
    container.appendChild(wrapper);
  }

  /**
   * Render status banner
   */
  function renderStatusBanner(status, summary) {
    var banner = el('div', 'wpmr-pfv-status-banner ' + status);
    var icon = el('span', 'wpmr-pfv-status-icon');
    icon.innerHTML = status === 'success' ? '✓' : (status === 'warning' ? '⚠' : '✕');
    var message = el('div', 'wpmr-pfv-status-message', summary);
    banner.appendChild(icon);
    banner.appendChild(message);
    return banner;
  }

  /**
   * Render statistics dashboard
   */
  function renderStatsDashboard(data) {
    var grid = el('div', 'wpmr-pfv-stats-grid');
    
    var stats = [
      { label: 'Total Products', value: data.total_products },
      { label: 'Errors', value: data.error_count },
      { label: 'Warnings', value: data.warning_count },
      { label: 'Valid Products', value: data.valid_products }
    ];
    
    stats.forEach(function(stat) {
      var card = el('div', 'wpmr-pfv-stat-card');
      var number = el('div', 'wpmr-pfv-stat-number', stat.value.toLocaleString());
      var label = el('div', 'wpmr-pfv-stat-label', stat.label);
      card.appendChild(number);
      card.appendChild(label);
      grid.appendChild(card);
    });
    
    return grid;
  }

  /**
   * Render errors section
   */
  function renderErrorsSection(errors) {
    var section = el('div', 'wpmr-pfv-section');
    var header = el('div', 'wpmr-pfv-section-header error');
    var icon = el('span', '');
    icon.innerHTML = '✕';
    var title = el('h2', 'wpmr-pfv-section-title', 'Critical Errors');
    header.appendChild(icon);
    header.appendChild(title);
    section.appendChild(header);
    
    errors.forEach(function(error) {
      var item = el('div', 'wpmr-pfv-issue-item');
      var itemTitle = el('h3', 'wpmr-pfv-issue-title', error.title);
      var itemMessage = el('p', 'wpmr-pfv-issue-message', error.message);
      item.appendChild(itemTitle);
      item.appendChild(itemMessage);
      
      // Affected products
      if (error.affected_items && error.affected_items.length > 0) {
        var details = document.createElement('details');
        details.className = 'wpmr-pfv-affected-products';
        var summary = document.createElement('summary');
        var count = error.affected_count || error.affected_items.length;
        summary.textContent = 'Affected products (' + count + ')';
        details.appendChild(summary);
        
        var list = el('ul', 'wpmr-pfv-product-list');
        var maxShow = 5;
        error.affected_items.slice(0, maxShow).forEach(function(itemId) {
          var li = el('li');
          var code = document.createElement('code');
          code.textContent = itemId;
          li.appendChild(code);
          list.appendChild(li);
        });
        
        if (error.affected_items.length > maxShow) {
          var more = el('li', '', '... and ' + (error.affected_items.length - maxShow) + ' more');
          list.appendChild(more);
        }
        
        details.appendChild(list);
        item.appendChild(details);
      }
      
      // How to fix
      if (error.how_to_fix) {
        var fixBox = el('div', 'wpmr-pfv-how-to-fix');
        var fixTitle = el('div', 'wpmr-pfv-how-to-fix-title', '💡 How to fix');
        var fixText = el('p', 'wpmr-pfv-how-to-fix-text', error.how_to_fix);
        fixBox.appendChild(fixTitle);
        fixBox.appendChild(fixText);
        item.appendChild(fixBox);
      }
      
      section.appendChild(item);
    });
    
    return section;
  }

  /**
   * Render warnings section
   */
  function renderWarningsSection(warnings) {
    var section = el('div', 'wpmr-pfv-section');
    var header = el('div', 'wpmr-pfv-section-header warning');
    var icon = el('span', '');
    icon.innerHTML = '⚠';
    var title = el('h2', 'wpmr-pfv-section-title', 'Warnings');
    header.appendChild(icon);
    header.appendChild(title);
    section.appendChild(header);
    
    warnings.forEach(function(warning) {
      var item = el('div', 'wpmr-pfv-issue-item');
      var itemTitle = el('h3', 'wpmr-pfv-issue-title', warning.title);
      var itemMessage = el('p', 'wpmr-pfv-issue-message', warning.message);
      item.appendChild(itemTitle);
      item.appendChild(itemMessage);
      
      // Affected products
      if (warning.affected_items && warning.affected_items.length > 0) {
        var details = document.createElement('details');
        details.className = 'wpmr-pfv-affected-products';
        var summary = document.createElement('summary');
        var count = warning.affected_count || warning.affected_items.length;
        summary.textContent = 'Affected products (' + count + ')';
        details.appendChild(summary);
        
        var list = el('ul', 'wpmr-pfv-product-list');
        var maxShow = 5;
        warning.affected_items.slice(0, maxShow).forEach(function(itemId) {
          var li = el('li');
          var code = document.createElement('code');
          code.textContent = itemId;
          li.appendChild(code);
          list.appendChild(li);
        });
        
        if (warning.affected_items.length > maxShow) {
          var more = el('li', '', '... and ' + (warning.affected_items.length - maxShow) + ' more');
          list.appendChild(more);
        }
        
        details.appendChild(list);
        item.appendChild(details);
      }
      
      section.appendChild(item);
    });
    
    return section;
  }

  /**
   * Render improvement tips section
   */
  function renderImprovementTips() {
    var section = el('div', 'wpmr-pfv-section');
    var header = el('div', 'wpmr-pfv-section-header info');
    var icon = el('span', '');
    icon.innerHTML = '💡';
    var title = el('h2', 'wpmr-pfv-section-title', 'Improvement Tips');
    header.appendChild(icon);
    header.appendChild(title);
    section.appendChild(header);
    
    var tips = [
      {
        title: 'Add High-Quality Images',
        description: 'Use images with at least 800x800 pixels for better visibility in Shopping ads.',
        impact: 'high'
      },
      {
        title: 'Include GTIN/MPN Numbers',
        description: 'Products with GTINs or MPNs get higher priority and better matching.',
        impact: 'high'
      },
      {
        title: 'Optimize Product Titles',
        description: 'Include brand, product type, and key attributes in the first 70 characters.',
        impact: 'medium'
      },
      {
        title: 'Add Product Ratings',
        description: 'Include product ratings and review counts to increase click-through rates.',
        impact: 'medium'
      },
      {
        title: 'Use Custom Labels',
        description: 'Leverage custom_label attributes for better campaign segmentation.',
        impact: 'low'
      }
    ];
    
    tips.forEach(function(tip) {
      var item = el('div', 'wpmr-pfv-tip-item');
      var tipIcon = el('div', 'wpmr-pfv-tip-icon', '💡');
      var content = el('div', 'wpmr-pfv-tip-content');
      var tipTitle = el('div', 'wpmr-pfv-tip-title');
      tipTitle.textContent = tip.title;
      var badge = el('span', 'wpmr-pfv-impact-badge ' + tip.impact, tip.impact);
      tipTitle.appendChild(badge);
      var tipDesc = el('p', 'wpmr-pfv-tip-description', tip.description);
      content.appendChild(tipTitle);
      content.appendChild(tipDesc);
      item.appendChild(tipIcon);
      item.appendChild(content);
      section.appendChild(item);
    });
    
    return section;
  }

  /**
   * Render export section
   */
  function renderExportSection() {
    var banner = el('div', 'wpmr-pfv-status-banner success');
    var message = el('div', 'wpmr-pfv-status-message', 'Check your email for the attached CSV file containing the validation report');
    banner.appendChild(message);
    return banner;
  }

  function onSubmit(e){
    e.preventDefault();
    var form = e.target;
    var endpoint = form.getAttribute('data-endpoint');
    var url = qs(form, 'url').value.trim();
    var emailEl = qs(form, 'email');
    var email = emailEl ? emailEl.value.trim() : '';
    var consentEl = qs(form, 'consent');
    var consent = consentEl ? !!consentEl.checked : false;
    var sample = qs(form, 'sample').value === '1';
    var captchaToken = '';
    // Try to read provider-generated hidden inputs directly for simplicity
    var recaptchaInput = qs(form, 'g-recaptcha-response');
    var turnstileInput = qs(form, 'cf-turnstile-response');
    if (recaptchaInput && recaptchaInput.value) captchaToken = recaptchaInput.value;
    else if (turnstileInput && turnstileInput.value) captchaToken = turnstileInput.value;
    var result = form.querySelector('.wpmr-pfv-result');

    // Set loading state with accessibility enhancements
    if (typeof setLoadingState === 'function') {
      setLoadingState(form);
    }
    result.textContent = WPMR_PFV_I18N.validating;

    fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': WPMR_PFV_I18N.rest_nonce || ''
      },
      credentials: 'same-origin',
      body: JSON.stringify({ url: url, email: email, consent: consent, sample: sample, captcha_token: captchaToken })
    })
    .then(function(res){ return res.json().then(function(data){ return { ok: res.ok, data: data }; }); })
    .then(function(r){
      // Reset loading state
      if (typeof resetLoadingState === 'function') {
        resetLoadingState(form);
      }

      if(r.ok){
        // Clear previous results
        result.innerHTML = '';
        
        // Show email notification message
        var emailMsg = el('div', 'wpmr-pfv-email-notice');
        emailMsg.textContent = (r.data && r.data.message) ? r.data.message : WPMR_PFV_I18N.success;
        emailMsg.style.cssText = 'padding: 12px 15px; background: #d7f4d7; border-left: 4px solid #46b450; color: #1e4620; margin-bottom: 20px; border-radius: 3px;';
        result.appendChild(emailMsg);
        
        // If a report is present, render new comprehensive display
        if(r.data && r.data.report){
          // Transform API response to display format
          var transformedData = transformValidationData(r.data);
          
          if (transformedData) {
            // Render new validation results display
            var wrap = el('div', 'wpmr-pfv-new-report-wrap');
            wrap.setAttribute('tabindex','-1');
            result.appendChild(wrap);
            renderNewValidationResults(wrap, transformedData);
            
            // Scroll to results
            setTimeout(function() {
              wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
            
            // Accessibility: move focus to results
            try { wrap.focus({ preventScroll: false }); } catch(e) { try { wrap.focus(); } catch(_){} }
            // Announce to screen readers
            if (typeof announceToScreenReader === 'function') {
              announceToScreenReader('Validation report generated successfully. ' + transformedData.summary, 'assertive');
            }
          } else {
            // Fallback to old rendering if transformation fails
            var wrap = el('div', 'wpmr-pfv-report');
            wrap.setAttribute('tabindex','-1');
            result.appendChild(wrap);
            renderReport(wrap, r.data);
            try { wrap.focus({ preventScroll: false }); } catch(e) { try { wrap.focus(); } catch(_){} }
            if (typeof announceToScreenReader === 'function') {
              announceToScreenReader('Validation report generated successfully.', 'assertive');
            }
          }
        }
        // Remember last submission for Resend
        window.__WPMR_LAST_REQ__ = { endpoint: endpoint, url: url, email: email, consent: consent, sample: sample };
      } else {
        var msg = (r.data && (r.data.message || (r.data.code+': '+(r.data.data && r.data.data.status)))) || WPMR_PFV_I18N.error;
        
        // Handle specific error codes
        if (r.data && r.data.code) {
          switch (r.data.code) {
            case 'wpmr_pfv_rate_limited_ip':
              msg = 'You have exceeded the daily request limit for this IP address. Please try again tomorrow.';
              break;
            case 'wpmr_pfv_rate_limited_email':
              msg = 'You have exceeded the daily request limit for this email address. Please try again tomorrow.';
              break;
            case 'wpmr_pfv_blocked':
              msg = 'Requests from this email address or IP are not allowed. Please contact support if this seems incorrect.';
              break;
            case 'wpmr_pfv_missing_url':
              msg = 'Please provide a valid feed URL.';
              break;
            case 'wpmr_pfv_invalid_email':
              msg = 'Please provide a valid email address.';
              break;
            case 'wpmr_pfv_missing_consent':
              msg = 'You must consent to receive the validation report.';
              break;
          }
        }
        
        result.textContent = msg;
        // Announce error to screen readers
        if (typeof announceToScreenReader === 'function') {
          announceToScreenReader('Validation failed: ' + msg, 'assertive');
        }
      }
    })
    .catch(function(err){
      // Reset loading state on error
      if (typeof resetLoadingState === 'function') {
        resetLoadingState(form);
      }
      result.textContent = WPMR_PFV_I18N.error;
      // Announce error to screen readers
      if (typeof announceToScreenReader === 'function') {
        announceToScreenReader('Network error occurred. Please try again.', 'assertive');
      }
    });
  }

  document.addEventListener('submit', function(e){
    if(e.target && e.target.classList.contains('wpmr-pfv-form')){
      onSubmit(e);
    }
  });
})();
