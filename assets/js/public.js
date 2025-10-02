(function(){
  function qs(form, name){ return form.querySelector('[name="'+name+'"]'); }

  function el(tag, cls, text){
    var e = document.createElement(tag);
    if(cls){ e.className = cls; }
    if(text !== undefined){ e.textContent = String(text); }
    return e;
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
        // Show message first
        result.textContent = (r.data && r.data.message) ? r.data.message : WPMR_PFV_I18N.success;
        // If a report is present, render it below
        if(r.data && r.data.report){
          var wrap = el('div', 'wpmr-pfv-report');
          wrap.setAttribute('tabindex','-1');
          result.appendChild(wrap);
          renderReport(wrap, r.data);
          // Accessibility: move focus to results
          try { wrap.focus({ preventScroll: false }); } catch(e) { try { wrap.focus(); } catch(_){} }
          // Announce to screen readers
          if (typeof announceToScreenReader === 'function') {
            announceToScreenReader('Validation report generated successfully.', 'assertive');
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
