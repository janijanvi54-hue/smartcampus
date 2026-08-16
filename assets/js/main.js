/* SmartCampus - shared utilities */

/** Absolute URL builder for app paths (handles APP_URL injected by PHP). */
function scUrl(path) {
  const base = (window.APP_URL || '').replace(/\/+$/, '');
  return base + '/' + String(path).replace(/^\//, '');
}

/** Show a toast notification. Types: success, danger, warning, info. */
function scToast(type, message) {
  const icons = {
    success: 'bi-check-circle-fill text-success',
    danger: 'bi-x-octagon-fill text-danger',
    warning: 'bi-exclamation-triangle-fill text-warning',
    info: 'bi-info-circle-fill text-info',
  };
  const icon = icons[type] || icons.info;
  const el = document.createElement('div');
  el.className = 'sc-toast';
  el.style.borderLeftColor = type === 'success' ? '#16a34a'
    : type === 'danger' ? '#dc2626'
    : type === 'warning' ? '#f59e0b' : '#0ea5e9';
  el.innerHTML = `<i class="bi ${icon} fs-5"></i><div class="flex-grow-1">${message}</div>
    <button class="btn-close" style="font-size:.7rem"></button>`;
  document.body.appendChild(el);
  el.querySelector('.btn-close').addEventListener('click', () => el.remove());
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 320); }, 4200);
}

/** Fetch wrapper for the SmartCampus API. */
async function scApi(url, options = {}) {
  const headers = Object.assign({ 'Content-Type': 'application/json' }, options.headers || {});
  const opts = Object.assign({}, options, { headers });
  const res = await fetch(url, opts);
  let data = {};
  try { data = await res.json(); } catch (e) { /* ignore */ }
  if (!res.ok) {
    throw new Error(data.message || data.errors?.join(' ') || 'Request failed');
  }
  return data;
}

/** Serialize a form into a plain object. */
function formToObj(form) {
  const fd = new FormData(form);
  const obj = {};
  fd.forEach((v, k) => { obj[k] = v; });
  return obj;
}

/** Set the disabled state and label of a submit button while loading. */
function setLoading(btn, loading, loadingText = 'Please wait...') {
  if (!btn) return;
  if (loading) {
    btn.dataset.origHtml = btn.innerHTML;
    btn.dataset.origDisabled = btn.disabled;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`;
  } else {
    btn.disabled = btn.dataset.origDisabled === 'true';
    btn.innerHTML = btn.dataset.origHtml || btn.innerHTML;
  }
}

/** Escape a string for safe use in HTML. */
function escHtml(s) {
  const div = document.createElement('div');
  div.textContent = s == null ? '' : String(s);
  return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
  // Auto-dismiss Bootstrap alerts
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(a => {
    setTimeout(() => {
      a.classList.remove('show');
      setTimeout(() => a.remove(), 300);
    }, 5000);
  });

  // Rotating campus photo background on the home hero
  const heroBg = document.getElementById('scHeroBg');
  if (heroBg) {
    const images = ['campus-1.jpg', 'campus-2.jpg', 'campus-4.jpg'].map(f => scUrl('assets/images/campus/' + f));
    let i = 0;
    images.forEach(src => { const img = new Image(); img.src = src; });
    heroBg.style.backgroundImage = `url("${images[0]}")`;
    setInterval(() => {
      i = (i + 1) % images.length;
      heroBg.style.backgroundImage = `url("${images[i]}")`;
    }, 6000);
  }
});
