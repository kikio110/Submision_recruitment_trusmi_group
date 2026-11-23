/* Dashboard 1 - Marketing KPI Performance.
 * Semua angka berasal dari /api/kpi (dihitung di PHP: App\Services\KpiCalculator).
 * JavaScript hanya menampilkan data; chart dibuat SEKALI lalu di-update. */
(() => {
  const $ = id => document.getElementById(id);
  const { num, pct, signed, esc, initials } = Fmt;
  const state = { employee: 'All', detail: null, data: null };
  const charts = {};
  const C = { navy: '#131b2e', teal: '#006a61', red: '#ba1a1a', grid: '#e5eeff' };

  /* ---------- Summary cards ---------- */
  function renderSummaryCards(d) {
    const s = d.summary;
    const cards = [
      { label: 'Total Employees', value: s.total_employees, icon: 'group', bg: 'bg-primary-container text-on-primary',
        sub: `${s.total_tasklists} tasklists (${s.sales_tasklists} Sales / ${s.report_tasklists} Report)`, bg2: 'groups' },
      { label: 'Average KPI Score', value: num(s.avg_total_kpi), suffix: ' / 1.00', icon: 'target', bg: 'bg-secondary text-on-secondary',
        sub: `Top: ${s.top_performer ?? '\u2014'} (${s.top_score != null ? num(s.top_score) : '\u2014'})`, bg2: 'track_changes' },
      { label: 'Avg Sales Achievement', value: pct(s.avg_sales_achievement), icon: 'trending_up', bg: 'bg-surface-container-highest text-primary-container',
        sub: `${s.sales_late} late sales tasklist`, bad: s.sales_late > 0, bg2: 'payments' },
      { label: 'Avg Report Achievement', value: pct(s.avg_report_achievement), icon: 'assignment_turned_in', bg: 'bg-primary text-on-primary',
        sub: `${s.report_late} late report tasklist`, bad: s.report_late > 0, bg2: 'fact_check' },
    ];
    $('summary-cards').innerHTML = cards.map(c => `
      <div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden">
        <div class="absolute -right-3 -bottom-3 text-surface-container opacity-40 pointer-events-none"><span class="material-symbols-outlined text-8xl">${c.bg2}</span></div>
        <div class="flex items-start justify-between relative">
          <div>
            <span class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">${esc(c.label)}</span>
            <div class="font-headline-xl-mobile sm:font-headline-xl text-headline-xl-mobile sm:text-headline-xl text-on-surface mt-1 tabular-nums">${c.value}<span class="text-body-md font-medium text-on-surface-variant">${c.suffix ?? ''}</span></div>
          </div>
          <div class="w-11 h-11 rounded-lg ${c.bg} flex items-center justify-center shadow-sm"><span class="material-symbols-outlined text-xl">${c.icon}</span></div>
        </div>
        <div class="pt-space-md mt-space-md relative font-body-sm text-body-sm ${c.bad ? 'text-error' : 'text-on-surface-variant'}">${esc(c.sub)}</div>
      </div>`).join('');
  }

  /* ---------- Charts ---------- */
  function initCharts() {
    charts.ach = new Chart($('chart-achievement'), {
      data: {
        labels: [],
        datasets: [
          { type: 'bar', label: 'Sales (%)', data: [], backgroundColor: C.navy, borderRadius: 3 },
          { type: 'bar', label: 'Report (%)', data: [], backgroundColor: C.teal, borderRadius: 3 },
          { type: 'line', label: '100% Target', data: [], borderColor: '#76777d', borderDash: [6, 4], borderWidth: 1.5, pointRadius: 0, fill: false },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, suggestedMax: 125, ticks: { callback: v => v + '%' }, grid: { color: C.grid } }, x: { grid: { display: false } } },
        plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } },
                   tooltip: { callbacks: { label: c => ` ${c.dataset.label}: ${num(c.parsed.y, 1)}%` } } },
      },
    });
    charts.total = new Chart($('chart-total'), {
      type: 'bar',
      data: { labels: [], datasets: [{ label: 'Total KPI', data: [], backgroundColor: [], borderRadius: 3 }] },
      options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        scales: { x: { beginAtZero: true, suggestedMax: 1, ticks: { callback: v => num(v) }, grid: { color: C.grid } }, y: { grid: { display: false } } },
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` Total KPI: ${num(c.parsed.x)}` } } },
      },
    });
  }

  const scoreColor = v => (v >= 0.75 ? C.teal : v >= 0.5 ? C.navy : C.red);

  function renderKPICharts(d) {
    const rows = d.rows;
    charts.ach.data.labels = rows.map(r => r.employee);
    charts.ach.data.datasets[0].data = rows.map(r => r.sales_achievement * 100);
    charts.ach.data.datasets[1].data = rows.map(r => r.report_achievement * 100);
    charts.ach.data.datasets[2].data = rows.map(() => 100);
    charts.ach.update();

    const ranked = [...rows].sort((a, b) => b.total_kpi - a.total_kpi);
    charts.total.data.labels = ranked.map(r => r.employee);
    charts.total.data.datasets[0].data = ranked.map(r => r.total_kpi);
    charts.total.data.datasets[0].backgroundColor = ranked.map(r => scoreColor(r.total_kpi));
    charts.total.update();

    const s = d.summary;
    const full = rows.filter(r => r.sales_achievement >= 1 && r.report_achievement >= 1).map(r => r.employee);
    $('insight').innerHTML = rows.length
      ? `<strong>Insight:</strong> ${s.sales_late + s.report_late} tasklist late
         (${s.sales_late} Sales, ${s.report_late} Report) mengurangi skor total sebesar <strong class="text-error">${num(s.total_deduction)}</strong>.
         ${full.length ? 'Target penuh Sales &amp; Report: ' + esc(full.join(', ')) + '.' : 'Belum ada karyawan yang mencapai 100% pada kedua KPI.'}`
      : 'Tidak ada data.';
    $('audit').innerHTML = rows.length
      ? `<strong class="text-secondary uppercase text-label-sm">Top Performer</strong><br>${esc(s.top_performer)} &mdash; ${num(s.top_score)}`
      : 'Tidak ada data.';
  }

  /* ---------- Table ---------- */
  const lateCell = (n, ded) => n > 0
    ? `<span class="inline-flex items-center px-1.5 py-0.5 rounded font-label-sm text-label-sm font-bold text-error bg-error-container">${n} late (&minus;${num(ded)})</span>`
    : '<span class="text-on-surface-variant">0</span>';
  const achBadge = v => `<span class="inline-flex px-2 py-0.5 rounded-full font-label-sm text-label-sm font-semibold ${v >= 1 ? 'bg-secondary-container/40 text-on-secondary-fixed' : 'bg-error-container text-on-error-container'}">${pct(v)}</span>`;

  function renderEmployeeTable(d) {
    $('kpi-tbody').innerHTML = d.rows.map(r => `
      <tr class="hover:bg-surface-container-low/70 transition-colors cursor-pointer" data-emp="${esc(r.employee)}">
        <td class="py-3 px-space-md whitespace-nowrap">
          <button class="flex items-center gap-space-sm text-left" data-emp="${esc(r.employee)}">
            <span class="w-8 h-8 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm flex items-center justify-center font-bold">${esc(initials(r.employee))}</span>
            <span class="font-label-lg text-label-lg font-bold">${esc(r.employee)}</span>
          </button>
        </td>
        <td class="py-3 px-space-sm text-right tabular-nums text-on-surface-variant">${num(r.sales_target, 0)}</td>
        <td class="py-3 px-space-sm text-right tabular-nums font-medium">${r.sales_actual}</td>
        <td class="py-3 px-space-sm text-right">${achBadge(r.sales_achievement)}</td>
        <td class="py-3 px-space-sm text-right tabular-nums">${num(r.sales_base)}</td>
        <td class="py-3 px-space-sm text-right">${lateCell(r.sales_late, r.sales_deduction)}</td>
        <td class="py-3 px-space-sm text-right tabular-nums font-bold bg-surface-container-high/20">${num(r.sales_final)}</td>
        <td class="py-3 px-space-sm text-right tabular-nums text-on-surface-variant">${num(r.report_target, 0)}</td>
        <td class="py-3 px-space-sm text-right tabular-nums font-medium">${r.report_actual}</td>
        <td class="py-3 px-space-sm text-right">${achBadge(r.report_achievement)}</td>
        <td class="py-3 px-space-sm text-right tabular-nums">${num(r.report_base)}</td>
        <td class="py-3 px-space-sm text-right">${lateCell(r.report_late, r.report_deduction)}</td>
        <td class="py-3 px-space-sm text-right tabular-nums font-bold bg-surface-container/60">${num(r.report_final)}</td>
        <td class="py-3 px-space-md text-right tabular-nums font-headline-sm text-headline-sm font-bold bg-surface-container-high/30 ${r.total_kpi >= 0.75 ? 'text-secondary' : r.total_kpi < 0.5 ? 'text-error' : ''}">${num(r.total_kpi)}</td>
      </tr>`).join('') || '<tr><td colspan="14" class="p-space-lg text-center text-on-surface-variant">Tidak ada data.</td></tr>';

    const s = d.summary;
    $('kpi-tfoot').innerHTML = d.rows.length ? `
      <tr class="bg-surface-container font-label-md text-label-md border-t-2 border-surface-container-highest">
        <td class="py-3.5 px-space-md font-bold">${d.rows.length > 1 ? 'DIVISION AVERAGE' : 'AVERAGE'}</td>
        <td></td><td></td><td class="py-3.5 px-space-sm text-right tabular-nums font-bold">${pct(s.avg_sales_achievement)}</td>
        <td class="py-3.5 px-space-sm text-right tabular-nums">${num(s.avg_sales_base)}</td><td class="py-3.5 px-space-sm text-right tabular-nums text-error">${s.sales_late} late</td>
        <td class="py-3.5 px-space-sm text-right tabular-nums font-bold">${num(s.avg_sales_final)}</td>
        <td></td><td></td><td class="py-3.5 px-space-sm text-right tabular-nums font-bold">${pct(s.avg_report_achievement)}</td>
        <td class="py-3.5 px-space-sm text-right tabular-nums">${num(s.avg_report_base)}</td><td class="py-3.5 px-space-sm text-right tabular-nums text-error">${s.report_late} late</td>
        <td class="py-3.5 px-space-sm text-right tabular-nums font-bold">${num(s.avg_report_final)}</td>
        <td class="py-3.5 px-space-md text-right tabular-nums font-bold bg-surface-container-high">${num(s.avg_total_kpi)}</td>
      </tr>` : '';
  }

  /* ---------- Employee detail ---------- */
  function breakdown(title, r, k) {
    return `
      <div class="p-space-md rounded-lg bg-surface-container-low">
        <div class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant mb-2">${title} (bobot ${pct(r[k + '_weight'], 0)})</div>
        <dl class="space-y-1.5 font-body-md text-body-md tabular-nums">
          <div class="flex justify-between"><dt>Achievement (${r[k + '_actual']} / ${num(r[k + '_target'], 0)})</dt><dd>${pct(r[k + '_achievement'])}</dd></div>
          <div class="flex justify-between"><dt>Base weight (${num(r[k + '_achievement'], 2)} &times; ${num(r[k + '_weight'])})</dt><dd>${num(r[k + '_base'])}</dd></div>
          <div class="flex justify-between text-error"><dt>Late deduction (${r[k + '_late']} &times; ${num(r[k + '_penalty'])})</dt><dd>&minus;${num(r[k + '_deduction'])}</dd></div>
          <div class="flex justify-between border-t border-outline-variant pt-1.5 font-bold"><dt>${title} final score</dt><dd>${num(r[k + '_final'])}</dd></div>
        </dl>
      </div>`;
  }

  function renderEmployeeDetail(d) {
    const names = d.rows.map(r => r.employee);
    if (!names.includes(state.detail)) state.detail = names[0] ?? null;
    $('detail-tabs').innerHTML = names.map(n => `
      <button data-detail="${esc(n)}" class="px-3 py-1.5 rounded-md font-label-sm text-label-sm ${n === state.detail ? 'bg-surface-container-lowest font-semibold text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface'}">${esc(n)}</button>`).join('');
    const r = d.rows.find(x => x.employee === state.detail);
    if (!r) { $('employee-detail').innerHTML = '<p class="text-on-surface-variant">Tidak ada data.</p>'; return; }
    const tile = (label, value, cls = '') => `
      <div class="p-space-md rounded-lg bg-surface-container-low">
        <div class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">${label}</div>
        <div class="font-headline-lg-mobile text-headline-lg-mobile font-bold tabular-nums mt-1 ${cls}">${value}</div>
      </div>`;
    $('employee-detail').innerHTML = `
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-space-md">
        ${tile('Total KPI Score', num(r.total_kpi), r.total_kpi >= 0.75 ? 'text-secondary' : r.total_kpi < 0.5 ? 'text-error' : '')}
        ${tile('Sales Achievement', pct(r.sales_achievement))}
        ${tile('Report Achievement', pct(r.report_achievement))}
        ${tile('Sales Late', r.sales_late, r.sales_late ? 'text-error' : '')}
        ${tile('Report Late', r.report_late, r.report_late ? 'text-error' : '')}
      </div>
      <h3 class="font-label-lg text-label-lg font-bold mt-space-lg mb-space-sm">Final Score Breakdown &mdash; ${esc(r.employee)}</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
        ${breakdown('Sales', r, 'sales')}
        ${breakdown('Report', r, 'report')}
        <div class="p-space-md rounded-lg bg-primary-container text-on-primary flex flex-col justify-center">
          <div class="font-label-sm text-label-sm uppercase tracking-wider opacity-80">Total KPI</div>
          <div class="text-headline-xl font-bold tabular-nums">${num(r.total_kpi)}</div>
          <div class="font-body-sm text-body-sm opacity-80 tabular-nums">${num(r.sales_final)} (Sales) + ${num(r.report_final)} (Report)</div>
        </div>
      </div>`;
  }

  /* ---------- Orkestrasi ---------- */
  function updateDashboard() {
    const d = state.data;
    renderSummaryCards(d);
    renderKPICharts(d);
    renderEmployeeTable(d);
    renderEmployeeDetail(d);
    $('period-label').textContent = d.period ? `Deadline: ${Fmt.date(d.period.from)}${d.period.from !== d.period.to ? ' \u2013 ' + Fmt.date(d.period.to) : ''}` : 'No period';
  }

  async function load() {
    document.querySelector('main').classList.add('skeleton');
    try {
      state.data = await api('/api/kpi', { employee: state.employee });
      if (state.employee !== 'All') state.detail = state.employee;
      updateDashboard();
    } catch (e) {
      $('summary-cards').innerHTML = `<div class="col-span-full p-space-lg bg-error-container text-on-error-container rounded-xl">Gagal memuat data (${esc(e.message)}).</div>`;
    } finally {
      document.querySelector('main').classList.remove('skeleton');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    $('filter-employee').addEventListener('change', e => { state.employee = e.target.value; load(); });
    $('kpi-tbody').addEventListener('click', e => {
      const t = e.target.closest('[data-emp]'); if (!t) return;
      state.detail = t.dataset.emp; renderEmployeeDetail(state.data);
      $('employee-detail').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    $('detail-tabs').addEventListener('click', e => {
      const t = e.target.closest('[data-detail]'); if (!t) return;
      state.detail = t.dataset.detail; renderEmployeeDetail(state.data);
    });
    load();
  });
})();
