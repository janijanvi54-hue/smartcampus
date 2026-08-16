/* SmartCampus - admin dashboard & analytics charts (Chart.js) */

const CHART_COLORS = {
  navy: '#0b2545',
  gold: '#f0b429',
  blue: '#2563eb',
  purple: '#7c3aed',
  green: '#16a34a',
  amber: '#f59e0b',
  red: '#dc2626',
  cyan: '#0ea5e9',
  pink: '#ec4899',
};

function scChart(ctx, config) {
  return new Chart(ctx, config);
}

/* ---------- Admin dashboard charts ---------- */
document.addEventListener('DOMContentLoaded', () => {
  const root = (document.location.pathname.includes('admin')) ? '..' : '';

  async function load(url) {
    const res = await fetch(url);
    return res.json();
  }

  // Dashboard page charts
  const cType = document.getElementById('chartType');
  if (cType) {
    load(scUrl('api/analytics.php?chart=type')).then(d => {
      scChart(cType, {
        type: 'bar',
        data: {
          labels: d.labels,
          datasets: [{
            label: 'Avg utilisation %',
            data: d.values,
            backgroundColor: ['#2563eb', '#7c3aed', '#16a34a', '#f59e0b', '#0ea5e9', '#dc2626'],
            borderRadius: 8,
          }],
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          scales: { y: { beginAtZero: true, max: 120, title: { display: true, text: '%' } } },
          plugins: { legend: { display: false } },
        },
      });
    });
  }

  const cDaily = document.getElementById('chartDaily');
  if (cDaily) {
    load(scUrl('api/analytics.php?chart=daily')).then(d => {
      scChart(cDaily, {
        type: 'line',
        data: {
          labels: d.labels,
          datasets: [
            { label: 'Total bookings', data: d.total, borderColor: CHART_COLORS.blue, backgroundColor: 'rgba(37,99,235,.1)', fill: true, tension: .35 },
            { label: 'Approved', data: d.approved, borderColor: CHART_COLORS.green, tension: .35 },
            { label: 'Pending', data: d.pending, borderColor: CHART_COLORS.amber, tension: .35, borderDash: [5, 5] },
          ],
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
      });
    });
  }

  const cHourly = document.getElementById('chartHourly');
  if (cHourly) {
    load(scUrl('api/analytics.php?chart=hourly')).then(d => {
      scChart(cHourly, {
        type: 'bar',
        data: {
          labels: d.labels,
          datasets: [
            { label: 'Avg users', data: d.users, backgroundColor: CHART_COLORS.cyan, borderRadius: 6 },
            { label: 'Avg utilisation %', data: d.util, type: 'line', borderColor: CHART_COLORS.gold, backgroundColor: CHART_COLORS.gold, tension: .35 },
          ],
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } },
      });
    });
  }

  const cClassroom = document.getElementById('chartClassroom');
  const cLab = document.getElementById('chartLab');
  const cLibrary = document.getElementById('chartLibrary');
  if (cClassroom) buildResourceChart(cClassroom, 'classroom', 'Classroom');
  if (cLab) buildResourceChart(cLab, 'computer_lab', 'Computer Lab');
  if (cLibrary) buildResourceChart(cLibrary, 'library', 'Library');

  function buildResourceChart(canvas, type, label) {
    load(scUrl('api/analytics.php?chart=resource&type=' + type)).then(d => {
      scChart(canvas, {
        type: 'bar',
        data: {
          labels: d.labels,
          datasets: [{
            label: 'Avg utilisation %',
            data: d.values,
            backgroundColor: d.values.map(v => v > 100 ? CHART_COLORS.red : v > 70 ? CHART_COLORS.amber : v < 30 ? CHART_COLORS.cyan : CHART_COLORS.green),
            borderRadius: 6,
          }],
        },
        options: {
          responsive: true, maintainAspectRatio: false, indexAxis: 'y',
          scales: { x: { beginAtZero: true, max: 130, title: { display: true, text: '%' } } },
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => `${ctx.raw}%` } } },
        },
      });
    });
  }

  const cStatus = document.getElementById('chartStatus');
  if (cStatus) {
    load(scUrl('api/analytics.php?chart=status')).then(d => {
      scChart(cStatus, {
        type: 'doughnut',
        data: {
          labels: d.labels,
          datasets: [{
            data: d.values,
            backgroundColor: [CHART_COLORS.amber, CHART_COLORS.green, CHART_COLORS.red, CHART_COLORS.navy, CHART_COLORS.cyan],
            borderWidth: 2,
          }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } },
      });
    });
  }

  /* ---------- Analytics page panels + charts ---------- */
  const tabLinks = document.querySelectorAll('[data-sc-tab]');
  if (tabLinks.length) {
    tabLinks.forEach(link => {
      link.addEventListener('click', (ev) => {
        ev.preventDefault();
        const panel = link.dataset.scTab;
        tabLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        document.querySelectorAll('.sc-panel').forEach(p => p.classList.add('d-none'));
        const target = document.getElementById('sc-panel-' + panel);
        if (target) target.classList.remove('d-none');
      });
    });
  }

  const cCompare = document.getElementById('chartCompare');
  if (cCompare) {
    load(scUrl('api/analytics.php?chart=type')).then(d => {
      scChart(cCompare, {
        type: 'bar',
        data: {
          labels: d.labels,
          datasets: [{
            label: 'Avg utilisation %',
            data: d.values,
            backgroundColor: ['#2563eb', '#7c3aed', '#16a34a', '#f59e0b', '#0ea5e9', '#dc2626'],
            borderRadius: 8,
          }],
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 120, title: { display: true, text: '%' } } }, plugins: { legend: { display: false } } },
      });
    });
  }

  const cDemand = document.getElementById('chartDemand');
  if (cDemand) {
    load(scUrl('api/analytics.php?chart=hourly')).then(d => {
      scChart(cDemand, {
        type: 'line',
        data: {
          labels: d.labels,
          datasets: [{
            label: 'Avg utilisation % by hour',
            data: d.util,
            borderColor: CHART_COLORS.navy,
            backgroundColor: 'rgba(11,37,69,.12)',
            fill: true,
            tension: .4,
            pointBackgroundColor: CHART_COLORS.gold,
            pointRadius: 5,
          }],
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: '%' } } }, plugins: { legend: { display: false } } },
      });
    });
  }
});
