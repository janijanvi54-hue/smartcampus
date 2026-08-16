/* SmartCampus - resources grid, resource details, bookings, notifications, complaints */

/* ---------- Available resources page ---------- */
async function loadResources() {
  const grid = document.getElementById('resourceGrid');
  if (!grid) return;
  const params = new URLSearchParams({
    type: document.getElementById('fType')?.value || '',
    search: document.getElementById('fSearch')?.value || '',
    capacity: document.getElementById('fCap')?.value || '',
    status: document.getElementById('fStatus')?.value || '',
    date: document.getElementById('fDate')?.value || '',
    start: document.getElementById('fStart')?.value || '08:00',
    end: document.getElementById('fEnd')?.value || '21:00',
    limit: 200,
  });
  try {
    const data = await scApi(scUrl('api/resources.php?' + params.toString()));
    renderResourceGrid(data.resources);
  } catch (err) {
    grid.innerHTML = `<div class="col-12 text-center text-muted py-5"><i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>${escHtml(err.message)}</div>`;
  }
}

function renderResourceGrid(resources) {
  const grid = document.getElementById('resourceGrid');
  if (!resources.length) {
    grid.innerHTML = `<div class="col-12 text-center text-muted py-5"><i class="bi bi-search fs-1 d-block mb-2"></i>No resources match your filters.</div>`;
    return;
  }
  const dotColor = { success: 'success', warning: 'warning', danger: 'danger', info: 'info' };
  const barColor = { success: '#16a34a', warning: '#f59e0b', danger: '#dc2626', info: '#0ea5e9' };
  grid.innerHTML = resources.map(r => `
    <div class="col-md-6 col-xl-4">
      <div class="sc-resource-card">
        <div class="rc-head d-flex justify-content-between align-items-start gap-2">
          <div>
            <h6 class="fw-bold mb-0">${escHtml(r.name)}</h6>
            <small class="text-muted">${escHtml(r.type_label)}</small>
          </div>
          <span class="badge text-bg-${r.available ? 'success' : 'secondary'}">${r.available ? 'Available' : 'Occupied'}</span>
        </div>
        <div class="rc-body">
          <div class="d-flex gap-2 flex-wrap small text-muted mb-2">
            <span><i class="bi bi-geo-alt me-1"></i>${escHtml(r.location)}</span>
            <span><i class="bi bi-people me-1"></i>${r.capacity}</span>
          </div>
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">Utilisation</span>
            <strong class="text-${dotColor[r.util_class]}"><span class="sc-dot dot-${dotColor[r.util_class]}"></span>${r.util_label} ${r.avg_utilization}%</strong>
          </div>
          <div class="util-bar mb-3"><span style="width:${Math.min(100, r.avg_utilization)}%;background:${barColor[r.util_class]}"></span></div>
          <a class="btn btn-sm btn-primary w-100" href="resource-details.php?id=${r.id}"><i class="bi bi-calendar-plus me-1"></i>View &amp; Book</a>
        </div>
      </div>
    </div>`).join('');
}

function bindResourceFilters() {
  ['fType', 'fSearch', 'fCap', 'fStatus', 'fDate', 'fStart', 'fEnd'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', loadResources);
  });
  const search = document.getElementById('fSearch');
  if (search) {
    let t;
    search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(loadResources, 350); });
  }
}

/* ---------- Resource details / booking page ---------- */
function loadTimeSlots() {
  const slotGrid = document.getElementById('slotGrid');
  if (!slotGrid) return;
  const id = document.getElementById('bkResourceId')?.value;
  const date = document.getElementById('bkDate')?.value;
  if (!id) return;
  scApi(`api/resource.php?id=${id}&date=${date}`)
    .then(data => {
      slotGrid.innerHTML = data.resource.slots.map(s =>
        `<button type="button" class="btn btn-sm ${s.available ? 'btn-outline-success' : 'btn-outline-secondary disabled'}" data-start="${s.start}" data-end="${s.end}" onclick="pickSlot('${s.start}','${s.end}')">${s.label}</button>`
      ).join('') || '<span class="text-muted small">No slots available.</span>';
    })
    .catch(() => { slotGrid.innerHTML = '<span class="text-muted small">Could not load slots.</span>'; });
}

function pickSlot(start, end) {
  document.getElementById('bkStart').value = start;
  document.getElementById('bkEnd').value = end;
  scToast('info', `Slot ${start} - ${end} selected. Check availability to confirm.`);
}

async function checkBooking() {
  const body = {
    resource_id: +document.getElementById('bkResourceId').value,
    date: document.getElementById('bkDate').value,
    start: document.getElementById('bkStart').value,
    end: document.getElementById('bkEnd').value,
    expected_users: +document.getElementById('bkUsers').value,
  };
  const feedback = document.getElementById('bkFeedback');
  const btn = document.getElementById('bkCheckBtn');
  setLoading(btn, true, 'Checking...');
  try {
    const data = await scApi(scUrl('api/availability.php'), { method: 'POST', body: JSON.stringify(body) });
    if (data.available) {
      feedback.innerHTML = `<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i>${escHtml(data.message)} Expected utilisation: <strong>${data.expected_utilization}%</strong>.</div>`;
      document.getElementById('bkSubmitBtn').disabled = false;
    } else {
      feedback.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-x-octagon me-1"></i>${escHtml(data.message)}</div>`;
      document.getElementById('bkSubmitBtn').disabled = true;
    }
  } catch (err) {
    feedback.innerHTML = `<div class="alert alert-danger py-2 small">${escHtml(err.message)}</div>`;
    document.getElementById('bkSubmitBtn').disabled = true;
  } finally {
    setLoading(btn, false);
  }
}

async function submitBooking() {
  const body = {
    action: 'create',
    resource_id: +document.getElementById('bkResourceId').value,
    date: document.getElementById('bkDate').value,
    start: document.getElementById('bkStart').value,
    end: document.getElementById('bkEnd').value,
    expected_users: +document.getElementById('bkUsers').value,
    purpose: document.getElementById('bkPurpose').value.trim(),
  };
  if (!body.purpose) { scToast('warning', 'Please provide a purpose for the booking.'); return; }
  const btn = document.getElementById('bkSubmitBtn');
  setLoading(btn, true, 'Submitting...');
  try {
    const data = await scApi(scUrl('api/bookings.php'), { method: 'POST', body: JSON.stringify(body) });
    scToast('success', data.message);
    document.getElementById('bkPurpose').value = '';
    document.getElementById('bkSubmitBtn').disabled = true;
    document.getElementById('bkFeedback').innerHTML = '';
    loadTimeSlots();
  } catch (err) {
    scToast('danger', err.message || err.errors?.join(' '));
  } finally {
    setLoading(btn, false);
  }
}

/* ---------- Booking cancellation (used across pages) ---------- */
async function cancelBooking(id) {
  if (!confirm('Cancel this booking?')) return;
  try {
    const data = await scApi(scUrl('api/bookings.php'), { method: 'POST', body: JSON.stringify({ action: 'cancel', booking_id: id }) });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 900);
  } catch (err) {
    scToast('danger', err.message);
  }
}

/* ---------- Notifications ---------- */
async function markRead(id) {
  try {
    await scApi(scUrl('api/notifications.php'), { method: 'POST', body: JSON.stringify({ action: 'read', notification_id: id }) });
    location.reload();
  } catch (err) { scToast('danger', err.message); }
}

async function markAllRead() {
  try {
    await scApi(scUrl('api/notifications.php'), { method: 'POST', body: JSON.stringify({ action: 'read_all' }) });
    location.reload();
  } catch (err) { scToast('danger', err.message); }
}

/* ---------- Report a problem ---------- */
async function loadMyComplaints() {
  const wrap = document.getElementById('myComplaints');
  if (!wrap) return;
  try {
    const data = await scApi(scUrl('api/complaints.php?scope=mine&limit=20'));
    if (!data.complaints.length) {
      wrap.innerHTML = '<p class="text-muted small text-center py-4">No reports yet.</p>';
      return;
    }
    const cls = { reported: 'warning', in_progress: 'info', resolved: 'success' };
    wrap.innerHTML = data.complaints.map(c => `
      <div class="d-flex gap-2 mb-3 border-bottom pb-2">
        <i class="bi bi-tools text-danger"></i>
        <div class="flex-grow-1 small">
          <div class="fw-semibold">${escHtml(c.category)} <span class="badge text-bg-${cls[c.status]}">${escHtml(c.status.replace('_', ' '))}</span></div>
          <div class="text-muted">${escHtml(c.resource_name || 'General')} &middot; ${escHtml(c.priority)} priority</div>
          <div class="text-muted">${escHtml(c.description)}</div>
          <div class="text-muted" style="font-size:.72rem">${escHtml(new Date(c.created_at.replace(' ', 'T')).toLocaleString())}</div>
        </div>
      </div>`).join('');
  } catch (err) {
    wrap.innerHTML = `<p class="text-muted small">${escHtml(err.message)}</p>`;
  }
}

async function submitComplaint(ev) {
  ev.preventDefault();
  const form = document.getElementById('complaintForm');
  const btn = document.getElementById('complaintBtn');
  if (!form.reportValidity()) return;
  const body = formToObj(form);
  delete body.attachment;
  body.resource_id = +body.resource_id;
  setLoading(btn, true, 'Submitting...');
  try {
    const data = await scApi(scUrl('api/complaints.php'), { method: 'POST', body: JSON.stringify({ ...body, action: 'create' }) });
    scToast('success', data.message);
    form.reset();
    loadMyComplaints();
  } catch (err) {
    scToast('danger', err.message);
  } finally {
    setLoading(btn, false);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  bindResourceFilters();
  if (document.getElementById('resourceGrid')) loadResources();
  if (document.getElementById('bkCheckBtn')) {
    document.getElementById('bkCheckBtn').addEventListener('click', checkBooking);
    document.getElementById('bkSubmitBtn').addEventListener('click', submitBooking);
    ['bkDate', 'bkStart', 'bkEnd'].forEach(id => document.getElementById(id).addEventListener('change', () => {
      document.getElementById('bkSubmitBtn').disabled = true;
      document.getElementById('bkFeedback').innerHTML = '';
      loadTimeSlots();
    }));
    loadTimeSlots();
  }
  const complaintForm = document.getElementById('complaintForm');
  if (complaintForm) complaintForm.addEventListener('submit', submitComplaint);
  loadMyComplaints();
});
