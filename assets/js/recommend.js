/* SmartCampus - faculty Smart Recommendation wizard */

let lastRequest = null;

async function findBestResource() {
  const btn = document.getElementById('rcFindBtn');
  const body = {
    type: document.getElementById('rcType').value,
    date: document.getElementById('rcDate').value,
    start: document.getElementById('rcStart').value,
    end: document.getElementById('rcEnd').value,
    expected_users: +document.getElementById('rcUsers').value,
    location: document.getElementById('rcLocation').value.trim(),
  };

  if (!body.date || body.start >= body.end) { scToast('warning', 'Please provide a valid date and time range.'); return; }
  if (!body.expected_users || body.expected_users < 1) { scToast('warning', 'Number of students must be at least 1.'); return; }

  setLoading(btn, true, 'Analysing campus resources...');
  const panel = document.getElementById('resultPanel');
  panel.querySelector('.card-header-sc').innerHTML = '<i class="bi bi-stars text-success"></i> Smart Recommendation Results';

  try {
    const data = await scApi(scUrl('api/recommend.php'), { method: 'POST', body: JSON.stringify(body) });
    lastRequest = { ...body, purpose: document.getElementById('rcPurpose').value.trim() };
    renderResults(data);
  } catch (err) {
    panel.querySelector('.p-4').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${escHtml(err.message)}</div>`;
  } finally {
    setLoading(btn, false);
  }
}

function renderResults(data) {
  const panel = document.getElementById('resultPanel');
  const results = data.results;
  const req = data.requested;

  let html = `<div class="row g-3 mb-3">`;

  // Requested resource status
  if (req) {
    const ok = req.available;
    html += `
      <div class="col-md-5">
        <div class="border rounded-3 p-3 h-100 ${ok ? 'border-success' : 'border-danger'}">
          <div class="fw-semibold mb-2">${ok ? '<span class="rec-badge" style="background:#e8f6ee;color:#166534"><i class="bi bi-check-circle"></i>Requested - Available</span>'
                                        : '<span class="rec-badge" style="background:#fee2e2;color:#991b1b"><i class="bi bi-x-octagon"></i>Requested - Not suitable</span>'}</div>
          <div class="small">
            <div><span class="text-muted">Resource:</span> <strong>${escHtml(req.name)}</strong></div>
            <div><span class="text-muted">Capacity:</span> ${req.capacity} users</div>
            <div><span class="text-muted">Expected utilisation:</span> <strong>${req.expected_utilization}%</strong></div>
            <div><span class="text-muted">Current utilisation:</span> ${req.current_utilization}%</div>
            ${!ok ? `<div class="text-danger small mt-2">${(req.reasons || []).map(e => escHtml(e)).join('<br>')}</div>` : ''}
          </div>
        </div>
      </div>`;
  }

  html += `
    <div class="col-md-${req ? '7' : '12'}">
      <div class="border rounded-3 p-3 h-100">
        <div class="fw-semibold mb-2"><span class="rec-badge" style="background:#e8f6ee;color:#166534"><i class="bi bi-stars"></i>Request summary</span></div>
        <div class="small">
          <div><span class="text-muted">Type:</span> ${escHtml(document.getElementById('rcType').selectedOptions[0].text)}</div>
          <div><span class="text-muted">Date:</span> <strong>${escHtml(lastRequest.date)}</strong> (${escHtml(lastRequest.start)} - ${escHtml(lastRequest.end)})</div>
          <div><span class="text-muted">Students:</span> ${lastRequest.expected_users}</div>
          ${lastRequest.location ? `<div><span class="text-muted">Preferred location:</span> ${escHtml(lastRequest.location)}</div>` : ''}
        </div>
      </div>
    </div></div>`;

  if (!results.length) {
    html += `<div class="alert alert-warning"><i class="bi bi-info-circle me-2"></i>No suitable alternatives found for the requested slot. Try a different time or resource type.</div>`;
    panel.querySelector('.p-4').innerHTML = html;
    return;
  }

  html += `<h6 class="fw-bold mb-3"><i class="bi bi-stars text-success me-2"></i>Ranked Alternative Resources</h6><div class="row g-3">`;

  results.forEach((r, i) => {
    const isBest = i === 0;
    const expClass = r.expected_utilization > 100 ? 'text-danger' : (r.expected_utilization > 70 ? 'text-warning' : 'text-success');
    html += `
      <div class="col-md-6 ${isBest ? '' : ''}">
        <div class="sc-card h-100 ${isBest ? 'border-2' : ''}" style="${isBest ? 'border:2px solid var(--sc-gold)' : ''}">
          <div class="p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold mb-0">${i + 1}. ${escHtml(r.resource.name)}</h6>
              ${isBest ? '<span class="rec-badge" style="background:#fef3c7;color:#92400e"><i class="bi bi-trophy"></i>Best Match</span>'
                       : `<span class="badge text-bg-light border">Score ${r.score}</span>`}
            </div>
            <div class="small">
              <div class="mb-1"><i class="bi bi-geo-alt text-muted me-1"></i>${escHtml(r.resource.location)} &middot; <i class="bi bi-people text-muted me-1"></i>Capacity ${r.resource.capacity}</div>
              <div class="d-flex flex-wrap gap-3 mb-2">
                <span><span class="text-muted">Current:</span> <strong>${r.current_utilization}%</strong></span>
                <span><span class="text-muted">Expected:</span> <strong class="${expClass}">${r.expected_utilization}%</strong></span>
              </div>
              <div class="util-bar mb-2"><span style="width:${Math.min(100, r.current_utilization)}%;background:#2563eb"></span></div>
              <ul class="rec-reason text-muted mb-2" style="padding-left:1.1rem;font-size:.8rem">
                ${r.reasons.map(x => `<li><i class="bi bi-check2 text-success me-1"></i>${escHtml(x)}</li>`).join('')}
              </ul>
              ${isBest ? `
                <div class="d-flex gap-2 mt-2">
                  <button class="btn btn-sm btn-success flex-grow-1" onclick="acceptRec(${r.resource.id})"><i class="bi bi-check-lg me-1"></i>Accept Recommendation</button>
                  <button class="btn btn-sm btn-outline-secondary" onclick="scToast('info', 'Choose another alternative from the list.')">Choose Another</button>
                  <button class="btn btn-sm btn-outline-danger" onclick="clearResults()"><i class="bi bi-x-lg"></i></button>
                </div>`
              : `
                <button class="btn btn-sm btn-outline-primary mt-1" onclick="bookDirect(${r.resource.id})"><i class="bi bi-calendar-plus me-1"></i>Book this instead</button>
              `}
            </div>
          </div>
        </div>
      </div>`;
  });

  html += `</div>`;
  panel.querySelector('.p-4').innerHTML = html;
}

async function acceptRec(resourceId) {
  const purpose = lastRequest?.purpose || 'Class / event';
  try {
    const data = await scApi(scUrl('api/bookings.php'), {
      method: 'POST',
      body: JSON.stringify({
        action: 'create',
        resource_id: resourceId,
        date: lastRequest.date,
        start: lastRequest.start,
        end: lastRequest.end,
        expected_users: lastRequest.expected_users,
        purpose,
      }),
    });
    scToast('success', data.message);
    clearResults();
  } catch (err) {
    scToast('danger', err.message);
  }
}

async function bookDirect(resourceId) {
  await acceptRec(resourceId);
}

function clearResults() {
  const panel = document.getElementById('resultPanel');
  panel.querySelector('.p-4').innerHTML = `
    <div class="text-center text-muted py-5">
      <i class="bi bi-magic fs-1 d-block mb-3"></i>
      Enter your requirements and click <strong>Find Best Resource</strong> to see intelligent suggestions.
    </div>`;
}

/* accept recommendation from a stored recommendation row (booking exists) */
async function acceptRecommendation(bookingId, recommendedResourceId) {
  if (!confirm('Accept this recommendation? A new booking will be created for the recommended resource.')) return;
  try {
    const data = await scApi(scUrl('api/bookings.php'), {
      method: 'POST',
      body: JSON.stringify({ action: 'recommend_accept', booking_id: bookingId, resource_id: recommendedResourceId }),
    });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 900);
  } catch (err) {
    scToast('danger', err.message);
  }
}

async function declineRecommendation(recId) {
  try {
    const data = await scApi(scUrl('api/recommend.php'), {
      method: 'POST',
      body: JSON.stringify({ action: 'decline', id: recId }),
    });
    scToast('info', data.message || 'Recommendation declined.');
    setTimeout(() => location.reload(), 700);
  } catch (err) {
    scToast('danger', err.message);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('rcFindBtn');
  if (btn) btn.addEventListener('click', findBestResource);
});
