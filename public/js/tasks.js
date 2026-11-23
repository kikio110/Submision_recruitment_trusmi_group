/* Dashboard 2 - Task Deadline Monitoring.
 * Satu request /api/tasks -> satu objek data -> semua komponen dirender dari objek yang sama.
 * Chart.js: instance dibuat sekali, filter hanya memanggil chart.update(). */
(() => {
  const $ = id => document.getElementById(id);
  const { num, pctRaw, esc, initials, date } = Fmt;
  const state = { employee: 'All', status: 'ALL', search: '', sort: 'asc', page: 1, perPage: 8, data: null };
  const charts = {};
  const C = { green: '#006a61', red: '#ba1a1a', grid: '#e5eeff' };
  let seq = 0;   // mencegah respons lama menimpa respons baru

  /* ---------- Util ---------- */
  const badge = s => ({
    ONTIME: '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-label-sm text-label-sm font-bold bg-secondary-container text-on-secondary-fixed">ONTIME</span>',
    LATE: '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-label-sm text-label-sm font-bold bg-error-container text-on-error-container">LATE</span>',
  }[s] || '<span class="inline-flex px-2 py-0.5 rounded-full font-label-sm text-label-sm font-bold bg-surface-container-high text-on-surface-variant">INVALID</span>');
  const diffText = n => n == null ? '\u2014' : n === 0 ? '0 days (On date)' : n < 0 ? `${n} day${n < -1 ? 's' : ''} (Early)` : `+${n} day${n > 1 ? 's' : ''} (Overdue)`;
  const diffCls = n => n == null ? 'text-on-surface-variant' : n > 0 ? 'text-error font-semibold' : 'text-secondary';
  const perfBadge = s => {
    const m = { 'Excellent': 'bg-secondary-container text-on-secondary-fixed', 'Good': 'bg-primary-fixed text-on-primary-fixed-variant',
                'Needs Improvement': 'bg-error-container text-on-error-container', 'No Data': 'bg-surface-container text-on-surface-variant' };
    return `<span class="inline-flex px-2.5 py-1 rounded-full font-label-sm text-label-sm font-bold ${m[s]}">${s}</span>`;
  };

  /* ---------- Summary cards ---------- */
  function renderSummaryCards(d) {
    const s = d.summary, n = s.total_tasklist;
    const cards = [
      { label: 'Total Tasklists', value: n, unit: 'Tasks', icon: 'assignment', ib: 'bg-primary-fixed text-primary-container',
        sub: `${state.employee === 'All' ? d.by_employee.length + ' karyawan' : esc(state.employee)} \u00b7 status: ${state.status}` },
      { label: 'Total Ontime', value: s.total_ontime, unit: 'Tasks', icon: 'check_circle', ib: 'bg-secondary-container text-on-secondary-container', cls: 'text-secondary',
        sub: `${pctRaw(s.persentase_ontime)} of total` },
      { label: 'Total Late', value: s.total_late, unit: 'Tasks', icon: 'warning', ib: 'bg-error-container text-on-error-container', cls: 'text-error',
        sub: `${pctRaw(s.persentase_late)} late rate` },
      { label: 'Overall Ontime Rate', value: pctRaw(s.persentase_ontime), icon: 'speed', ib: 'bg-surface-container-highest text-primary-container',
        sub: s.total_invalid ? `${s.total_invalid} tasklist invalid` : `${s.total_ontime} dari ${n} tasklist` },
    ];
    $('summary-cards').innerHTML = cards.map(c => `
      <div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
          <span class="font-label-sm text-label-sm uppercase tracking-wider ${c.cls ?? 'text-on-surface-variant'}">${c.label}</span>
          <div class="w-9 h-9 rounded-lg ${c.ib} flex items-center justify-center"><span class="material-symbols-outlined text-lg">${c.icon}</span></div>
        </div>
        <div class="mt-1 font-headline-xl-mobile sm:font-headline-xl text-headline-xl-mobile sm:text-headline-xl tabular-nums ${c.cls ?? 'text-on-surface'}">${c.value}<span class="text-body-md font-medium text-on-surface-variant"> ${c.unit ?? ''}</span></div>
        <div class="mt-space-md font-body-sm text-body-sm text-on-surface-variant">${c.sub}</div>
      </div>`).join('');

    const v = d.validation, ok = v.counts_add_up && v.percent_is_100 && v.single_status;
    $('validation-badge').innerHTML = `<span class="inline-flex items-center gap-1 px-space-sm py-space-xs rounded-lg font-label-md text-label-md font-semibold ${ok ? 'bg-secondary-container/50 text-on-secondary-fixed' : 'bg-error-container text-on-error-container'}">
      <span class="material-symbols-outlined text-sm">${ok ? 'verified' : 'error'}</span>${ok ? 'Data validation passed' : 'Data validation FAILED'}${v.invalid_tasks ? ` \u00b7 ${v.invalid_tasks} invalid` : ''}</span>`;
  }

  /* ---------- Charts ---------- */
  const centerText = {
    id: 'centerText',
    afterDraw(chart) {
      const { ctx, chartArea: a } = chart, t = chart.options.plugins.centerText;
      if (!t) return;
      const x = (a.left + a.right) / 2, y = (a.top + a.bottom) / 2;
      ctx.save(); ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      ctx.fillStyle = '#0b1c30'; ctx.font = '700 32px Inter, sans-serif'; ctx.fillText(t.total, x, y - 8);
      ctx.fillStyle = '#45464d'; ctx.font = '600 11px Inter, sans-serif'; ctx.fillText('TOTAL TASKS', x, y + 16);
      ctx.restore();
    },
  };

  function initCharts() {
    charts.donut = new Chart($('chart-donut'), {
      type: 'doughnut',
      data: { labels: ['ONTIME', 'LATE'], datasets: [{ data: [0, 0], backgroundColor: [C.green, C.red], borderWidth: 2, borderColor: '#fff' }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false }, centerText: { total: 0 } } },
      plugins: [centerText],
    });
    charts.emp = new Chart($('chart-employee'), {
      type: 'bar',
      data: { labels: [], datasets: [
        { label: 'ONTIME', data: [], backgroundColor: C.green, borderRadius: 3 },
        { label: 'LATE', data: [], backgroundColor: C.red, borderRadius: 3 } ] },
      options: { responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 }, grid: { color: C.grid } }, x: { grid: { display: false } } },
        plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } } },
    });
  }

  function renderDonutChart(d) {
    const s = d.summary;
    charts.donut.data.datasets[0].data = [s.total_ontime, s.total_late];
    charts.donut.options.plugins.centerText.total = s.total_tasklist;
    charts.donut.update();
    $('donut-legend').innerHTML = [['ONTIME Deliveries', s.total_ontime, s.persentase_ontime, C.green, 'bg-secondary-container/40'],
                                   ['LATE Submissions', s.total_late, s.persentase_late, C.red, 'bg-error-container/60']]
      .map(([l, n, p, col, bg]) => `<div class="flex items-center justify-between p-space-sm rounded-lg ${bg}">
        <span class="flex items-center gap-2 font-label-md text-label-md"><span class="w-2.5 h-2.5 rounded-full" style="background:${col}"></span>${l}</span>
        <span class="font-label-md text-label-md font-semibold tabular-nums">${n} tasks &middot; ${pctRaw(p)}</span></div>`).join('');
  }

  function renderEmployeeChart(d) {
    charts.emp.data.labels = d.by_employee.map(e => e.employee);
    charts.emp.data.datasets[0].data = d.by_employee.map(e => e.total_ontime);
    charts.emp.data.datasets[1].data = d.by_employee.map(e => e.total_late);
    charts.emp.update();
  }

  /* ---------- Tables ---------- */
  function renderEmployeeTable(d) {
    $('emp-tbody').innerHTML = d.by_employee.map(e => `
      <tr class="hover:bg-surface-container-low/70">
        <td class="py-3 px-space-md whitespace-nowrap"><div class="flex items-center gap-space-sm">
          <span class="w-8 h-8 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm flex items-center justify-center font-bold">${esc(initials(e.employee))}</span>
          <span class="font-label-lg text-label-lg font-bold">${esc(e.employee)}</span></div></td>
        <td class="py-3 px-space-sm text-right tabular-nums">${e.total_tasklist}</td>
        <td class="py-3 px-space-sm text-right tabular-nums text-secondary font-semibold">${e.total_ontime}</td>
        <td class="py-3 px-space-sm text-right tabular-nums text-error font-semibold">${e.total_late}</td>
        <td class="py-3 px-space-sm"><div class="flex items-center gap-2"><div class="w-24 h-2 bg-surface-container-highest rounded-full overflow-hidden">
          <div class="h-2 rounded-full ${e.persentase_ontime >= 75 ? 'bg-secondary' : 'bg-error'}" style="width:${Math.min(100, e.persentase_ontime)}%"></div></div>
          <span class="tabular-nums font-semibold">${pctRaw(e.persentase_ontime, 2)}</span></div></td>
        <td class="py-3 px-space-sm text-right tabular-nums text-error">${pctRaw(e.persentase_late, 2)}</td>
        <td class="py-3 px-space-md text-center">${perfBadge(e.performance_status)}</td>
      </tr>`).join('') || '<tr><td colspan="7" class="p-space-lg text-center text-on-surface-variant">Tidak ada data.</td></tr>';
    const s = d.summary;
    $('emp-tfoot').innerHTML = `<tr class="bg-surface-container font-label-md text-label-md font-bold">
      <td class="py-3 px-space-md">Overall</td><td class="px-space-sm text-right tabular-nums">${s.total_tasklist}</td>
      <td class="px-space-sm text-right tabular-nums">${s.total_ontime}</td><td class="px-space-sm text-right tabular-nums">${s.total_late}</td>
      <td class="px-space-sm tabular-nums">${pctRaw(s.persentase_ontime, 2)}</td><td class="px-space-sm text-right tabular-nums">${pctRaw(s.persentase_late, 2)}</td>
      <td class="px-space-md text-center">${perfBadge(s.performance_status)}</td></tr>`;
  }

  function renderTaskTable(d) {
    $('task-tbody').innerHTML = d.tasks.map(t => `
      <tr class="hover:bg-surface-container-low/70 cursor-pointer ${t.status === 'LATE' ? 'bg-error-container/20' : ''}" data-id="${t.id}" tabindex="0">
        <td class="py-3 px-space-md tabular-nums font-mono">${t.id}</td>
        <td class="py-3 px-space-sm font-semibold">${esc(t.tasklist)}</td>
        <td class="py-3 px-space-sm">${esc(t.employee)}</td>
        <td class="py-3 px-space-sm"><span class="px-2 py-0.5 rounded bg-primary-fixed text-on-primary-fixed-variant font-label-sm text-label-sm">${esc(t.kpi)} KPI</span></td>
        <td class="py-3 px-space-sm whitespace-nowrap">${date(t.deadline)}</td>
        <td class="py-3 px-space-sm whitespace-nowrap ${t.status === 'LATE' ? 'text-error font-semibold' : ''}">${date(t.actual)}</td>
        <td class="py-3 px-space-sm">${badge(t.status)}</td>
        <td class="py-3 px-space-md text-right whitespace-nowrap ${diffCls(t.days_diff)}">${diffText(t.days_diff)}</td>
      </tr>`).join('') || '<tr><td colspan="8" class="p-space-lg text-center text-on-surface-variant">Tidak ada tasklist yang cocok.</td></tr>';

    const m = d.meta, from = m.total ? (m.page - 1) * m.per_page + 1 : 0, to = Math.min(m.total, m.page * m.per_page);
    $('page-info').textContent = `Showing ${from}\u2013${to} of ${m.total} tasks`;
    const pages = Array.from({ length: m.last_page }, (_, i) => i + 1);
    $('pager').innerHTML = `<button data-page="${m.page - 1}" class="p-1 rounded hover:bg-surface-container disabled:opacity-30" ${m.page <= 1 ? 'disabled' : ''} aria-label="Prev"><span class="material-symbols-outlined text-base">chevron_left</span></button>`
      + pages.map(p => `<button data-page="${p}" class="px-2.5 py-1 rounded font-semibold ${p === m.page ? 'bg-primary-container text-on-primary' : 'hover:bg-surface-container'}">${p}</button>`).join('')
      + `<button data-page="${m.page + 1}" class="p-1 rounded hover:bg-surface-container disabled:opacity-30" ${m.page >= m.last_page ? 'disabled' : ''} aria-label="Next"><span class="material-symbols-outlined text-base">chevron_right</span></button>`;
    $('sort-icon').textContent = state.sort === 'asc' ? 'arrow_upward' : 'arrow_downward';
  }

  function renderStatusTabs() {
    const items = [['ALL', 'All Tasks'], ['ONTIME', 'Ontime Only'], ['LATE', 'Late Only']];
    $('status-tabs').innerHTML = items.map(([v, l]) => `<button data-status="${v}" class="px-3 py-1.5 rounded-md ${state.status === v ? 'bg-surface-container-lowest font-semibold text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface'}">${l}</button>`).join('');
    $('filter-status').value = state.status;
  }

  /* ---------- Task detail ---------- */
  function openTask(id) {
    const t = state.data.tasks.find(x => x.id === id); if (!t) return;
    const row = (k, v) => `<div class="flex justify-between gap-4 py-1.5 border-b border-surface-container"><dt class="text-on-surface-variant">${k}</dt><dd class="font-semibold text-right">${v}</dd></div>`;
    $('modal-body').innerHTML = row('Tasklist', esc(t.tasklist)) + row('Employee', esc(t.employee)) + row('KPI Category', esc(t.kpi))
      + row('Deadline', date(t.deadline)) + row('Actual', date(t.actual)) + row('Status', badge(t.status))
      + row('Days Difference', `<span class="${diffCls(t.days_diff)}">${diffText(t.days_diff)}</span>`);
    $('task-modal').classList.replace('hidden', 'flex');
    $('modal-close').focus();
  }
  const closeTask = () => $('task-modal').classList.replace('flex', 'hidden');

  /* ---------- Orkestrasi ---------- */
  function updateDashboard() {
    const d = state.data;
    renderSummaryCards(d);
    renderDonutChart(d);
    renderEmployeeChart(d);
    renderEmployeeTable(d);
    renderTaskTable(d);
    renderStatusTabs();
  }

  async function load() {
    const my = ++seq;
    try {
      const d = await api('/api/tasks', { employee: state.employee, status: state.status, search: state.search, sort: state.sort, page: state.page, per_page: state.perPage });
      if (my !== seq) return;
      state.data = d; state.page = d.meta.page;
      updateDashboard();
    } catch (e) {
      $('summary-cards').innerHTML = `<div class="col-span-full p-space-lg bg-error-container text-on-error-container rounded-xl">Gagal memuat data (${esc(e.message)}).</div>`;
    }
  }

  const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

  document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    $('filter-employee').addEventListener('change', e => { state.employee = e.target.value; state.page = 1; load(); });
    $('filter-status').addEventListener('change', e => { state.status = e.target.value; state.page = 1; load(); });
    $('status-tabs').addEventListener('click', e => { const b = e.target.closest('[data-status]'); if (b) { state.status = b.dataset.status; state.page = 1; load(); } });
    $('search').addEventListener('input', debounce(e => { state.search = e.target.value.trim(); state.page = 1; load(); }, 250));
    $('sort-deadline').addEventListener('click', () => { state.sort = state.sort === 'asc' ? 'desc' : 'asc'; state.page = 1; load(); });
    $('pager').addEventListener('click', e => { const b = e.target.closest('[data-page]'); if (b && !b.disabled) { state.page = Number(b.dataset.page); load(); } });
    const open = e => { const tr = e.target.closest('tr[data-id]'); if (tr) openTask(Number(tr.dataset.id)); };
    $('task-tbody').addEventListener('click', open);
    $('task-tbody').addEventListener('keydown', e => { if (e.key === 'Enter') open(e); });
    $('modal-close').addEventListener('click', closeTask);
    $('task-modal').addEventListener('click', e => { if (e.target.id === 'task-modal') closeTask(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTask(); });
    load();
  });
})();
