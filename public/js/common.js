/* Util bersama (format angka, escape HTML, fetch JSON, sidebar mobile). */
window.Fmt = {
  // Pembulatan di sisi tampilan saja; epsilon mencegah 0.735 -> "0.73".
  num(n, d = 2) { return (Math.round((Number(n) + 1e-9) * 10 ** d) / 10 ** d).toFixed(d); },
  pct(ratio, d = 1) { return Fmt.num(ratio * 100, d) + '%'; },       // 0.875 -> 87.5%
  pctRaw(p, d = 1) { return Fmt.num(p, d) + '%'; },                  // 61.54 -> 61.5%
  signed(n, d = 2) { const v = Number(n); return (v > 0 ? '+' : v < 0 ? '\u2212' : '') + Fmt.num(Math.abs(v), d); },
  date(s) {
    if (!s) return '\u2014';
    const [y, m, d] = s.split('-').map(Number);                         // tanpa new Date(string) -> bebas timezone
    return `${String(d).padStart(2, '0')} ${['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][m - 1]} ${y}`;
  },
  esc(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); },
  initials(name) { return String(name).trim().slice(0, 2).toUpperCase(); },
};

window.api = async function (url, params = {}) {
  const q = new URLSearchParams(Object.entries(params).filter(([, v]) => v !== '' && v != null));
  const res = await fetch(`${url}?${q}`, { headers: { Accept: 'application/json' } });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
};

window.addEventListener('DOMContentLoaded', () => {
  const sb = document.getElementById('sidebar'), bd = document.getElementById('sidebar-backdrop');
  const toggle = open => { sb.classList.toggle('-translate-x-full', !open); bd.classList.toggle('hidden', !open); };
  document.getElementById('sidebar-toggle')?.addEventListener('click', () => toggle(sb.classList.contains('-translate-x-full')));
  bd?.addEventListener('click', () => toggle(false));
});
