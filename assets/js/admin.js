/* SmartCampus - admin actions (resources, bookings, users, complaints, announcements) */

/* ---------- Resources CRUD ---------- */
function openResourceModal(data) {
  document.getElementById('resModalTitle').textContent = data ? 'Edit Resource' : 'Add Resource';
  document.getElementById('resId').value = data ? data.id : '';
  document.getElementById('resName').value = data ? data.name : '';
  document.getElementById('resType').value = data ? data.type : 'classroom';
  document.getElementById('resCapacity').value = data ? data.capacity : '';
  document.getElementById('resLocation').value = data ? data.location : '';
  document.getElementById('resDescription').value = data ? data.description || '' : '';
  document.getElementById('resFacilities').value = data ? data.facilities || '' : '';
  document.getElementById('resStatus').value = data ? data.status : 'active';
  document.getElementById('resBookableBy').value = data ? (data.bookable_by || 'all') : 'all';
}

async function saveResource() {
  const form = document.getElementById('resourceForm');
  const id = document.getElementById('resId').value;
  const body = formToObj(form);
  if (!form.reportValidity()) return;
  const btn = document.getElementById('resSaveBtn');
  setLoading(btn, true, 'Saving...');
  try {
    body.action = id ? 'update' : 'create';
    if (id) body.id = id;
    body.capacity = +body.capacity;
    const data = await scApi(scUrl('api/resource_admin.php'), { method: 'POST', body: JSON.stringify(body) });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 800);
  } catch (err) {
    scToast('danger', err.message || err.errors?.join(' '));
  } finally { setLoading(btn, false); }
}

async function toggleResource(id) {
  try {
    const data = await scApi(scUrl('api/resource_admin.php'), { method: 'POST', body: JSON.stringify({ action: 'toggle', id }) });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 700);
  } catch (err) { scToast('danger', err.message); }
}

async function deleteResource(id) {
  if (!confirm('Delete this resource? This will also remove its bookings and usage history.')) return;
  try {
    const data = await scApi(scUrl('api/resource_admin.php'), { method: 'POST', body: JSON.stringify({ action: 'delete', id }) });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 700);
  } catch (err) { scToast('danger', err.message); }
}

/* ---------- Bookings management ---------- */
async function setBookingStatus(id, status) {
  if (!confirm(`Mark booking #${id} as ${status}?`)) return;
  try {
    const data = await scApi(scUrl('api/bookings.php'), { method: 'POST', body: JSON.stringify({ action: 'status', booking_id: id, status }) });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 700);
  } catch (err) { scToast('danger', err.message); }
}

async function bulkStatus(status) {
  const checks = [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
  if (!checks.length) { scToast('warning', 'Select at least one booking.'); return; }
  if (!confirm(`Apply "${status}" to ${checks.length} selected booking(s)?`)) return;
  let ok = 0;
  for (const id of checks) {
    try {
      await scApi(scUrl('api/bookings.php'), { method: 'POST', body: JSON.stringify({ action: 'status', booking_id: +id, status }) });
      ok++;
    } catch (e) { /* keep going */ }
  }
  scToast('success', `Updated ${ok} booking(s).`);
  setTimeout(() => location.reload(), 700);
}

/* ---------- Users ---------- */
function openUserModal(data) {
  document.getElementById('userModalTitle').textContent = data ? 'Edit User' : 'Add User';
  document.getElementById('userId').value = data ? data.id : '';
  document.getElementById('userName').value = data ? data.name : '';
  document.getElementById('userEmail').value = data ? data.email : '';
  document.getElementById('userRole').value = data ? data.role : 'student';
  document.getElementById('userDept').value = data ? data.department || '' : '';
  document.getElementById('userIdentifier').value = data ? data.identifier || '' : '';
  toggleAdminWarning();
}

function toggleAdminWarning() {
  const warn = document.getElementById('adminWarn');
  if (warn) warn.classList.toggle('d-none', document.getElementById('userRole').value !== 'admin');
}

async function saveUser() {
  const id = document.getElementById('userId').value;
  if (id) {
    const body = {
      action: 'update',
      user_id: +id,
      name: document.getElementById('userName').value.trim(),
      department: document.getElementById('userDept').value.trim(),
      identifier: document.getElementById('userIdentifier').value.trim(),
    };
    if (!body.name) { scToast('warning', 'Name is required.'); return; }
    try {
      const data = await scApi(scUrl('api/users.php'), { method: 'POST', body: JSON.stringify(body) });
      scToast('success', data.message);
      setTimeout(() => location.reload(), 700);
    } catch (err) { scToast('danger', err.message); }
  } else {
    // New user: create via admin with temporary password
    const name = document.getElementById('userName').value.trim();
    const email = document.getElementById('userEmail').value.trim();
    const role = document.getElementById('userRole').value;
    if (!name || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { scToast('warning', 'Name and valid email are required.'); return; }
    const tempPass = prompt('Set a temporary password for this user (min 8 characters):', 'Password123!');
    if (!tempPass || tempPass.length < 8) { scToast('warning', 'Password must be at least 8 characters.'); return; }
    try {
      const data = await scApi(scUrl('api/users.php'), {
        method: 'POST',
        body: JSON.stringify({
          action: 'create',
          name,
          email,
          role,
          department: document.getElementById('userDept').value.trim(),
          identifier: document.getElementById('userIdentifier').value.trim(),
          password: tempPass,
        }),
      });
      scToast('success', data.message);
      setTimeout(() => location.reload(), 700);
    } catch (err) { scToast('danger', err.message); }
  }
}

async function toggleUser(id) {
  try {
    const data = await scApi(scUrl('api/users.php'), { method: 'POST', body: JSON.stringify({ action: 'toggle', user_id: id }) });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 700);
  } catch (err) { scToast('danger', err.message); }
}

/* ---------- Complaints ---------- */
async function setComplaintStatus(id, status) {
  try {
    const data = await scApi(scUrl('api/complaints.php'), { method: 'POST', body: JSON.stringify({ action: 'status', complaint_id: id, status }) });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 700);
  } catch (err) { scToast('danger', err.message); }
}

/* ---------- Announcements ---------- */
function openAnnModal(data) {
  document.getElementById('annModalTitle').textContent = data ? 'Edit Announcement' : 'New Announcement';
  document.getElementById('annId').value = data ? data.id : '';
  document.getElementById('annTitle').value = data ? data.title : '';
  document.getElementById('annMessage').value = data ? data.message : '';
  document.getElementById('annStatus').value = data ? data.status : 'published';
}

async function saveAnnouncement() {
  const id = document.getElementById('annId').value;
  const body = {
    title: document.getElementById('annTitle').value.trim(),
    message: document.getElementById('annMessage').value.trim(),
    status: document.getElementById('annStatus').value,
  };
  if (!body.title || !body.message) { scToast('warning', 'Title and message are required.'); return; }
  try {
    const data = await scApi(scUrl('api/announcements.php'), {
      method: 'POST',
      body: JSON.stringify({ ...body, action: id ? 'update' : 'create', id }),
    });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 800);
  } catch (err) { scToast('danger', err.message); }
}

async function deleteAnnouncement(id) {
  if (!confirm('Delete this announcement?')) return;
  try {
    const data = await scApi(scUrl('api/announcements.php'), { method: 'POST', body: JSON.stringify({ action: 'delete', id }) });
    scToast('success', data.message);
    setTimeout(() => location.reload(), 700);
  } catch (err) { scToast('danger', err.message); }
}

/* ---------- Table search filters ---------- */
document.addEventListener('DOMContentLoaded', () => {
  // Resource table client filter
  const resSearch = document.getElementById('resSearch');
  const resType = document.getElementById('resTypeFilter');
  const resTable = document.getElementById('resourceTable');
  if (resTable && resSearch) {
    const filter = () => {
      const q = resSearch.value.toLowerCase();
      const t = resType ? resType.value : '';
      resTable.querySelectorAll('tbody tr').forEach(tr => {
        const text = tr.textContent.toLowerCase();
        const type = tr.dataset.type || '';
        tr.style.display = (text.includes(q) && (!t || type === t)) ? '' : 'none';
      });
    };
    resSearch.addEventListener('input', filter);
    if (resType) resType.addEventListener('change', filter);
  }

  // Bookings select-all
  const selectAll = document.getElementById('selectAll');
  if (selectAll) {
    selectAll.addEventListener('change', () => {
      document.querySelectorAll('.row-check').forEach(c => c.checked = selectAll.checked);
    });
  }
});
