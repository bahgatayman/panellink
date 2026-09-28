/* ==========================================================================
   Link Space Panel — Design Playground (NOT production code)
   Every concept renders the SAME screens from the SAME data; only the design
   system (tokens + a few concept-level layout choices) differs.
   ========================================================================== */
(() => {
'use strict';

/* ---------------------------------------------------------------- basics */
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
let LANG = 'en';
const L = (en, ar) => (LANG === 'ar' ? ar : en);
const esc = s => String(s).replace(/[&<>"]/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));
const clone = o => JSON.parse(JSON.stringify(o));
const hash = s => { let h = 2166136261; for (const ch of String(s)) { h ^= ch.charCodeAt(0); h = Math.imul(h, 16777619); } return Math.abs(h); };

/* Demo clock: the panel always opens at 1:05 PM and then ticks in real time. */
const T0 = Date.now();
const NOW0 = 13 * 60 + 5;
const nowMin = () => NOW0 + (Date.now() - T0) / 60000;
const TODAY = new Date(); TODAY.setHours(0, 0, 0, 0);
const dayDate = off => { const d = new Date(TODAY); d.setDate(d.getDate() + off); return d; };
const locale = () => (LANG === 'ar' ? 'ar-EG-u-nu-latn' : 'en-GB');
const fmtDate = (d, o) => d.toLocaleDateString(locale(), o);

function fmtTime(min) {
  const h = Math.floor(min / 60) % 24, m = Math.floor(min % 60);
  const h12 = ((h + 11) % 12) + 1;
  return `${h12}:${String(m).padStart(2, '0')} ${h < 12 ? L('AM', 'ص') : L('PM', 'م')}`;
}
function fmtHour(h) { const h12 = ((h + 11) % 12) + 1; return `${h12} ${h < 12 ? L('AM', 'ص') : L('PM', 'م')}`; }
function fmtDur(min) {
  min = Math.max(0, Math.floor(min));
  const h = Math.floor(min / 60), m = min % 60;
  if (LANG === 'ar') return h ? `${h} س ${String(m).padStart(2, '0')} د` : `${m} د`;
  return h ? `${h}h ${String(m).padStart(2, '0')}m` : `${m}m`;
}
function fmtClock(min) {
  const s = Math.max(0, Math.floor(min * 60));
  const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), x = s % 60;
  return `${h}:${String(m).padStart(2, '0')}:${String(x).padStart(2, '0')}`;
}
const fmtHours = h => (LANG === 'ar' ? `${h} س` : `${h}h`);
const money = v => { const n = Math.round(v).toLocaleString('en-US'); return LANG === 'ar' ? `${n} ج.م` : `EGP ${n}`; };

/* ------------------------------------------------------------------ icons */
const I = {
  home: '<path d="M3.5 10.5 12 3.5l8.5 7V20a1 1 0 0 1-1 1H15v-6H9v6H4.5a1 1 0 0 1-1-1z"/>',
  live: '<path d="M3 12h4l3 7 4-14 3 7h4"/>',
  cal: '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
  door: '<path d="M5.5 21V4a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v17M3 21h18M14.5 12.5h.01"/>',
  users: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.5 3.3-5.5 6.5-5.5s5.9 2 6.5 5.5M16 4.8a3.5 3.5 0 0 1 0 6.4M21.5 20c-.4-2.4-1.7-4.1-3.5-5"/>',
  box: '<path d="M20.5 7.5 12 3 3.5 7.5v9L12 21l8.5-4.5zM3.5 7.5 12 12l8.5-4.5M12 12v9"/>',
  money: '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
  gear: '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/>',
  search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  x: '<path d="M6 6l12 12M18 6 6 18"/>',
  chevL: '<path d="m15 18-6-6 6-6"/>',
  chevR: '<path d="m9 18 6-6-6-6"/>',
  chevD: '<path d="m6 9 6 6 6-6"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  check: '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
  alert: '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5h.01"/>',
  trash: '<path d="M4 7h16M10 11v6M14 11v6M5.5 7l1 13a1 1 0 0 0 1 1h9a1 1 0 0 0 1-1l1-13M9 7V4h6v3"/>',
  download: '<path d="M12 4v11M7 10.5l5 5 5-5M4 20h16"/>',
  more: '<circle cx="5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="19" cy="12" r="1.2"/>',
  side: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/>',
  bell: '<path d="M6 16v-5a6 6 0 0 1 12 0v5l1.5 2h-15zM10 20.5a2 2 0 0 0 4 0"/>',
  grid: '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
  list: '<path d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01"/>',
  seat: '<circle cx="12" cy="7" r="3.5"/><path d="M5 21c.8-4 3.6-6.5 7-6.5s6.2 2.5 7 6.5"/>',
  edit: '<path d="M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4"/>',
  play: '<path d="M7.5 5v14l11-7z"/>',
  receipt: '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6"/>',
  printer: '<path d="M7 9V3.5h10V9M7 17H4v-7.5h16V17h-3M7 14h10v7H7z"/>',
  logout: '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
  cup: '<path d="M4.5 8h12v5a6 6 0 0 1-6 6 6 6 0 0 1-6-6zM16.5 9.5H18a2.5 2.5 0 0 1 0 5h-1.5"/>',
  arrowR: '<path d="M5 12h14M13 6l6 6-6 6"/>',
  copy: '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>',
  eyeoff: '<path d="M3 3l18 18M10.6 5.1A9.8 9.8 0 0 1 12 5c5 0 8.5 4.5 9.5 7a13 13 0 0 1-2.6 3.7M6.5 6.6C4.4 8 3 10 2.5 12c1 2.5 4.5 7 9.5 7 1.8 0 3.4-.6 4.8-1.4M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
  wifi: '<path d="M2.5 9a14 14 0 0 1 19 0M5.5 12.5a9.5 9.5 0 0 1 13 0M9 16a4.5 4.5 0 0 1 6 0M12 19.5h.01"/>',
};
const ic = (n, cls = '') => `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${I[n] || ''}</svg>`;

/* Room pictograms — used only by the hospitality concept */
function roomArt(type) {
  const s = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"';
  let g = '';
  if (type === 'training') {
    g += '<rect x="55" y="8" width="50" height="5" rx="2.5" fill="currentColor" opacity=".55"/>';
    for (let r = 0; r < 3; r++) for (let c = 0; c < 4; c++) g += `<rect x="${26 + c * 29}" y="${28 + r * 19}" width="21" height="8" rx="2" ${s}/>`;
  } else if (type === 'studio') {
    g += `<circle cx="80" cy="38" r="17" ${s} stroke-width="5" opacity=".75"/><path d="M80 55v25M80 62l-14 18M80 62l14 18" ${s}/><rect x="16" y="10" width="26" height="70" rx="3" fill="currentColor" opacity=".12"/><rect x="118" y="10" width="26" height="70" rx="3" fill="currentColor" opacity=".12"/>`;
  } else if (type === 'office') {
    g += `<rect x="36" y="26" width="88" height="26" rx="3" ${s}/><rect x="66" y="14" width="28" height="12" rx="2" fill="currentColor" opacity=".5"/><circle cx="60" cy="68" r="8" ${s}/><circle cx="100" cy="68" r="8" ${s}/><rect x="130" y="16" width="16" height="62" rx="3" fill="currentColor" opacity=".12"/>`;
  } else if (type === 'shared') {
    g += `<rect x="22" y="36" width="116" height="18" rx="4" ${s}/>`;
    for (let i = 0; i < 6; i++) g += `<circle cx="${32 + i * 19}" cy="24" r="5.5" ${s}/><circle cx="${32 + i * 19}" cy="66" r="5.5" ${s} ${i % 2 ? 'fill="currentColor"' : ''}/>`;
  } else {
    g += `<ellipse cx="80" cy="45" rx="42" ry="17" ${s}/>`;
    [[48, 18], [80, 14], [112, 18], [48, 72], [80, 76], [112, 72], [28, 45], [132, 45]].forEach(([x, y]) => { g += `<circle cx="${x}" cy="${y}" r="5.5" ${s}/>`; });
  }
  return `<svg viewBox="0 0 160 90" aria-hidden="true">${g}</svg>`;
}

/* ------------------------------------------------------------------- data */
const TYPES = {
  training: ['Training Room', 'قاعة تدريب'], studio: ['Studio', 'استوديو'], office: ['Office', 'مكتب'],
  shared: ['Shared Room', 'مساحة مشتركة'], meeting: ['Meeting Room', 'قاعة اجتماعات'],
};
const typeName = t => L(...TYPES[t]);
const ROOMS = [
  { id: 'tr1', name: ['Training Room 01', 'قاعة التدريب 01'], type: 'training', cap: 24, rate: 350, floor: ['1st floor', 'الدور الأول'], status: 'occupied', until: 840 },
  { id: 'tr2', name: ['Training Room 02', 'قاعة التدريب 02'], type: 'training', cap: 16, rate: 280, floor: ['1st floor', 'الدور الأول'], status: 'available', next: 960 },
  { id: 'sa', name: ['Studio A', 'استوديو A'], type: 'studio', cap: 6, rate: 400, floor: ['Ground floor', 'الدور الأرضي'], status: 'occupied', until: 810 },
  { id: 'sb', name: ['Studio B', 'استوديو B'], type: 'studio', cap: 4, rate: 300, floor: ['Ground floor', 'الدور الأرضي'], status: 'reserved', next: 840 },
  { id: 'o1', name: ['Office 01', 'مكتب 01'], type: 'office', cap: 4, rate: 150, floor: ['2nd floor', 'الدور الثاني'], status: 'occupied', until: 750 },
  { id: 'o2', name: ['Office 02', 'مكتب 02'], type: 'office', cap: 6, rate: 180, floor: ['2nd floor', 'الدور الثاني'], status: 'available', next: null },
  { id: 'sh', name: ['Shared Workspace', 'مساحة العمل المشتركة'], type: 'shared', cap: 40, rate: 40, floor: ['Ground floor', 'الدور الأرضي'], status: 'occupied', used: 23 },
  { id: 'm3', name: ['Meeting Room 03', 'قاعة الاجتماعات 03'], type: 'meeting', cap: 8, rate: 200, floor: ['1st floor', 'الدور الأول'], status: 'maintenance' },
];
const ROOM = Object.fromEntries(ROOMS.map(r => [r.id, r]));
const rn = r => L(r.name[0], r.name[1]);

const PEOPLE = {
  ahmed: ['Ahmed Mohamed', 'أحمد محمد', '0100 234 5567'], mariam: ['Mariam Hassan', 'مريم حسن', '0112 887 1402'],
  omar: ['Omar Khaled', 'عمر خالد', '0127 555 0198'], salma: ['Salma Ibrahim', 'سلمى إبراهيم', '0109 331 7720'],
  youssef: ['Youssef Adel', 'يوسف عادل', '0115 640 2231'], hana: ['Hana Tarek', 'هنا طارق', '0122 018 9954'],
  nour: ['Nour El-Din Samir', 'نور الدين سمير', '0106 772 4410'], rana: ['Rana Ashraf', 'رنا أشرف', '0128 190 3376'],
  mostafa: ['Mostafa Gamal', 'مصطفى جمال', '0101 455 8823'], laila: ['Laila Sherif', 'ليلى شريف', '0114 902 6617'],
  karim: ['Karim Mostafa', 'كريم مصطفى', '0120 367 1189'], mahmoud: ['Mahmoud Fathy', 'محمود فتحي', '0111 284 5530'],
};
const PKEYS = Object.keys(PEOPLE);
const pn = k => L(PEOPLE[k][0], PEOPLE[k][1]);
const initials = k => { const p = PEOPLE[k][LANG === 'ar' ? 1 : 0].split(' '); return (p[0][0] + (p[1] ? p[1][0] : '')).toUpperCase(); };

const PTYPES = { drink: ['Drink', 'مشروب'], food: ['Food', 'أكل'], service: ['Service', 'خدمة'], supply: ['Supply', 'مستلزمات'] };
const PRODUCTS0 = [
  { id: 'esp', n: ['Espresso', 'إسبريسو'], type: 'drink', price: 35, active: true, sku: 'DR-001', stock: null, sold: 142 },
  { id: 'cap', n: ['Cappuccino', 'كابتشينو'], type: 'drink', price: 55, active: true, sku: 'DR-002', stock: null, sold: 198 },
  { id: 'turk', n: ['Turkish Coffee', 'قهوة تركي'], type: 'drink', price: 30, active: true, sku: 'DR-003', stock: null, sold: 87 },
  { id: 'tea', n: ['Tea', 'شاي'], type: 'drink', price: 20, active: true, sku: 'DR-004', stock: null, sold: 164 },
  { id: 'water', n: ['Mineral Water', 'مياه معدنية'], type: 'drink', price: 10, active: true, sku: 'DR-005', stock: 48, sold: 231 },
  { id: 'oj', n: ['Fresh Orange Juice', 'عصير برتقال فريش'], type: 'drink', price: 45, active: true, sku: 'DR-006', stock: null, sold: 58 },
  { id: 'crois', n: ['Butter Croissant', 'كرواسون زبدة'], type: 'food', price: 40, active: true, sku: 'FD-001', stock: 9, sold: 76 },
  { id: 'club', n: ['Club Sandwich', 'كلوب ساندويتش'], type: 'food', price: 85, active: true, sku: 'FD-002', stock: 6, sold: 49 },
  { id: 'print', n: ['Printing (per page)', 'طباعة (للصفحة)'], type: 'service', price: 2, active: true, sku: 'SV-001', stock: null, sold: 1240 },
  { id: 'proj', n: ['Projector Rental', 'إيجار بروجكتور'], type: 'service', price: 150, active: true, sku: 'SV-002', stock: null, sold: 12 },
  { id: 'locker', n: ['Locker (day)', 'لوكر (يومي)'], type: 'service', price: 50, active: true, sku: 'SV-003', stock: null, sold: 21 },
  { id: 'mark', n: ['Whiteboard Markers Set', 'طقم أقلام سبورة'], type: 'supply', price: 60, active: true, sku: 'SP-001', stock: 3, sold: 9 },
  { id: 'energy', n: ['Energy Drink', 'مشروب طاقة'], type: 'drink', price: 45, active: false, sku: 'DR-007', stock: 0, sold: 17 },
];

const SESSIONS0 = [
  { id: 1, who: 'ahmed', room: 'tr1', kind: 'exclusive', start: 602, end: 840, party: 18, items: [{ p: 'cap', q: 8 }, { p: 'water', q: 12 }] },
  { id: 2, who: 'mariam', room: 'sa', kind: 'exclusive', start: 675, end: 810, party: 3, items: [{ p: 'esp', q: 2 }] },
  { id: 3, who: 'omar', room: 'sh', kind: 'shared', start: 580, party: 1, items: [{ p: 'cap', q: 1 }, { p: 'crois', q: 1 }] },
  { id: 4, who: 'salma', room: 'sh', kind: 'shared', start: 725, party: 3, items: [] },
  { id: 5, who: 'youssef', room: 'o1', kind: 'exclusive', start: 510, end: 750, party: 2, items: [{ p: 'tea', q: 2 }, { p: 'print', q: 14 }] },
  { id: 6, who: 'hana', room: 'sh', kind: 'shared', start: 760, party: 2, items: [{ p: 'oj', q: 2 }] },
];

const BOOKINGS_TODAY = [
  { room: 'tr1', who: 'ahmed', s: 600, e: 840, st: 'live' },
  { room: 'tr1', who: 'nour', s: 900, e: 1080, st: 'confirmed' },
  { room: 'tr2', who: 'rana', s: 540, e: 660, st: 'done' },
  { room: 'tr2', who: 'mostafa', s: 960, e: 1200, st: 'pending' },
  { room: 'sa', who: 'mariam', s: 660, e: 810, st: 'live' },
  { room: 'sa', who: 'laila', s: 1020, e: 1140, st: 'confirmed' },
  { room: 'sb', who: 'karim', s: 840, e: 960, st: 'confirmed' },
  { room: 'o1', who: 'youssef', s: 510, e: 750, st: 'live' },
  { room: 'o1', who: 'mahmoud', s: 810, e: 1050, st: 'confirmed' },
  { room: 'o2', who: 'karim', s: 540, e: 720, st: 'done' },
];
const DAY_START = 480, DAY_END = 1320, SLOTS = (DAY_END - DAY_START) / 30;

function bookingsFor(roomId, d) {
  if (roomId === 'sh' || roomId === 'm3') return [];
  if (d === 0) return BOOKINGS_TODAY.filter(b => b.room === roomId);
  if (roomId === 'tr2' && d === 1) return [
    { room: 'tr2', who: 'rana', s: 510, e: 600, st: 'confirmed' },
    { room: 'tr2', who: 'nour', s: 900, e: 1080, st: 'pending' },
  ];
  const h = hash(roomId + ':' + d);
  const out = [];
  let cur = h % 3;
  const n = 1 + (h >> 4) % 3;
  for (let i = 0; i < n; i++) {
    const len = 2 + ((h >> (i * 3 + 2)) % 6);
    const st = cur + 1 + ((h >> (i * 2 + 7)) % 5);
    if (st + len > SLOTS) break;
    out.push({ room: roomId, who: PKEYS[(h + i * 5) % PKEYS.length], s: DAY_START + st * 30, e: DAY_START + (st + len) * 30, st: d < 0 ? 'done' : ((h >> i) % 5 === 0 ? 'pending' : 'confirmed') });
    cur = st + len;
  }
  return out;
}
function busySlots(roomId, d) {
  const busy = new Array(SLOTS).fill(false);
  bookingsFor(roomId, d).forEach(b => { for (let t = b.s; t < b.e; t += 30) { const i = (t - DAY_START) / 30; if (i >= 0 && i < SLOTS) busy[i] = true; } });
  if (ROOM[roomId].status === 'maintenance') busy.fill(true);
  return busy;
}
const pastSlot = (d, i) => d === 0 && DAY_START + i * 30 < nowMin();

/* Revenue: 30 days. The last 14 are hand-written; the rest derived. */
const REV = (() => {
  const b14 = [5200, 6100, 4800, 7300, 6900, 3900, 4200, 6600, 7100, 5800, 7700, 8200, 4600, 6380];
  const p14 = [1100, 1350, 900, 1600, 1420, 700, 820, 1500, 1380, 1250, 1690, 1840, 950, 2040];
  const b = [], p = [];
  for (let i = 0; i < 16; i++) { b.push(4300 + ((i * 37) % 9) * 360); p.push(760 + ((i * 53) % 7) * 160); }
  return { b: b.concat(b14), p: p.concat(p14) };
})();

const TX = [
  { id: 'INV-10482', d: 0, t: 778, who: 'karim', what: ['Office 02 · 3h booking', 'مكتب 02 · حجز 3 ساعات'], kind: 'booking', m: 'card', amt: 540, st: 'paid' },
  { id: 'INV-10481', d: 0, t: 760, who: 'omar', what: ['Cappuccino, Butter Croissant', 'كابتشينو، كرواسون زبدة'], kind: 'product', m: 'cash', amt: 95, st: 'paid' },
  { id: 'INV-10480', d: 0, t: 670, who: 'rana', what: ['Training Room 02 · 2h booking', 'قاعة التدريب 02 · حجز ساعتين'], kind: 'booking', m: 'instapay', amt: 560, st: 'paid' },
  { id: 'INV-10479', d: 0, t: 655, who: 'hana', what: ['Fresh Orange Juice ×2', 'عصير برتقال فريش ×2'], kind: 'product', m: 'cash', amt: 90, st: 'paid' },
  { id: 'INV-10478', d: 0, t: 620, who: 'laila', what: ['Studio A · deposit for 5 PM', 'استوديو A · عربون حجز 5 م'], kind: 'booking', m: 'instapay', amt: 400, st: 'pending' },
  { id: 'INV-10477', d: 0, t: 585, who: 'mostafa', what: ['Shared Workspace · 4h 10m', 'المساحة المشتركة · 4 س 10 د'], kind: 'booking', m: 'cash', amt: 167, st: 'paid' },
  { id: 'INV-10476', d: 0, t: 570, who: 'salma', what: ['Refund · cancelled booking', 'استرداد · حجز ملغي'], kind: 'booking', m: 'card', amt: -300, st: 'refunded' },
  { id: 'INV-10475', d: 0, t: 545, who: 'mahmoud', what: ['Club Sandwich, Espresso', 'كلوب ساندويتش، إسبريسو'], kind: 'product', m: 'card', amt: 120, st: 'paid' },
  { id: 'INV-10474', d: -1, t: 1300, who: 'youssef', what: ['Shared Workspace · 6h', 'المساحة المشتركة · 6 س'], kind: 'booking', m: 'cash', amt: 240, st: 'paid' },
  { id: 'INV-10473', d: -1, t: 1215, who: 'nour', what: ['Training Room 01 · 4h booking', 'قاعة التدريب 01 · حجز 4 ساعات'], kind: 'booking', m: 'card', amt: 1400, st: 'paid' },
];
const METHODS = { cash: ['Cash', 'كاش'], card: ['Card', 'بطاقة'], instapay: ['InstaPay', 'إنستاباي'] };

const ACTIVITY = [
  { t: 778, c: 'ok', en: '<b>Karim Mostafa</b> checked out of Office 02 · EGP 540 by card', ar: '<b>كريم مصطفى</b> غادر مكتب 02 · 540 ج.م بالبطاقة' },
  { t: 760, c: 'info', en: '<b>Hana Tarek</b> started a shared session · 2 people', ar: '<b>هنا طارق</b> بدأت جلسة مشتركة · شخصان' },
  { t: 751, c: 'danger', en: '<b>Office 01</b> passed its booked end time', ar: '<b>مكتب 01</b> تجاوز وقت الحجز' },
  { t: 725, c: 'info', en: '<b>Salma Ibrahim</b> started a shared session · 3 people', ar: '<b>سلمى إبراهيم</b> بدأت جلسة مشتركة · 3 أشخاص' },
  { t: 708, c: 'warn', en: '<b>Mostafa Gamal</b> requested Training Room 02 for 4 PM', ar: '<b>مصطفى جمال</b> طلب قاعة التدريب 02 الساعة 4 م' },
  { t: 675, c: 'info', en: '<b>Mariam Hassan</b> checked in to Studio A', ar: '<b>مريم حسن</b> سجلت دخول استوديو A' },
];

/* ------------------------------------------------------------- concepts */
const SCREENS = [
  { k: 'dashboard', en: 'Dashboard', ar: 'لوحة التحكم', path: 'dashboard', note: 'Revenue, today’s bookings, live sessions, occupancy, activity and quick actions — arranged the way this concept prioritises them.' },
  { k: 'sessions', en: 'Active Sessions', ar: 'الجلسات النشطة', path: 'active-sessions', note: 'Real-time operations. Timers tick every second. Try the room filters, expand products, “Add product” and “Check out”.' },
  { k: 'bookings', en: 'Bookings', ar: 'الحجوزات', path: 'bookings', note: 'Day / Week / Month calendar with room filters. Click an empty hour to start a booking there; click a day in Month view.' },
  { k: 'rooms', en: 'Rooms & Workspaces', ar: 'الغرف', path: 'workspaces', note: 'Room cards with type, capacity, live status, pricing and quick actions (⋯ menu).' },
  { k: 'financials', en: 'Financials', ar: 'المالية', path: 'financials', note: 'Period, revenue-type and payment filters, hover the bars for exact values, open the Export menu.' },
  { k: 'products', en: 'Products', ar: 'المنتجات', path: 'products', note: 'Search (try “xyz” for the empty state), type filters, grid/list, toggle active, add or delete a product.' },
  { k: 'create', en: 'Booking / Session Creation', ar: 'إنشاء حجز', path: 'bookings/create', note: 'Room → date → start → end → customer → summary → confirm. Tap a start time, then an end time — every end option shows the resulting duration.' },
  { k: 'overlays', en: 'Modals & Drawers', ar: 'النوافذ والأدراج', path: 'active-sessions', note: 'Every overlay type in one place. “Open live” runs it inside the frame.' },
];

const EXTRA_SCREENS = [
  { k: 'customers', en: 'Customers', ar: 'العملاء', path: 'users', note: 'Find anyone in seconds, see who is here right now, act from the row.' },
  { k: 'checkout', en: 'Checkout', ar: 'الدفع', path: 'active-sessions/checkout', note: 'Full checkout with receipt, payment method and change calculator.' },
];
const screenDef = k => SCREENS.concat(EXTRA_SCREENS).find(s => s.k === k);
const screensFor = c => (c.screens ? c.screens.map(screenDef) : SCREENS);
const HOOKS = { screen: {}, modal: {}, action: {}, ds: {}, topbar: {}, state: {}, section: {} };

const CONCEPTS = [
  {
    id: 'calm', tag: 'Calm', n: '01', name: 'Calm Premium SaaS', ref: 'Linear · Vercel · modern productivity tools',
    short: 'Quiet, precise and dense. Colour is used only for status.',
    philosophy: 'The interface steps back so the work comes forward. Near-monochrome surfaces, hairline dividers instead of boxes, one ink-black action colour. Colour appears only when something has a status — so an overtime session is found in a glance.',
    optimized: 'Front-desk staff who keep the panel open all shift. More on screen without feeling crowded; fast mouse-and-keyboard operation.',
    type: 'Geist (UI & numerals) · IBM Plex Sans Arabic',
    feel: 'Quiet · precise · focused',
    shell: 'sidebar', dash: 'strip', prodView: 'list',
    navGroups: null,
    swatches: [['Ink · primary', '#171717'], ['Accent', '#3b5bdb'], ['Background', '#ffffff'], ['Sidebar', '#fafafa'], ['Surface 2', '#f6f6f6'], ['Border', '#ebebeb'], ['Text 2', '#5d5d5d'], ['Text 3', '#8f8f8f']],
    scale: [['Display', '30 / 600 / −2%', 'font-size:30px;font-weight:600;letter-spacing:-.02em', 'EGP 8,420'], ['Page title', '21 / 600', 'font-size:21px;font-weight:600;letter-spacing:-.018em', 'Active Sessions'], ['Section', '13.5 / 600', 'font-size:13.5px;font-weight:600', 'Up next today'], ['Body', '13.5 / 400', 'font-size:13.5px', 'Booked until 2:00 PM · 18 people'], ['Label', '12 / 500', 'font-size:12px;font-weight:500;color:var(--text-2)', 'Started · Duration · Amount'], ['Numerals', 'tabular', 'font-size:13.5px;font-variant-numeric:tabular-nums', '1:05:32 · EGP 1,280']],
    radii: [['6', 'Controls'], ['8', 'Cards'], ['10', 'Modals']],
    borders: '1px hairlines (#ebebeb). KPIs share one strip separated by dividers — fewer boxes, less noise.',
    principles: [['Colour = status', 'Everything neutral until it needs attention. Badges are a dot + word, no fills.'], ['One ink button', 'The single black button is always the next step on the screen.'], ['Dividers over boxes', 'Hierarchy comes from type weight and spacing, not containers.']],
    why: [['Status jumps out because nothing else is coloured', 'With a monochrome base, the one red “Overtime” badge is visible from across the desk without any extra decoration.'], ['Density without fatigue', 'A 13.5px base and 40px rows fit all six session cards and the whole day’s calendar on a laptop screen with little scrolling.'], ['Predictable, repeatable', 'Few tokens and strict rules make every future screen look like it belongs — developers can hardly get it wrong.'], ['Arabic-ready', 'Logical spacing and IBM Plex Sans Arabic keep the same quiet tone in RTL.']],
    trade: 'Can feel reserved or “techy” to non-technical staff, and relies on disciplined typography in every new screen to avoid looking bare.',
  },
  {
    id: 'warm', tag: 'Warm', n: '02', name: 'Warm Modern Workspace', ref: 'Notion · premium coworking brands',
    short: 'Paper-toned, friendly, editorial. The panel talks in sentences.',
    philosophy: 'It should feel like the space itself: calm, warm and human. Paper-toned surfaces, an editorial serif for headings and a clay accent. The dashboard speaks in plain sentences — “3 bookings still to come today” — before it shows numbers.',
    optimized: 'Owner-operated spaces and small teams, community-driven coworking brands, staff who prefer friendly software over “systems”.',
    type: 'Newsreader (headings) + Inter (UI) · Noto Naskh Arabic + IBM Plex Sans Arabic',
    feel: 'Warm · human · reassuring',
    shell: 'sidebar', dash: 'narrative', prodView: 'grid',
    navGroups: [[['Today', 'اليوم'], ['dashboard', 'sessions', 'bookings']], [['Manage', 'الإدارة'], ['rooms', 'customers', 'products']], [['Money', 'المال'], ['financials']]],
    swatches: [['Charcoal · primary', '#2d2a25'], ['Clay · accent', '#b4532a'], ['Paper · background', '#fbfaf7'], ['Sidebar', '#f4f2ec'], ['Surface 2', '#f4f1ea'], ['Border', '#e9e4d9'], ['Text 2', '#6b645a'], ['Text 3', '#9c9486']],
    scale: [['Greeting', 'Newsreader 34 / 500', 'font-family:var(--font-head);font-size:34px;font-weight:500;letter-spacing:-.02em', 'Good afternoon, Nada'], ['Page title', 'Newsreader 30 / 500', 'font-family:var(--font-head);font-size:30px;font-weight:500', 'Active sessions'], ['Section', 'Newsreader 17 / 500', 'font-family:var(--font-head);font-size:17px;font-weight:500', 'Today’s schedule'], ['Body', 'Inter 14 / 400', 'font-size:14px', 'Mariam is in Studio A until 1:30 PM'], ['Label', 'Inter 12.5 / 500', 'font-size:12.5px;font-weight:500;color:var(--text-2)', 'Started · Duration · Amount'], ['Numerals', 'Newsreader for KPIs', 'font-family:var(--font-head);font-size:28px;font-weight:500', 'EGP 8,420']],
    radii: [['8', 'Controls'], ['10', 'Cards'], ['14', 'Modals'], ['999', 'Chips & badges']],
    borders: '1px warm borders (#e9e4d9) with a barely-there warm shadow. Sidebar has no border — it is a different paper tone.',
    principles: [['Say it in a sentence', 'Summaries answer “what should I do next?” in words before numbers.'], ['Warm neutrals, one accent', 'Clay marks selection and focus; charcoal is the action colour.'], ['Empty states teach', 'Every empty screen explains what will appear and offers the next step.']],
    why: [['Less “software”, more hospitality', 'Warm paper tones and a serif voice feel like a well-run space, which lowers the stress of a busy front desk.'], ['Clear hierarchy through type contrast', 'Serif headings vs sans UI text separate “what this is” from “what to do” without extra boxes.'], ['Guides new staff', 'The narrative summary and friendly empty states make the panel self-explanatory on day one.'], ['Rounded, soft but not bubbly', 'Radii stay at 8–10px on working surfaces; only chips are pills.']],
    trade: 'A strong brand choice — very personal. Slightly less dense than Calm/Ops, and the clay accent sits near red, so danger always pairs its colour with an icon and wording.',
  },
  {
    id: 'ops', tag: 'Operations', n: '03', name: 'Premium Business Operations', ref: 'Stripe · Ramp · Mercury',
    short: 'Numbers are the product. Precise panels, tabular figures, brand navy.',
    philosophy: 'Money and time are what this business sells, so every amount is treated with care: tabular figures, right-aligned, always paired with context (vs. last period, share of total). Crisp panels and precise tables on a cool grey canvas; the existing Link Space navy anchors trust.',
    optimized: 'Owners and managers who reconcile money daily, multi-room operations, anyone who needs to trust the totals at a glance.',
    type: 'Inter with tabular figures · IBM Plex Mono (IDs) · IBM Plex Sans Arabic',
    feel: 'Trustworthy · exact · composed',
    shell: 'sidebar', dash: 'finance', prodView: 'list', bigAmount: true,
    navGroups: [[['Operations', 'التشغيل'], ['dashboard', 'sessions', 'bookings', 'rooms', 'customers']], [['Commerce', 'المبيعات'], ['products', 'financials']]],
    swatches: [['Navy · primary', '#163c85'], ['Blue · accent', '#2f5bb7'], ['Canvas', '#f6f8fa'], ['Surface', '#ffffff'], ['Table header', '#f9fafb'], ['Border', '#e3e8ee'], ['Ink', '#0c1b33'], ['Text 2', '#4a5a70']],
    scale: [['Hero figure', '40 / 600 / −3%', 'font-size:40px;font-weight:600;letter-spacing:-.03em;font-variant-numeric:tabular-nums', 'EGP 8,420'], ['Page title', '24 / 600', 'font-size:24px;font-weight:600;letter-spacing:-.02em', 'Financials'], ['Section', '15 / 600', 'font-size:15px;font-weight:600', 'Recent transactions'], ['Body', '14 / 400', 'font-size:14px', 'Paid by card · INV-10482'], ['Table header', '11 / 500 caps', 'font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.05em;color:var(--text-3)', 'Amount'], ['Identifiers', 'IBM Plex Mono 13', 'font-family:var(--font-mono);font-size:13px', 'INV-10482']],
    radii: [['6', 'Controls'], ['8', 'Panels'], ['10', 'Modals']],
    borders: '1px cool borders (#e3e8ee) plus a 1px hairline shadow. Tables get a tinted header row. Modal footers are tinted.',
    principles: [['Every number has context', 'A KPI always shows its comparison or share.'], ['Align the money', 'Amounts are tabular and end-aligned so columns can be scanned.'], ['Brand continuity', 'Keeps Link Space navy #163c85 as the primary action colour.']],
    why: [['Built for trust', 'Consistent number formatting, right-aligned amounts and visible comparisons make totals feel verifiable — important when staff collect cash.'], ['Clear money screens', 'Financials and Checkout read like a clean statement: line items, subtotal, total to collect.'], ['Continuity with today', 'Uses the current brand navy, so existing users recognise the product immediately.'], ['Scales to reporting', 'Panels and tables extend naturally to branches, staff reports and exports.']],
    trade: 'Leans “finance tool” more than hospitality — operational screens (sessions, rooms) feel more like a ledger than a place.',
  },
  {
    id: 'host', tag: 'Hospitality', n: '04', name: 'Modern Hospitality', ref: 'Airbnb · Calendly · hospitality software',
    short: 'Rooms are places, not rows. Visual, touch-friendly, plain language.',
    philosophy: 'Rooms are places, not rows. Each room type has a recognisable pictogram and tint, controls are large enough for a reception tablet, and status is written in plain language (“Free until 4:00 PM”). Navigation moves to the top so the full width goes to rooms and time.',
    optimized: 'Non-technical front-desk staff, reception tablets, booking-heavy businesses where customers stand at the desk.',
    type: 'Plus Jakarta Sans · Tajawal (Arabic)',
    feel: 'Welcoming · visual · effortless',
    shell: 'top', dash: 'board', prodView: 'grid', roomArt: true,
    navGroups: null,
    swatches: [['Teal · primary', '#0b7a64'], ['Ink', '#1f1f1f'], ['Background', '#ffffff'], ['Surface 2', '#f6f5f2'], ['Border', '#e8e6e1'], ['Training tint', '#2f6fdb'], ['Studio tint', '#c2417a'], ['Shared tint', '#c9731f']],
    scale: [['Display', '34 / 700', 'font-size:34px;font-weight:700;letter-spacing:-.02em', 'EGP 8,420'], ['Page title', '26 / 700', 'font-size:26px;font-weight:700;letter-spacing:-.02em', 'Rooms'], ['Section', '16.5 / 700', 'font-size:16.5px;font-weight:700', 'Right now'], ['Body', '14.5 / 400', 'font-size:14.5px', 'Free until 4:00 PM'], ['Label', '13 / 600', 'font-size:13px;font-weight:600;color:var(--text-2)', 'Capacity · Price'], ['Buttons', '14.5 / 600', 'font-size:14.5px;font-weight:600', 'Check out']],
    radii: [['10', 'Inputs'], ['14', 'Cards'], ['18', 'Modals'], ['999', 'Buttons & chips']],
    borders: 'Soft 1px borders (#e8e6e1), no card shadows. Room-type tint bands carry identity; status is always a labelled pill.',
    principles: [['Plain language', '“Free until 4 PM” instead of “Available / next: 16:00”.'], ['Big targets', '40px controls, 46px time slots, pill buttons — comfortable on a tablet.'], ['Places have faces', 'Each room type gets a pictogram and tint so staff recognise rooms before reading.']],
    why: [['Instantly understandable', 'Room pictograms and plain-language status work for staff on their first shift and for customers looking over the counter.'], ['Booking-first', 'Top navigation frees the full width for the calendar and room board; time slots are large and forgiving.'], ['Tablet-ready by design', 'Touch-sized controls and pill buttons feel natural on an iPad at reception.'], ['Status stays honest', 'Type tints identify rooms; status is always a separate labelled pill, so colour never means two things.']],
    trade: 'Lowest density of the five, and the type tints add colour that must be governed carefully so they never compete with status.',
  },
  {
    id: 'bold', tag: 'Bold', n: '05', name: 'Bold Product-Led SaaS', ref: 'Attio · Figma · modern product-led apps',
    short: 'Confident, keyboard-first, one vermilion accent, big numerals.',
    philosophy: 'Confident, fast and keyboard-first. A dark command sidebar frames a bright workspace; large display numerals make timers and totals readable from across the desk, and a single vermilion accent marks the one action that matters. ⌘K reaches any screen or action.',
    optimized: 'Power users and tech-savvy teams, creative studios and brand-forward hubs that want the panel to feel like their brand.',
    type: 'Instrument Sans (already in the Vite build) · Cairo (already used in the layout) · Geist Mono for timers',
    feel: 'Confident · fast · expressive',
    shell: 'sidebar', dash: 'display', prodView: 'grid', cmdk: true,
    navGroups: null,
    swatches: [['Vermilion · primary', '#e8492a'], ['Ink', '#121212'], ['Sidebar', '#141414'], ['Background', '#ffffff'], ['Surface 2', '#f5f5f4'], ['Border', '#e8e8e6'], ['Text 2', '#595955'], ['Chart 2', '#3a63d8']],
    scale: [['Display', '44 / 600 / −4%', 'font-size:44px;font-weight:600;letter-spacing:-.04em', 'EGP 8,420'], ['Timer', 'Geist Mono 34 / 600', 'font-family:var(--font-mono);font-size:34px;font-weight:600;letter-spacing:-.04em', '3:03:12'], ['Page title', '28 / 650 / −3%', 'font-size:28px;font-weight:650;letter-spacing:-.03em', 'Active Sessions'], ['Section', '15 / 600', 'font-size:15px;font-weight:600', 'Quick actions'], ['Body', '14 / 400', 'font-size:14px', 'Shared · per minute · 3 people'], ['Label', '12.5 / 500', 'font-size:12.5px;font-weight:500;color:var(--text-2)', 'Started · Amount']],
    radii: [['8', 'Controls'], ['12', 'Cards'], ['14', 'Modals']],
    borders: '1px neutral borders, no card shadows. Dark sidebar separates navigation from work. Attention states use a 3px top rule on cards.',
    principles: [['One accent, used sparingly', 'Vermilion only for the primary action and focus.'], ['Numbers you can read from afar', 'Display numerals and mono timers for live data.'], ['Keyboard-first', '⌘K command bar plus single-key shortcuts for frequent actions.']],
    why: [['Fast for power users', 'The command bar and shortcuts make frequent actions (new booking, check out) two keystrokes away.'], ['Live data is legible', 'Mono timers and display numerals can be read at a glance from behind the desk.'], ['Distinctive but controlled', 'Personality comes from type and one accent, not gradients or decoration — the workspace itself stays white and calm.'], ['Reuses existing fonts', 'Instrument Sans and Cairo are already part of the project, so adopting it adds no new dependency.']],
    trade: 'The dark sidebar and vermilion are a strong statement; vermilion sits close to red, so destructive actions use a deeper red, an icon and clear wording, and are always separated.',
  },
];
const CMAP = Object.fromEntries(CONCEPTS.map(c => [c.id, c]));
function addConcept(c, index = CONCEPTS.length) { CONCEPTS.splice(index, 0, c); CMAP[c.id] = c; }

const NAV = [
  { k: 'dashboard', ic: 'home', en: 'Dashboard', ar: 'لوحة التحكم', key: 'D' },
  { k: 'sessions', ic: 'live', en: 'Active Sessions', ar: 'الجلسات النشطة', cnt: true, key: 'S' },
  { k: 'bookings', ic: 'cal', en: 'Bookings', ar: 'الحجوزات', key: 'B' },
  { k: 'rooms', ic: 'door', en: 'Rooms', ar: 'الغرف', key: 'R' },
  { k: 'customers', ic: 'users', en: 'Customers', ar: 'العملاء', key: 'C' },
  { k: 'products', ic: 'box', en: 'Products', ar: 'المنتجات', key: 'P' },
  { k: 'financials', ic: 'money', en: 'Financials', ar: 'المالية', key: 'F' },
];
const NAVMAP = Object.fromEntries(NAV.map(n => [n.k, n]));

/* ----------------------------------------------------------- frame state */
const FS = {};
function newDraft() { return { room: 'tr2', date: 1, start: 4, end: 10, cust: 'ahmed', custQ: '', newCust: false }; }
let newState = function (cid, screen) {
  return {
    cid, screen, collapsed: false, sessFilter: 'all', sessQ: '', sessExp: { 1: true },
    sessions: clone(SESSIONS0), products: clone(PRODUCTS0), nextId: 100,
    calView: 'day', calRoom: 'all', calOff: 0, roomType: 'all',
    finPeriod: 14, finKind: 'all', finMethod: 'all', loading: false,
    prodQ: '', prodType: 'all', prodView: CMAP[cid].prodView,
    menu: null, modal: null, toast: null, lastRemoved: null, draft: newDraft(),
  };
};
const _newState = newState;
newState = function (cid, screen) { const st = _newState(cid, screen); if (HOOKS.state[cid]) HOOKS.state[cid](st); return st; };

/* --------------------------------------------------------- tiny helpers */
const pname = (fs, id) => { const p = fs.products.find(x => x.id === id) || PRODUCTS0.find(x => x.id === id); return p ? L(p.n[0], p.n[1]) : id; };
const pprice = (fs, id) => { const p = fs.products.find(x => x.id === id) || PRODUCTS0.find(x => x.id === id); return p ? p.price : 0; };
function btn(label, o = {}) {
  const cls = ['btn', 'btn-' + (o.kind || 'secondary'), o.sm ? 'btn-sm' : '', o.iconOnly ? 'btn-icon' : '', o.block ? 'btn-block' : '', o.cls || ''].join(' ');
  const act = o.act ? ` data-act="${o.act}"${o.v !== undefined ? ` data-v="${esc(o.v)}"` : ''}` : '';
  return `<button type="button" class="${cls}"${act}${o.disabled ? ' disabled' : ''}${o.title ? ` title="${esc(o.title)}" aria-label="${esc(o.title)}"` : ''}>${o.icon ? ic(o.icon, o.flip ? 'flip' : '') : ''}${o.iconOnly ? '' : `<span>${label}</span>`}${o.kbd ? `<span class="kbd" style="margin-inline-start:4px;background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.25);color:inherit">${o.kbd}</span>` : ''}</button>`;
}
const badge = (cls, label, o = {}) => `<span class="badge ${cls}">${o.dot !== false ? `<span class="dot${o.pulse ? ' pulse' : ''}"></span>` : ''}${label}</span>`;
const seg = (items, cur, act) => `<div class="seg" role="tablist">${items.map(([v, l]) => `<button type="button" class="${String(v) === String(cur) ? 'on' : ''}" data-act="${act}" data-v="${v}">${l}</button>`).join('')}</div>`;
const chips = (items, cur, act) => `<div class="chips">${items.map(([v, l, n]) => `<button type="button" class="chip ${v === cur ? 'on' : ''}" data-act="${act}" data-v="${v}">${l}${n !== undefined ? `<span class="count">${n}</span>` : ''}</button>`).join('')}</div>`;
const pageHead = (title, sub, actions) => `<div class="page-head"><div><h1>${title}</h1>${sub ? `<div class="sub">${sub}</div>` : ''}</div>${actions ? `<div class="actions">${actions}</div>` : ''}</div>`;
const card = (title, body, o = {}) => `<section class="card ${o.cls || ''}">${title ? `<div class="card-h"><h3>${title}</h3>${o.right || ''}</div>` : ''}<div class="${o.flush ? '' : 'card-b'}"${o.flush ? ' style="padding-top:8px"' : ''}>${body}</div></section>`;
const emptyState = (icon, title, text, actions = '') => `<div class="empty"><div class="empty-ic">${ic(icon)}</div><h4>${title}</h4><p>${text}</p>${actions ? `<div class="actions">${actions}</div>` : ''}</div>`;

/* --------------------------------------------------------- domain logic */
function sessStatus(s) {
  if (s.kind !== 'exclusive') return 'live';
  const n = nowMin();
  if (n > s.end) return 'over';
  if (s.end - n <= 30) return 'ending';
  return 'live';
}
function sessCalc(fs, s) {
  const r = ROOM[s.room];
  const el = nowMin() - s.start;
  const billMin = s.kind === 'exclusive' ? Math.max(el, s.end - s.start) : el;
  const rate = r.rate * (s.kind === 'shared' ? s.party : 1);
  const time = rate * billMin / 60;
  const items = s.items.reduce((a, it) => a + pprice(fs, it.p) * it.q, 0);
  return { el, billMin, rate, time, items, total: time + items };
}
function sessBadge(s) {
  const st = sessStatus(s);
  if (st === 'over') return badge('b-danger', L('Overtime', 'تجاوز الوقت'));
  if (st === 'ending') return badge('b-warn', L('Ending soon', 'ينتهي قريبًا'));
  return badge('b-ok', L('Live', 'جارية'), { pulse: true });
}
function roomState(r, fs) {
  const n = nowMin();
  if (r.status === 'maintenance') return { cls: 'b-neutral', label: L('Out of service', 'خارج الخدمة'), detail: L('AC maintenance until tomorrow', 'صيانة التكييف حتى الغد'), sc: 'var(--text-3)' };
  if (r.type === 'shared') { const used = fs ? fs.sessions.filter(s => s.room === 'sh').reduce((a, s) => a + s.party, 0) + 17 : r.used; return { cls: 'b-info', label: L('Open', 'مفتوحة'), detail: L(`${used} of ${r.cap} seats taken`, `${used} من ${r.cap} مقعد مشغول`), sc: 'var(--info)', used }; }
  if (r.status === 'occupied') {
    if (r.until < n) return { cls: 'b-danger', label: L('Overtime', 'تجاوز الوقت'), detail: L(`${Math.round(n - r.until)} min past booking`, `${Math.round(n - r.until)} دقيقة بعد الحجز`), sc: 'var(--danger)' };
    return { cls: 'b-info', label: L('In use', 'مشغولة'), detail: L(`Until ${fmtTime(r.until)}`, `حتى ${fmtTime(r.until)}`), sc: 'var(--info)' };
  }
  if (r.status === 'reserved') return { cls: 'b-warn', label: L('Reserved', 'محجوزة'), detail: L(`Next booking ${fmtTime(r.next)}`, `الحجز القادم ${fmtTime(r.next)}`), sc: 'var(--warn)' };
  return { cls: 'b-ok', label: L('Available', 'متاحة'), detail: r.next ? L(`Free until ${fmtTime(r.next)}`, `متاحة حتى ${fmtTime(r.next)}`) : L('Free for the rest of the day', 'متاحة لباقي اليوم'), sc: 'var(--ok)' };
}
const bookingStatus = st => ({
  live: badge('b-info', L('In progress', 'جارٍ')),
  confirmed: badge('b-ok', L('Confirmed', 'مؤكد')),
  pending: badge('b-warn', L('Pending', 'قيد الانتظار')),
  done: badge('b-neutral', L('Completed', 'مكتمل')),
}[st]);
const txStatus = st => ({
  paid: badge('b-ok', L('Paid', 'مدفوع')),
  pending: badge('b-warn', L('Pending', 'معلق')),
  refunded: badge('b-neutral', L('Refunded', 'مسترد')),
}[st]);

/* ================================================================ SHELL */
function frameHTML(fid) {
  const fs = FS[fid], c = CMAP[fs.cid];
  const dir = LANG === 'ar' ? 'rtl' : 'ltr';
  return `<div class="theme app c-${c.id} ${c.shell === 'top' ? 'topnav' : ''} ${fs.collapsed ? 'collapsed' : ''}" dir="${dir}" lang="${LANG}" data-fid="${fid}">
    ${c.shell === 'top' ? '' : sidebar(fs, c)}
    <div class="main">${topbar(fs, c)}<div class="content"><div class="content-in">${screenHTML(fs, c)}</div></div>${tabbar(fs)}</div>
    ${fs.modal ? overlay(fs, fs.modal, false) : ''}
    ${fs.toast ? `<div class="toast" role="status">${ic('check')}<span>${fs.toast.msg}</span>${fs.toast.undo ? `<button type="button" data-act="undo">${L('Undo', 'تراجع')}</button>` : ''}</div>` : ''}
  </div>`;
}
const navActive = fs => (fs.screen === 'create' ? 'bookings' : fs.screen === 'checkout' ? 'sessions' : fs.screen);
function navItem(fs, n, c) {
  const on = navActive(fs) === n.k;
  const cnt = n.cnt ? `<span class="cnt lbl">${fs.sessions.length}</span>` : (c.cmdk ? `<span class="cnt lbl"><span class="kbd">${n.key}</span></span>` : '');
  return `<button type="button" class="nav-item ${on ? 'on' : ''}" data-act="nav" data-v="${n.k}" title="${esc(L(n.en, n.ar))}">${ic(n.ic)}<span class="lbl">${L(n.en, n.ar)}</span>${cnt}</button>`;
}
function sidebar(fs, c) {
  let nav;
  if (c.navGroups) nav = c.navGroups.map(([lab, keys]) => `<div class="nav-label lbl">${L(lab[0], lab[1])}</div>${keys.map(k => navItem(fs, NAVMAP[k], c)).join('')}`).join('');
  else nav = NAV.map(n => navItem(fs, n, c)).join('');
  return `<aside class="side">
    <div class="brandrow"><div class="logo">LS</div><div class="lbl"><div class="ws-name">Link Space</div><div class="ws-sub">${L('Nasr City branch', 'فرع مدينة نصر')}</div></div></div>
    <nav class="nav">${nav}</nav>
    <div class="nav" style="margin-top:8px">${navItem(fs, { k: 'settings', ic: 'gear', en: 'Settings', ar: 'الإعدادات', key: ',' }, c)}</div>
    <div class="side-foot"><div class="avatar sm">NS</div><div class="lbl" style="min-width:0"><div class="ws-name trunc">${L('Nada Samir', 'ندى سمير')}</div><div class="ws-sub">${L('Front desk', 'الاستقبال')}</div></div></div>
  </aside>`;
}
function screenTitle(k) { const s = screenDef(k); if (s) return L(s.en, s.ar); if (k === 'customers') return L('Customers', 'العملاء'); return L('Settings', 'الإعدادات'); }
function topbar(fs, c) {
  if (HOOKS.topbar[c.id]) return HOOKS.topbar[c.id](fs, c);
  const collapse = btn('', { kind: 'ghost', iconOnly: true, icon: 'side', act: 'collapse', title: L('Collapse sidebar', 'طي القائمة'), cls: 'hide-t' });
  const bell = `<button type="button" class="btn btn-ghost btn-icon iconbtn" title="${L('Notifications', 'الإشعارات')}" aria-label="${L('Notifications', 'الإشعارات')}" data-act="toast" data-v="${esc(L('3 unread notifications', '3 إشعارات غير مقروءة'))}">${ic('bell')}<span class="ndot"></span></button>`;
  const mTitle = `<div class="show-m strong" style="font-size:15px">${screenTitle(fs.screen)}</div>`;
  const mLogo = `<div class="show-m"><div class="logo" style="width:26px;height:26px">LS</div></div>`;
  if (c.shell === 'top') {
    return `<header class="topbar">
      <div class="top-brand"><div class="logo">LS</div><div class="ws-name hide-m">Link Space</div></div>
      ${mTitle}
      <nav class="hnav">${NAV.filter(n => n.k !== 'customers').map(n => navItem(fs, n, c)).join('')}</nav>
      <div class="grow"></div>
      ${btn('', { kind: 'ghost', iconOnly: true, icon: 'search', title: L('Search', 'بحث'), act: 'toast', v: L('Search opens here', 'البحث يظهر هنا'), cls: 'hide-m' })}${bell}<div class="avatar sm">NS</div>
    </header>`;
  }
  if (c.id === 'calm') return `<header class="topbar">${collapse}${mLogo}<div class="crumbs hide-m">Link Space <span>/</span> <b>${screenTitle(fs.screen)}</b></div>${mTitle}<div class="grow"></div><span class="faint sm hide-m num" data-t="now"></span>${bell}</header>`;
  if (c.id === 'warm') return `<header class="topbar">${collapse}${mLogo}${mTitle}<div class="grow"></div><div class="search hide-m" style="width:260px">${ic('search')}<input class="input" placeholder="${L('Search people, rooms, bookings…', 'ابحث عن عملاء، غرف، حجوزات…')}"></div>${bell}</header>`;
  if (c.id === 'ops') return `<header class="topbar">${collapse}${mLogo}${mTitle}<div class="search hide-m" style="width:380px">${ic('search')}<input class="input" placeholder="${L('Search customers, invoices, bookings', 'ابحث عن العملاء، الفواتير، الحجوزات')}"></div><div class="grow"></div><button type="button" class="btn btn-ghost hide-m">${L('Nasr City branch', 'فرع مدينة نصر')}${ic('chevD')}</button>${bell}<div class="avatar sm">NS</div></header>`;
  return `<header class="topbar">${collapse}${mLogo}${mTitle}<button type="button" class="cmd" data-act="open" data-v="cmdk">${ic('search')}<span class="grow">${L('Search or jump to…', 'ابحث أو انتقل إلى…')}</span><span class="kbd">⌘K</span></button><div class="grow"></div><span class="num strong hide-m" data-t="now"></span>${bell}</header>`;
}
function tabbar(fs) {
  const tabs = [['dashboard', 'home', 'Home', 'الرئيسية'], ['sessions', 'live', 'Sessions', 'الجلسات'], ['bookings', 'cal', 'Bookings', 'الحجوزات'], ['rooms', 'door', 'Rooms', 'الغرف'], ['financials', 'money', 'Money', 'المالية']];
  return `<nav class="tabbar">${tabs.map(([k, i, en, ar]) => `<button type="button" class="${navActive(fs) === k ? 'on' : ''}" data-act="nav" data-v="${k}">${ic(i)}<span>${L(en, ar)}</span></button>`).join('')}</nav>`;
}
function screenHTML(fs, c) {
  if (HOOKS.screen[c.id]) { const out = HOOKS.screen[c.id](fs, c); if (out != null) return out; }
  switch (fs.screen) {
    case 'dashboard': return scrDashboard(fs, c);
    case 'sessions': return scrSessions(fs, c);
    case 'bookings': return scrBookings(fs, c);
    case 'rooms': return scrRooms(fs, c);
    case 'financials': return scrFinancials(fs, c);
    case 'products': return scrProducts(fs, c);
    case 'create': return scrCreate(fs, c);
    case 'overlays': return scrOverlays(fs, c);
    default: return `<div class="ph">${emptyState(fs.screen === 'customers' ? 'users' : 'gear', L('Not part of this comparison', 'غير مشمول في هذه المقارنة'), L('This screen will follow the same system once a direction is chosen. Use the navigation to explore the eight compared screens.', 'ستتبع هذه الشاشة نفس النظام بعد اختيار الاتجاه. استخدم القائمة لاستعراض الشاشات الثماني.'), btn(L('Back to dashboard', 'العودة للوحة التحكم'), { act: 'nav', v: 'dashboard' }))}</div>`;
  }
}

/* ============================================================ DASHBOARD */
function kpiData(fs) {
  const shared = fs.sessions.filter(s => s.kind === 'shared').length;
  return [
    { l: L('Revenue today', 'إيرادات اليوم'), v: money(8420), d: `<span class="up">+12%</span> ${L('vs last Sunday', 'مقارنة بالأحد الماضي')}` },
    { l: L('Today’s bookings', 'حجوزات اليوم'), v: '14', d: L('3 still pending confirmation', '3 بانتظار التأكيد') },
    { l: L('Active sessions', 'الجلسات النشطة'), v: String(fs.sessions.length), d: L(`${shared} shared · ${fs.sessions.length - shared} private`, `${shared} مشتركة · ${fs.sessions.length - shared} خاصة`) },
    { l: L('Occupancy now', 'الإشغال الآن'), v: '68%', d: L('3 of 7 rooms · 23 of 40 seats', '3 من 7 غرف · 23 من 40 مقعد'), meter: 68 },
  ];
}
const kpiCards = fs => `<div class="grid g-kpi">${kpiData(fs).map(k => `<div class="card kpi"><div class="kpi-l">${k.l}</div><div class="kpi-v">${k.v}</div>${k.meter ? `<div class="meter" style="margin-top:10px"><i style="width:${k.meter}%"></i></div>` : ''}<div class="kpi-d">${k.d}</div></div>`).join('')}</div>`;
function overtimeBanner(fs) {
  const s = fs.sessions.find(x => sessStatus(x) === 'over');
  if (!s) return '';
  const over = Math.round(nowMin() - s.end);
  return `<div class="banner warn" style="margin-bottom:20px">${ic('alert')}<div class="grow"><b>${L(`${rn(ROOM[s.room])} is ${over} min past its booking`, `${rn(ROOM[s.room])} تجاوز الحجز بـ ${over} دقيقة`)}</b><span class="muted">${L(`${pn(s.who)} is still inside. ${pn('mahmoud')} is booked there at ${fmtTime(810)}.`, `${pn(s.who)} ما زال بالداخل. ${pn('mahmoud')} لديه حجز هناك الساعة ${fmtTime(810)}.`)}</span></div>${btn(L('Open session', 'فتح الجلسة'), { sm: true, act: 'nav', v: 'sessions' })}</div>`;
}
function chartHTML(n, kind = 'all', stacked = true) {
  const b = REV.b.slice(-n), p = REV.p.slice(-n);
  const val = i => (kind === 'booking' ? b[i] : kind === 'product' ? p[i] : b[i] + p[i]);
  const max = Math.max(...b.map((_, i) => val(i)));
  const top = Math.ceil(max / 2500) * 2500;
  const gls = [0, .5, 1].map(f => `<div class="gl" style="bottom:${f * 100}%"><span>${f ? (top * f / 1000) + 'k' : '0'}</span></div>`).join('');
  const bars = b.map((_, i) => {
    const d = dayDate(i - n + 1);
    const label = fmtDate(d, { weekday: 'short', day: 'numeric', month: 'short' });
    const s1 = kind === 'product' ? 0 : b[i], s2 = kind === 'booking' ? 0 : p[i];
    const h1 = s1 / top * 100, h2 = s2 / top * 100;
    const segs = stacked && kind === 'all'
      ? `<i class="s2" style="height:${h2}%"></i><i class="s1" style="height:${h1}%"></i>`
      : `<i class="${kind === 'product' ? 's2' : 's1'} solo" style="height:${(s1 + s2) / top * 100}%"></i>`;
    const tip = `<div class="bar-tip"><b>${label}${i === n - 1 ? ' · ' + L('so far', 'حتى الآن') : ''}</b><br>${kind !== 'product' ? `<i class="sw" style="background:var(--data1)"></i>${L('Bookings', 'الحجوزات')} ${money(b[i])}<br>` : ''}${kind !== 'booking' ? `<i class="sw" style="background:var(--data2)"></i>${L('Products', 'المنتجات')} ${money(p[i])}<br>` : ''}${kind === 'all' ? `<b>${L('Total', 'الإجمالي')} ${money(b[i] + p[i])}</b>` : ''}</div>`;
    return `<div class="bar ${i === n - 1 ? 'today' : ''}">${segs}${tip}</div>`;
  }).join('');
  const every = n > 14 ? 5 : n > 7 ? 2 : 1;
  const xs = b.map((_, i) => `<span>${(n - 1 - i) % every === 0 ? (i === n - 1 ? L('Today', 'اليوم') : fmtDate(dayDate(i - n + 1), { day: 'numeric' })) : ''}</span>`).join('');
  return `<div class="chart" role="img" aria-label="${L('Daily revenue', 'الإيرادات اليومية')}">${gls}${bars}</div><div class="chart-x">${xs}</div>`;
}
const chartLegend = (kind = 'all') => `<div class="legend">${kind !== 'product' ? `<span><i style="--lg:var(--data1)"></i>${L('Bookings', 'الحجوزات')}</span>` : ''}${kind !== 'booking' ? `<span><i style="--lg:var(--data2)"></i>${L('Products', 'المنتجات')}</span>` : ''}</div>`;
function upNextList(o = {}) {
  const n = nowMin();
  const list = BOOKINGS_TODAY.filter(b => b.s > n - 1).sort((a, b) => a.s - b.s).slice(0, o.max || 5);
  return `<div class="list">${list.map(b => `<div class="li"><div class="num strong" style="width:68px">${fmtTime(b.s)}</div><div class="grow" style="min-width:0"><div class="strong trunc">${pn(b.who)}</div><div class="sm muted trunc">${rn(ROOM[b.room])} · ${fmtDur(b.e - b.s)}</div></div>${bookingStatus(b.st)}${o.act !== false ? `<span class="hide-m">${btn(L('Check in', 'تسجيل دخول'), { sm: true, kind: 'ghost', act: 'toast', v: L(`${pn(b.who)} checked in`, `تم تسجيل دخول ${pn(b.who)}`) })}</span>` : ''}</div>`).join('')}</div>`;
}
function roomsNowList(fs) {
  return `<div class="list">${ROOMS.map(r => { const st = roomState(r, fs); return `<div class="li"><div class="grow" style="min-width:0"><div class="strong trunc">${rn(r)}</div><div class="sm muted trunc">${st.detail}</div></div>${badge(st.cls, st.label)}</div>`; }).join('')}</div>`;
}
const activityList = (max = 6) => `<div class="list">${ACTIVITY.slice(0, max).map(a => `<div class="li" style="align-items:flex-start"><span class="feed-dot" style="--fd:var(--${a.c});margin-top:7px"></span><div class="grow sm" style="font-size:inherit">${L(a.en, a.ar)}</div><span class="act-time">${fmtTime(a.t)}</span></div>`).join('')}</div>`;

function scrDashboard(fs, c) {
  const dateStr = fmtDate(TODAY, { weekday: 'long', day: 'numeric', month: 'long' });
  const newB = btn(L('New booking', 'حجز جديد'), { kind: 'primary', icon: 'plus', act: 'nav', v: 'create', kbd: c.cmdk ? 'N' : '' });
  const startS = btn(L('Start session', 'بدء جلسة'), { icon: 'play', act: 'open', v: 'start' });
  const revCard = (n = 14) => card(L('Revenue · last 14 days', 'الإيرادات · آخر 14 يومًا'), chartHTML(n) + `<div style="margin-top:12px">${chartLegend()}</div>`, { right: btn(L('Financials', 'المالية'), { sm: true, kind: 'ghost', act: 'nav', v: 'financials', icon: 'arrowR', flip: true }) });

  if (c.dash === 'strip') {
    return pageHead(L('Dashboard', 'لوحة التحكم'), dateStr, startS + newB)
      + `<div class="kpi-strip">${kpiData(fs).map(k => `<div class="kpi"><div class="kpi-l">${k.l}</div><div class="kpi-v">${k.v}</div><div class="kpi-d">${k.d}</div></div>`).join('')}</div>`
      + overtimeBanner(fs)
      + `<div class="grid g-2"><div class="stack">${revCard()}${card(L('Up next today', 'التالي اليوم'), upNextList(), { flush: true })}</div><div class="stack">${card(L('Rooms right now', 'الغرف الآن'), roomsNowList(fs), { flush: true })}${card(L('Activity', 'النشاط'), activityList(5), { flush: true })}</div></div>`;
  }
  if (c.dash === 'narrative') {
    const hr = Math.floor(nowMin() / 60);
    const greet = hr < 12 ? L('Good morning', 'صباح الخير') : hr < 17 ? L('Good afternoon', 'مساء الخير') : L('Good evening', 'مساء الخير');
    const tiles = [
      ['primary', 'nav', 'create', L('New booking', 'حجز جديد'), L('Reserve a room for later', 'احجز غرفة لوقت لاحق')],
      ['', 'open', 'start', L('Start a walk-in', 'بدء جلسة حضور'), L('Seat someone right now', 'استقبل عميلًا الآن')],
      ['', 'nav', 'sessions', L('Check someone out', 'إنهاء جلسة'), L(`${fs.sessions.length} sessions running`, `${fs.sessions.length} جلسات جارية`)],
      ['', 'open', 'addItems:1', L('Add a product', 'إضافة منتج'), L('Coffee, snacks, printing', 'قهوة، سناكس، طباعة')],
    ];
    const timeline = BOOKINGS_TODAY.filter(b => b.e > nowMin() - 60).sort((a, b) => a.s - b.s).slice(0, 6).map(b => `<div class="tl-item ${b.st === 'live' ? 'now' : ''}"><div class="t">${fmtTime(b.s)}</div><span class="d"></span><div><div class="strong">${pn(b.who)} <span class="muted" style="font-weight:400">· ${rn(ROOM[b.room])}</span></div><div class="sm muted">${fmtTime(b.s)} – ${fmtTime(b.e)} · ${bookingStatus(b.st)}</div></div></div>`).join('');
    return `<div style="margin:8px 0 0"><div class="sm faint">${dateStr}</div><h1 class="greet" style="margin-top:6px">${greet}, ${L('Nada', 'ندى')}</h1>
      <p class="narr">${L(`<b>${fs.sessions.length} sessions</b> are running and <b>4 bookings</b> are still to come today. You’re <b>EGP 910 ahead</b> of last Sunday. One thing needs you: <b>Office 01</b> ran past its booking.`, `هناك <b>${fs.sessions.length} جلسات</b> جارية و<b>4 حجوزات</b> متبقية اليوم. أنت متقدم بـ <b>910 ج.م</b> عن الأحد الماضي. أمر واحد يحتاجك: <b>مكتب 01</b> تجاوز وقت حجزه.`)}</p></div>
      <div class="qtiles">${tiles.map(([cls, a, v, t, s]) => `<button type="button" class="qtile ${cls}" data-act="${a}" data-v="${v}"><b>${t}</b><span>${s}</span></button>`).join('')}</div>
      ${overtimeBanner(fs)}${kpiCards(fs)}
      <div class="grid g-2" style="margin-top:var(--gap)"><div class="stack">${card(L('Today’s schedule', 'جدول اليوم'), `<div class="timeline">${timeline}</div>`, { right: btn(L('Open calendar', 'فتح التقويم'), { sm: true, kind: 'ghost', act: 'nav', v: 'bookings' }) })}${revCard()}</div><div class="stack">${card(L('What happened today', 'ماذا حدث اليوم'), activityList(6), { flush: true })}</div></div>`;
  }
  if (c.dash === 'finance') {
    const tx = TX.filter(t => t.d === 0).slice(0, 5);
    return pageHead(L('Dashboard', 'لوحة التحكم'), dateStr, startS + newB)
      + overtimeBanner(fs)
      + `<section class="card hero-rev" style="margin-bottom:var(--gap)"><div class="card-b"><div class="between"><div><div class="kpi-l">${L('Revenue today', 'إيرادات اليوم')}</div><div class="hero-num" style="margin-top:8px">${money(8420)}</div><div class="ctx" style="margin-top:8px"><span class="up">+ ${money(910)} (12.1%)</span> ${L('vs last Sunday · updated 1 min ago', 'مقارنة بالأحد الماضي · آخر تحديث منذ دقيقة')}</div></div>${chartLegend()}</div><div style="margin-top:24px">${chartHTML(14)}</div></div>
        <div class="hero-side"><div><div class="kpi-l">${L('Bookings', 'الحجوزات')}</div><div class="kpi-v" style="font-size:20px">${money(6380)}</div><div class="ctx">76% ${L('of today', 'من اليوم')}</div></div><div><div class="kpi-l">${L('Products', 'المنتجات')}</div><div class="kpi-v" style="font-size:20px">${money(2040)}</div><div class="ctx">24% ${L('of today', 'من اليوم')}</div></div><div><div class="kpi-l">${L('Outstanding', 'مستحقات جارية')}</div><div class="kpi-v" style="font-size:20px">${money(fs.sessions.reduce((a, s) => a + sessCalc(fs, s).total, 0))}</div><div class="ctx">${L(`in ${fs.sessions.length} open sessions`, `في ${fs.sessions.length} جلسات مفتوحة`)}</div></div></div></section>`
      + kpiCards(fs).replace(`<div class="kpi-l">${L('Revenue today', 'إيرادات اليوم')}</div><div class="kpi-v">${money(8420)}</div>`, `<div class="kpi-l">${L('Avg. ticket', 'متوسط الفاتورة')}</div><div class="kpi-v">${money(327)}</div>`).replace(`<span class="up">+12%</span> ${L('vs last Sunday', 'مقارنة بالأحد الماضي')}`, `<span class="up">+4.3%</span> ${L('vs 30-day avg.', 'مقارنة بمتوسط 30 يومًا')}`)
      + `<div class="grid g-2" style="margin-top:var(--gap)">${card(L('Recent transactions', 'أحدث المعاملات'), txTable(tx, true), { flush: true, right: btn(L('View all', 'عرض الكل'), { sm: true, kind: 'ghost', act: 'nav', v: 'financials' }) })}${card(L('Activity', 'النشاط'), activityList(5), { flush: true })}</div>`;
  }
  if (c.dash === 'board') {
    const tiles = ROOMS.map(r => { const st = roomState(r, fs); return `<button type="button" class="btile" style="--sc:${st.sc}" data-act="nav" data-v="rooms"><div class="between"><b class="trunc">${rn(r)}</b></div><span class="tt" style="--tc:var(--t-${r.type})">${typeName(r.type)}</span><div>${badge(st.cls, st.label)}</div><div class="when">${st.detail}</div></button>`; }).join('');
    return pageHead(L('Today at Link Space', 'اليوم في لينك سبيس'), dateStr, '')
      + `<div class="bigact" style="margin-bottom:24px"><button type="button" class="primary" data-act="nav" data-v="create">${ic('plus')}${L('New booking', 'حجز جديد')}</button><button type="button" data-act="open" data-v="start">${ic('play')}${L('Start walk-in session', 'بدء جلسة حضور')}</button><button type="button" data-act="nav" data-v="sessions">${ic('receipt')}${L('Check out a customer', 'إنهاء جلسة عميل')}</button></div>`
      + overtimeBanner(fs)
      + `<div class="between" style="margin-bottom:12px"><h3 class="sec-title">${L('Right now', 'الآن')}</h3><span class="sm muted">${L('Updated live', 'تحديث مباشر')}</span></div><div class="board" style="margin-bottom:28px">${tiles}</div>`
      + `<div class="grid g-2"><div class="stack">${card(L('Arriving next', 'الوصول التالي'), upNextList({ max: 5 }), { flush: true })}</div><div class="stack">${kpiCards(fs).replace('g-kpi', 'g-2e')}${card(L('Activity', 'النشاط'), activityList(4), { flush: true })}</div></div>`;
  }
  // display (bold)
  const disp = kpiData(fs).map(k => `<div><div class="l">${k.l}</div><div class="v">${k.v}</div><div class="kpi-d">${k.d}</div></div>`).join('');
  const qa = [['create', 'nav', 'plus', L('New booking', 'حجز جديد'), 'N'], ['start', 'open', 'play', L('Start walk-in session', 'بدء جلسة حضور'), 'W'], ['sessions', 'nav', 'receipt', L('Check out a session', 'إنهاء جلسة'), 'O'], ['addItems:1', 'open', 'cup', L('Add product to a session', 'إضافة منتج لجلسة'), 'A'], ['cmdk', 'open', 'search', L('Find a customer', 'البحث عن عميل'), '⌘K']];
  return pageHead(L('Today', 'اليوم'), `${dateStr} · <span class="num" data-t="now"></span>`, startS + newB)
    + `<div class="display">${disp}</div>` + overtimeBanner(fs)
    + `<div class="grid g-2"><div class="stack">${revCard()}${card(L('Activity', 'النشاط'), activityList(5), { flush: true })}</div><div class="stack">${card(L('Quick actions', 'إجراءات سريعة'), `<div class="list qlist">${qa.map(([v, a, i, t, k]) => `<div class="li" data-act="${a}" data-v="${v}">${ic(i)}<span class="grow">${t}</span><span class="kbd">${k}</span></div>`).join('')}</div>`, { flush: true })}${card(L('Rooms right now', 'الغرف الآن'), roomsNowList(fs), { flush: true })}</div></div>`;
}

/* ======================================================= ACTIVE SESSIONS */
function scrSessions(fs, c) {
  const list = fs.sessions;
  const types = ['training', 'studio', 'office', 'shared'];
  const cnt = t => list.filter(s => ROOM[s.room].type === t).length;
  const typeChip = { training: L('Training Room', 'قاعة تدريب'), studio: L('Studio', 'استوديو'), office: L('Office', 'مكتب'), shared: L('Shared Room', 'مساحة مشتركة') };
  let shown = list.filter(s => fs.sessFilter === 'all' || ROOM[s.room].type === fs.sessFilter);
  const q = fs.sessQ.trim().toLowerCase();
  if (q) shown = shown.filter(s => (PEOPLE[s.who][0] + ' ' + PEOPLE[s.who][1] + ' ' + ROOM[s.room].name.join(' ')).toLowerCase().includes(q));
  const rank = s => ({ over: 0, ending: 1, live: 2 })[sessStatus(s)];
  shown.sort((a, b) => rank(a) - rank(b) || a.start - b.start);
  const attention = list.filter(s => sessStatus(s) !== 'live').length;
  const running = list.reduce((a, s) => a + sessCalc(fs, s).total, 0);
  const sub = `${L('Live', 'مباشر')} · ${L('running total', 'الإجمالي الجاري')} <b class="num" style="color:var(--text)">${money(running)}</b>${attention ? ` · <span style="color:var(--danger)">${L(`${attention} need attention`, `${attention} تحتاج انتباه`)}</span>` : ''}`;
  let body;
  if (!list.length) body = card('', emptyState('live', L('No active sessions', 'لا توجد جلسات نشطة'), L('When a customer checks in, their session appears here with a live timer and running amount.', 'عند تسجيل دخول عميل تظهر جلسته هنا مع مؤقت ومبلغ مباشر.'), btn(L('Start session', 'بدء جلسة'), { kind: 'primary', icon: 'play', act: 'open', v: 'start' })));
  else if (!shown.length) body = card('', emptyState('search', L('No sessions match', 'لا توجد جلسات مطابقة'), q ? L(`Nothing found for “${esc(fs.sessQ)}”.`, `لا نتائج لـ «${esc(fs.sessQ)}».`) : L(`No one is in a ${typeChip[fs.sessFilter]} right now.`, `لا يوجد أحد في ${typeChip[fs.sessFilter]} الآن.`), btn(L('Show all rooms', 'عرض كل الغرف'), { act: 'sessReset' })));
  else body = `<div class="grid g-sessions">${shown.map(s => sessCard(fs, c, s)).join('')}</div>`;
  return pageHead(`${L('Active Sessions', 'الجلسات النشطة')} <span class="faint" style="font-weight:400">(${list.length})</span>`, sub, btn(L('Start session', 'بدء جلسة'), { kind: 'primary', icon: 'play', act: 'open', v: 'start', kbd: c.cmdk ? 'W' : '' }))
    + `<div class="toolbar">${chips([['all', L('All Rooms', 'كل الغرف'), list.length], ...types.map(t => [t, typeChip[t], cnt(t)])], fs.sessFilter, 'sessFilter')}<div class="grow"></div><div class="search hide-m" style="width:220px">${ic('search')}<input class="input" data-inp="sessQ" value="${esc(fs.sessQ)}" placeholder="${L('Find customer or room', 'ابحث عن عميل أو غرفة')}"></div></div>`
    + body;
}
function sessCard(fs, c, s) {
  const r = ROOM[s.room], st = sessStatus(s), k = sessCalc(fs, s);
  const kind = s.kind === 'exclusive'
    ? `${L('Private booking', 'حجز خاص')} · ${L('until', 'حتى')} ${fmtTime(s.end)}`
    : `${L('Shared', 'مشترك')} · ${L('per minute', 'بالدقيقة')} · ${s.party} ${s.party > 1 ? L('people', 'أشخاص') : L('person', 'شخص')}`;
  const amt = `data-t="amt" data-s="${s.start}" data-rate="${k.rate}" data-min="${s.kind === 'exclusive' ? s.end - s.start : 0}" data-x="${k.items}"`;
  const nItems = s.items.reduce((a, i) => a + i.q, 0);
  const exp = fs.sessExp[s.id];
  const items = s.items.length
    ? `<button type="button" class="toggle" data-act="sessExp" data-v="${s.id}" aria-expanded="${exp ? 'true' : 'false'}"><span>${nItems} ${L(nItems === 1 ? 'item' : 'items', 'منتج')} · <b class="num" style="color:var(--text)">${money(k.items)}</b></span>${ic(exp ? 'chevD' : 'chevR', 'flip')}</button>${exp ? `<ul>${s.items.map(i => `<li><span>${i.q}× ${pname(fs, i.p)}</span><span class="num">${money(pprice(fs, i.p) * i.q)}</span></li>`).join('')}</ul>` : ''}`
    : `<span class="faint">${L('No products yet', 'لا توجد منتجات بعد')}</span>`;
  let warn = '';
  if (st === 'over') {
    const nb = BOOKINGS_TODAY.find(b => b.room === s.room && b.s >= s.end);
    warn = `<div class="scard-warn">${ic('alert')}<span>${L(`${Math.round(nowMin() - s.end)} min over`, `متأخر ${Math.round(nowMin() - s.end)} دقيقة`)}${nb ? L(` · next booking ${fmtTime(nb.s)}`, ` · الحجز التالي ${fmtTime(nb.s)}`) : ''}</span></div>`;
  }
  const stats = c.bigAmount
    ? `<div class="stat"><div class="stat-l">${L('Started', 'البداية')}</div><div class="stat-v">${fmtTime(s.start)}</div></div><div class="stat dur"><div class="stat-l">${L('Duration', 'المدة')}</div><div class="stat-v" data-t="dur" data-s="${s.start}">${fmtDur(k.el)}</div></div><div class="stat"><div class="stat-l">${L('Rate', 'السعر')}</div><div class="stat-v">${money(k.rate)}<span class="faint" style="font-weight:400">/${L('h', 'س')}</span></div></div>`
    : `<div class="stat"><div class="stat-l">${L('Started', 'البداية')}</div><div class="stat-v">${fmtTime(s.start)}</div></div><div class="stat dur"><div class="stat-l">${L('Duration', 'المدة')}</div><div class="stat-v" data-t="dur" data-s="${s.start}">${fmtDur(k.el)}</div></div><div class="stat"><div class="stat-l">${L('Amount', 'المبلغ')}</div><div class="stat-v" ${amt}>${money(k.time + k.items)}</div></div>`;
  return `<article class="card scard ${st}" style="--tc:var(--t-${r.type})">
    <div class="scard-top"><div class="avatar">${initials(s.who)}</div><div class="grow" style="min-width:0"><button type="button" class="scard-name trunc" data-act="open" data-v="customer:${s.who}">${pn(s.who)}</button><div class="scard-room trunc">${rn(r)} · ${typeName(r.type)}</div></div>${sessBadge(s)}</div>
    <div class="timer-hero"><span data-t="clock" data-s="${s.start}">${fmtClock(k.el)}</span><small>${kind}</small></div>
    ${c.cmdk ? '' : `<div class="sm muted" style="padding:0 var(--pad) 12px;margin-top:-4px">${kind}</div>`}
    ${c.bigAmount ? `<div class="amt-line"><span class="sm muted">${L('Current amount', 'المبلغ الحالي')}</span><b ${amt}>${money(k.total)}</b></div>` : ''}
    <div class="scard-stats">${stats}</div>
    <div class="scard-items">${items}</div>
    ${warn}
    <div class="scard-act">${btn(L('Add product', 'إضافة منتج'), { icon: 'plus', act: 'open', v: 'addItems:' + s.id })}${btn(L('Check out', 'إنهاء وتحصيل'), { kind: 'primary', act: 'open', v: 'checkout:' + s.id })}</div>
  </article>`;
}

/* ============================================================== BOOKINGS */
function calRooms(fs) { return ROOMS.filter(r => r.type !== 'shared' && (fs.calRoom === 'all' || r.type === fs.calRoom)); }
function scrBookings(fs, c) {
  const d = dayDate(fs.calOff);
  const rel = fs.calOff === 0 ? L('Today', 'اليوم') : fs.calOff === 1 ? L('Tomorrow', 'غدًا') : fs.calOff === -1 ? L('Yesterday', 'أمس') : '';
  let label;
  if (fs.calView === 'day') label = `${rel ? rel + ' · ' : ''}${fmtDate(d, { weekday: 'short', day: 'numeric', month: 'short' })}`;
  else if (fs.calView === 'week') label = `${fmtDate(dayDate(fs.calOff), { day: 'numeric', month: 'short' })} – ${fmtDate(dayDate(fs.calOff + 6), { day: 'numeric', month: 'short' })}`;
  else label = fmtDate(d, { month: 'long', year: 'numeric' });
  const filterItems = [['all', L('All rooms', 'كل الغرف')], ['training', L('Training', 'تدريب')], ['studio', L('Studio', 'استوديو')], ['office', L('Office', 'مكتب')], ['meeting', L('Meeting', 'اجتماعات')]];
  const toolbar = `<div class="toolbar">${seg([['day', L('Day', 'يوم')], ['week', L('Week', 'أسبوع')], ['month', L('Month', 'شهر')]], fs.calView, 'calView')}
    <div class="row">${btn('', { iconOnly: true, icon: 'chevL', act: 'calNav', v: -1, sm: true, title: L('Previous', 'السابق'), flip: true })}${btn(L('Today', 'اليوم'), { sm: true, act: 'calNav', v: 0 })}${btn('', { iconOnly: true, icon: 'chevR', act: 'calNav', v: 1, sm: true, title: L('Next', 'التالي'), flip: true })}<b style="margin-inline-start:6px;white-space:nowrap">${label}</b></div>
    <div class="grow"></div>${chips(filterItems.map(([v, l]) => [v, l]), fs.calRoom, 'calRoom')}</div>`;
  let view;
  if (fs.calView === 'day') view = calDay(fs);
  else if (fs.calView === 'week') view = calWeek(fs);
  else view = calMonth(fs);
  const legend = `<div class="legend" style="margin:12px 0 24px"><span><i style="--lg:var(--info)"></i>${L('In progress', 'جارٍ')}</span><span><i style="--lg:var(--ok)"></i>${L('Confirmed', 'مؤكد')}</span><span><i style="--lg:var(--warn)"></i>${L('Pending', 'قيد الانتظار')}</span><span><i style="--lg:var(--border-strong)"></i>${L('Completed', 'مكتمل')}</span><span class="hide-m">${L('Click an empty hour to book it', 'اضغط على ساعة فارغة لحجزها')}</span></div>`;
  const n = nowMin();
  const upcoming = BOOKINGS_TODAY.filter(b => b.s > n).sort((a, b) => a.s - b.s).slice(0, 4);
  const bcards = `<div class="grid g-2e">${upcoming.map(b => `<div class="card card-b" style="display:grid;gap:10px"><div class="between"><span class="num strong">${fmtTime(b.s)} – ${fmtTime(b.e)}</span>${bookingStatus(b.st)}</div><div><div class="strong">${pn(b.who)}</div><div class="sm muted">${rn(ROOM[b.room])} · ${fmtDur(b.e - b.s)} · ${money(ROOM[b.room].rate * (b.e - b.s) / 60)}</div></div><div class="row">${b.st === 'pending' ? btn(L('Confirm', 'تأكيد'), { sm: true, kind: 'primary', act: 'toast', v: L('Booking confirmed', 'تم تأكيد الحجز') }) : btn(L('Check in', 'تسجيل دخول'), { sm: true, act: 'toast', v: L(`${pn(b.who)} checked in`, `تم تسجيل دخول ${pn(b.who)}`) })}${btn(L('Details', 'التفاصيل'), { sm: true, kind: 'ghost', act: 'open', v: 'customer:' + b.who })}</div></div>`).join('')}</div>`;
  const avail = `<div class="avail">${ROOMS.filter(r => r.type !== 'shared').map(r => { const bs = bookingsFor(r.id, 0); const off = r.status === 'maintenance'; return `<div class="avail-row"><div class="sm strong trunc">${rn(r)}</div><div class="avail-bar" style="${off ? 'background:var(--surface-2)' : ''}">${off ? '<i style="inset-inline:0;opacity:.25"></i>' : bs.map(b => `<i style="inset-inline-start:${(b.s - DAY_START) / (DAY_END - DAY_START) * 100}%;width:${(b.e - b.s) / (DAY_END - DAY_START) * 100}%"></i>`).join('')}<i class="nowmark" style="inset-inline-start:${(n - DAY_START) / (DAY_END - DAY_START) * 100}%"></i></div></div>`; }).join('')}<div class="tl-x" style="padding-inline-start:142px"><span>8 ${L('AM', 'ص')}</span><span>12 ${L('PM', 'م')}</span><span>4 ${L('PM', 'م')}</span><span>10 ${L('PM', 'م')}</span></div></div>`;
  return pageHead(L('Bookings', 'الحجوزات'), L('14 bookings today · 3 pending confirmation', '14 حجزًا اليوم · 3 بانتظار التأكيد'), btn(L('New booking', 'حجز جديد'), { kind: 'primary', icon: 'plus', act: 'nav', v: 'create', kbd: c.cmdk ? 'N' : '' }))
    + toolbar + view + legend
    + `<div class="grid g-2"><div><h3 class="sec-title" style="margin-bottom:12px">${L('Coming up today', 'القادم اليوم')}</h3>${bcards}</div>${card(L('Availability today', 'التوفر اليوم'), avail + `<div class="sm muted" style="margin-top:10px">${L('Green = free · grey = booked · red line = now', 'الأخضر = متاح · الرمادي = محجوز · الخط الأحمر = الآن')}</div>`)}</div>`;
}
function calDay(fs) {
  const rooms = calRooms(fs);
  if (!rooms.length) return card('', emptyState('cal', L('No rooms of this type', 'لا توجد غرف من هذا النوع'), L('Try another room filter.', 'جرّب فلترًا آخر.')));
  const H = 48, hours = (DAY_END - DAY_START) / 60;
  const times = Array.from({ length: hours }, (_, i) => `<div>${i ? fmtHour(8 + i) : ''}</div>`).join('');
  const n = nowMin();
  const cols = rooms.map(r => {
    const off = r.status === 'maintenance';
    const evs = bookingsFor(r.id, fs.calOff).map(b => { const top = (b.s - DAY_START) / 60 * H, h = (b.e - b.s) / 60 * H - 2; return `<div class="ev ${b.st}" style="top:${top}px;height:${h}px" data-act="open" data-v="customer:${b.who}"><b>${pn(b.who)}</b><span>${fmtTime(b.s)} – ${fmtTime(b.e)}</span></div>`; }).join('');
    const busy = busySlots(r.id, fs.calOff);
    const slots = off ? '' : Array.from({ length: hours }, (_, i) => { const past = fs.calOff < 0 || (fs.calOff === 0 && DAY_START + (i + 1) * 60 <= n); if (past || busy[i * 2] || busy[i * 2 + 1]) return ''; return `<button type="button" class="cal-slot" style="top:${i * H}px" data-act="calSlot" data-v="${r.id}:${i * 2}" data-label="+ ${L('Book', 'احجز')} ${fmtHour(8 + i)}" aria-label="${L('Book', 'احجز')} ${rn(r)} ${fmtHour(8 + i)}"></button>`; }).join('');
    const nowL = fs.calOff === 0 ? `<div class="now-line" style="top:${(n - DAY_START) / 60 * H}px"></div>` : '';
    return `<div class="cal-col ${off ? 'off' : ''}" style="height:${hours * H}px">${slots}${evs}${nowL}</div>`;
  }).join('');
  const heads = rooms.map(r => `<div class="cal-hd"><span class="trunc" style="display:block">${rn(r)}</span><small>${r.status === 'maintenance' ? L('Out of service', 'خارج الخدمة') : `${money(r.rate)}/${L('h', 'س')} · ${r.cap} ${L('seats', 'مقعد')}`}</small></div>`).join('');
  return `<div class="cal" style="grid-template-columns:56px repeat(${rooms.length}, minmax(128px, 1fr))"><div class="cal-corner"></div>${heads}<div class="cal-times">${times}</div>${cols}</div>`;
}
function calWeek(fs) {
  const rooms = calRooms(fs);
  const days = Array.from({ length: 7 }, (_, i) => fs.calOff + i);
  return `<div class="week">${days.map(d => {
    const evs = rooms.flatMap(r => bookingsFor(r.id, d)).sort((a, b) => a.s - b.s);
    const hrs = evs.reduce((a, b) => a + (b.e - b.s), 0) / 60;
    const occ = Math.min(100, Math.round(hrs / (rooms.length * 14 || 1) * 100));
    const shownE = evs.slice(0, 6);
    const date = dayDate(d);
    return `<div class="wday ${d === 0 ? 'today' : ''}"><div class="wday-h"><span class="faint">${fmtDate(date, { weekday: 'short' })}</span><b>${date.getDate()}</b><div class="meter" style="margin-top:6px"><i style="width:${occ}%"></i></div><span class="faint" style="font-size:11px">${occ}% ${L('booked', 'محجوز')}</span></div><div class="wday-b">${shownE.map(b => `<div class="wev ev ${b.st}" style="position:static" data-act="open" data-v="customer:${b.who}"><b>${fmtTime(b.s)}</b>${rn(ROOM[b.room])}<br><span>${pn(b.who)}</span></div>`).join('')}${evs.length > 6 ? `<button type="button" class="link sm" data-act="calDay" data-v="${d}">+${evs.length - 6} ${L('more', 'أخرى')}</button>` : ''}${!evs.length ? `<span class="faint sm">${L('No bookings', 'لا حجوزات')}</span>` : ''}</div></div>`;
  }).join('')}</div>`;
}
function calMonth(fs) {
  const base = dayDate(fs.calOff);
  const first = new Date(base.getFullYear(), base.getMonth(), 1);
  const startOff = Math.round((first - TODAY) / 864e5) - first.getDay();
  const rooms = calRooms(fs);
  const hdr = Array.from({ length: 7 }, (_, i) => `<div class="mh">${fmtDate(dayDate(startOff + i), { weekday: 'short' })}</div>`).join('');
  const cells = Array.from({ length: 35 }, (_, i) => {
    const off = startOff + i, date = dayDate(off);
    const out = date.getMonth() !== base.getMonth();
    const evs = rooms.flatMap(r => bookingsFor(r.id, off));
    const hrs = evs.reduce((a, b) => a + (b.e - b.s), 0) / 60;
    const occ = Math.min(100, Math.round(hrs / (rooms.length * 14 || 1) * 100));
    return `<button type="button" class="md ${out ? 'out' : ''} ${off === 0 ? 'today' : ''}" data-act="calDay" data-v="${off}"><span class="md-n">${date.getDate()}</span><span class="md-c">${evs.length ? `${evs.length} ${L('bookings', 'حجوزات')}` : ''}</span>${evs.length ? `<span class="meter" style="height:4px"><i style="width:${occ}%"></i></span>` : ''}</button>`;
  }).join('');
  return `<div class="month">${hdr}${cells}</div>`;
}

/* ================================================================= ROOMS */
function scrRooms(fs, c) {
  const types = ['all', 'training', 'studio', 'office', 'shared', 'meeting'];
  const cnt = t => (t === 'all' ? ROOMS.length : ROOMS.filter(r => r.type === t).length);
  const shown = ROOMS.filter(r => fs.roomType === 'all' || r.type === fs.roomType);
  const freeNow = ROOMS.filter(r => r.status === 'available').length;
  const cards = shown.map(r => {
    const st = roomState(r, fs);
    const bs = bookingsFor(r.id, 0);
    const bookedH = bs.reduce((a, b) => a + (b.e - b.s), 0) / 60;
    const util = r.type === 'shared' ? Math.round((st.used || r.used) / r.cap * 100) : Math.round(bookedH / 14 * 100);
    const menuId = 'room-' + r.id;
    const price = r.type === 'shared' ? `${money(r.rate)} <small>/ ${L('seat · hour', 'مقعد · ساعة')}</small>` : `${money(r.rate)} <small>/ ${L('hour', 'ساعة')}</small>`;
    const primaryAct = r.status === 'occupied' ? btn(L('View session', 'عرض الجلسة'), { sm: true, act: 'nav', v: 'sessions' }) : r.status === 'maintenance' ? btn(L('Back in service', 'إعادة للخدمة'), { sm: true, act: 'toast', v: L(`${rn(r)} is available again`, `${rn(r)} متاحة مجددًا`) }) : btn(L('Book', 'احجز'), { sm: true, act: 'bookRoom', v: r.id });
    return `<article class="card rcard" style="--tc:var(--t-${r.type})">
      <div class="rcard-art art">${roomArt(r.type)}${badge(st.cls, st.label)}</div>
      <div class="rcard-b"><div class="between" style="align-items:flex-start"><div style="min-width:0"><h3 style="font-size:var(--fs-h3)" class="trunc">${rn(r)}</h3><div class="sm ${c.roomArt ? 'tt' : 'muted'}">${typeName(r.type)} · ${L(r.floor[0], r.floor[1])}</div></div>${c.roomArt ? '' : badge(st.cls, st.label)}</div>
      <div class="sm muted">${st.detail}</div>
      <div class="rcard-meta"><span>${ic('seat')}${r.cap} ${r.type === 'shared' ? L('seats', 'مقعد') : L('people', 'شخص')}</span><span>${ic('clock')}${r.type === 'shared' ? L(`${util}% full`, `${util}% ممتلئة`) : L(`${fmtHours(bookedH)} booked today`, `${fmtHours(bookedH)} محجوزة اليوم`)}</span></div>
      <div class="meter"><i style="width:${r.status === 'maintenance' ? 0 : util}%"></i></div></div>
      <div class="rcard-f"><span class="price">${price}</span><div class="row">${primaryAct}<div class="rel">${btn('', { sm: true, iconOnly: true, kind: 'ghost', icon: 'more', act: 'menu', v: menuId, title: L('More actions', 'إجراءات أخرى') })}${fs.menu === menuId ? `<div class="menu"><button type="button" data-act="toast" data-v="${esc(L('Room editor opens here', 'محرر الغرفة يفتح هنا'))}">${ic('edit')}${L('Edit room', 'تعديل الغرفة')}</button><button type="button" data-act="calRoomGo" data-v="${r.type}">${ic('cal')}${L('View bookings', 'عرض الحجوزات')}</button><hr><button type="button" class="danger" data-act="toast" data-v="${esc(L(`${rn(r)} marked out of service`, `تم إيقاف ${rn(r)} عن الخدمة`))}">${ic('eyeoff')}${L('Mark out of service', 'إيقاف عن الخدمة')}</button></div>` : ''}</div></div></div>
    </article>`;
  }).join('');
  return pageHead(L('Rooms & Workspaces', 'الغرف ومساحات العمل'), L(`${ROOMS.length} rooms · ${freeNow} free right now · Nasr City branch`, `${ROOMS.length} غرف · ${freeNow} متاحة الآن · فرع مدينة نصر`), btn(L('Add room', 'إضافة غرفة'), { kind: 'primary', icon: 'plus', act: 'toast', v: L('Room form opens here', 'نموذج الغرفة يفتح هنا') }))
    + `<div class="toolbar">${chips(types.map(t => [t, t === 'all' ? L('All types', 'كل الأنواع') : typeName(t), cnt(t)]), fs.roomType, 'roomType')}</div>`
    + `<div class="grid g-rooms">${cards}</div>`;
}

/* ============================================================ FINANCIALS */
function txTable(rows, compact) {
  if (!rows.length) return emptyState('receipt', L('No transactions', 'لا توجد معاملات'), L('Nothing matches these filters. Try a different period or payment method.', 'لا شيء يطابق هذه الفلاتر. جرّب فترة أو طريقة دفع أخرى.'));
  return `<div class="tbl-wrap"><table class="tbl"><thead><tr>${compact ? '' : `<th class="hide-m">${L('Invoice', 'الفاتورة')}</th>`}<th>${L('Time', 'الوقت')}</th><th>${L('Customer', 'العميل')}</th>${compact ? '' : `<th class="hide-t">${L('Description', 'الوصف')}</th><th class="hide-m">${L('Method', 'الطريقة')}</th>`}<th class="hide-m">${L('Status', 'الحالة')}</th><th class="r">${L('Amount', 'المبلغ')}</th></tr></thead><tbody>${rows.map(t => `<tr>${compact ? '' : `<td class="hide-m"><span class="mono muted">${t.id}</span></td>`}<td class="num muted">${t.d === 0 ? '' : L('Yesterday ', 'أمس ')}${fmtTime(t.t)}</td><td>${pn(t.who)}</td>${compact ? '' : `<td class="hide-t muted trunc" style="max-width:260px">${L(t.what[0], t.what[1])}</td><td class="hide-m">${L(...METHODS[t.m])}</td>`}<td class="hide-m">${txStatus(t.st)}</td><td class="r amt" style="${t.amt < 0 ? 'color:var(--text-3)' : ''}">${t.amt < 0 ? '−' : ''}${money(Math.abs(t.amt))}</td></tr>`).join('')}</tbody></table></div>`;
}
function scrFinancials(fs, c) {
  const n = fs.finPeriod;
  const b = REV.b.slice(-n).reduce((a, x) => a + x, 0), p = REV.p.slice(-n).reduce((a, x) => a + x, 0);
  const total = b + p, txCount = n * 23 + 7;
  const tx = TX.filter(t => (fs.finKind === 'all' || t.kind === fs.finKind) && (fs.finMethod === 'all' || t.m === fs.finMethod));
  const k = [
    { l: L('Total revenue', 'إجمالي الإيرادات'), v: money(total), d: `<span class="up">+8.4%</span> ${L('vs previous period', 'مقارنة بالفترة السابقة')}` },
    { l: L('Booking revenue', 'إيرادات الحجوزات'), v: money(b), d: `${Math.round(b / total * 100)}% ${L('of total', 'من الإجمالي')} · <span class="up">+6.9%</span>` },
    { l: L('Product revenue', 'إيرادات المنتجات'), v: money(p), d: `${Math.round(p / total * 100)}% ${L('of total', 'من الإجمالي')} · <span class="up">+14.2%</span>` },
    { l: L('Average ticket', 'متوسط الفاتورة'), v: money(total / txCount), d: L(`${txCount.toLocaleString('en-US')} transactions`, `${txCount.toLocaleString('en-US')} معاملة`) },
  ];
  const skelK = `<div class="card kpi"><div class="skel" style="width:50%"></div><div class="skel" style="width:70%;height:24px;margin-top:12px"></div><div class="skel" style="width:40%;margin-top:10px"></div></div>`;
  const kpis = `<div class="grid g-kpi">${fs.loading ? skelK.repeat(4) : k.map(x => `<div class="card kpi"><div class="kpi-l">${x.l}</div><div class="kpi-v">${x.v}</div><div class="kpi-d">${x.d}</div></div>`).join('')}</div>`;
  const chart = fs.loading ? `<div class="skel" style="height:200px;border-radius:6px"></div>` : chartHTML(n, fs.finKind);
  const exportMenu = `<div class="rel">${btn(L('Export', 'تصدير'), { kind: 'primary', icon: 'download', act: 'menu', v: 'export' })}${fs.menu === 'export' ? `<div class="menu"><button type="button" data-act="export" data-v="CSV">${ic('download')}${L('CSV for Excel', 'ملف CSV لإكسل')}</button><button type="button" data-act="export" data-v="PDF">${ic('printer')}${L('PDF statement', 'كشف PDF')}</button></div>` : ''}</div>`;
  return pageHead(L('Financials', 'المالية'), L('Nasr City branch · all amounts in Egyptian pounds', 'فرع مدينة نصر · كل المبالغ بالجنيه المصري'), exportMenu)
    + `<div class="toolbar">${seg([[7, L('7 days', '7 أيام')], [14, L('14 days', '14 يومًا')], [30, L('30 days', '30 يومًا')]], fs.finPeriod, 'finPeriod')}${chips([['all', L('All revenue', 'كل الإيرادات')], ['booking', L('Bookings', 'الحجوزات')], ['product', L('Products', 'المنتجات')]], fs.finKind, 'finKind')}<div class="grow"></div><select class="input hide-m" style="width:170px" data-inp="finMethod" aria-label="${L('Payment method', 'طريقة الدفع')}"><option value="all">${L('All payment methods', 'كل طرق الدفع')}</option>${Object.entries(METHODS).map(([v, l]) => `<option value="${v}" ${fs.finMethod === v ? 'selected' : ''}>${L(...l)}</option>`).join('')}</select></div>`
    + kpis
    + `<div style="margin-top:var(--gap)">${card(L(`Revenue by day · last ${n} days`, `الإيرادات اليومية · آخر ${n} يومًا`), chart, { right: chartLegend(fs.finKind) })}</div>`
    + `<div style="margin-top:var(--gap)">${card(L('Recent transactions', 'أحدث المعاملات'), txTable(tx), { flush: true, right: `<span class="sm muted">${tx.length} ${L('shown', 'معروضة')}</span>` })}</div>`;
}

/* ============================================================== PRODUCTS */
function stockCell(p) {
  if (p.stock === null) return `<span class="faint">${L('Not tracked', 'غير متتبع')}</span>`;
  if (p.stock === 0) return badge('b-danger', L('Out of stock', 'نفد المخزون'));
  if (p.stock <= 5) return badge('b-warn', L(`Low · ${p.stock} left`, `منخفض · ${p.stock} متبقي`));
  return `<span class="num">${p.stock} ${L('in stock', 'بالمخزون')}</span>`;
}
function prodMenu(fs, p) {
  const id = 'prod-' + p.id;
  return `<div class="rel">${btn('', { sm: true, iconOnly: true, kind: 'ghost', icon: 'more', act: 'menu', v: id, title: L('More actions', 'إجراءات أخرى') })}${fs.menu === id ? `<div class="menu"><button type="button" data-act="open" data-v="product:${p.id}">${ic('edit')}${L('Edit', 'تعديل')}</button><button type="button" data-act="toast" data-v="${esc(L('Product duplicated', 'تم نسخ المنتج'))}">${ic('copy')}${L('Duplicate', 'نسخ')}</button><hr><button type="button" class="danger" data-act="open" data-v="del:${p.id}">${ic('trash')}${L('Delete…', 'حذف…')}</button></div>` : ''}</div>`;
}
function scrProducts(fs, c) {
  const q = fs.prodQ.trim().toLowerCase();
  const list = fs.products.filter(p => (fs.prodType === 'all' || p.type === fs.prodType) && (!q || (p.n.join(' ') + ' ' + p.sku).toLowerCase().includes(q)));
  const active = fs.products.filter(p => p.active).length;
  const cnt = t => fs.products.filter(p => p.type === t).length;
  const toolbar = `<div class="toolbar"><div class="search" style="width:260px;max-width:100%">${ic('search')}<input class="input" data-inp="prodQ" value="${esc(fs.prodQ)}" placeholder="${L('Search name or SKU', 'ابحث بالاسم أو الكود')}"></div>${chips([['all', L('All', 'الكل'), fs.products.length], ...Object.keys(PTYPES).map(t => [t, L(...PTYPES[t]), cnt(t)])], fs.prodType, 'prodType')}<div class="grow"></div><div class="hide-m">${seg([['list', L('List', 'قائمة')], ['grid', L('Grid', 'شبكة')]], fs.prodView, 'prodView')}</div></div>`;
  let body;
  if (!list.length) body = card('', emptyState('search', L('No products found', 'لا توجد منتجات'), q ? L(`Nothing matches “${esc(fs.prodQ)}”. Check the spelling or add it as a new product.`, `لا شيء يطابق «${esc(fs.prodQ)}». تحقق من الكتابة أو أضفه كمنتج جديد.`) : L('No products of this type yet.', 'لا توجد منتجات من هذا النوع بعد.'), btn(L('Clear search', 'مسح البحث'), { act: 'prodClear' }) + btn(L('Add product', 'إضافة منتج'), { kind: 'primary', icon: 'plus', act: 'open', v: 'product' })));
  else if (fs.prodView === 'grid') body = `<div class="grid g-products">${list.map(p => `<article class="card" style="padding:var(--pad);display:grid;gap:10px;${p.active ? '' : 'opacity:.72'}"><div class="between" style="align-items:flex-start"><div style="min-width:0"><div class="strong trunc">${L(p.n[0], p.n[1])}</div><div class="sm faint">${L(...PTYPES[p.type])} · <span class="mono">${p.sku}</span></div></div>${prodMenu(fs, p)}</div><div class="price" style="font-size:20px">${money(p.price)}</div><div class="sm">${stockCell(p)}</div><div class="between" style="border-top:1px solid var(--border-soft);padding-top:10px"><span class="sm muted">${p.active ? L('Visible at checkout', 'ظاهر عند الدفع') : L('Hidden', 'مخفي')}</span><button type="button" class="switch ${p.active ? 'on' : ''}" role="switch" aria-checked="${p.active}" data-act="prodToggle" data-v="${p.id}" aria-label="${L('Active', 'نشط')}"></button></div></article>`).join('')}</div>`;
  else body = card('', `<div class="tbl-wrap"><table class="tbl"><thead><tr><th>${L('Product', 'المنتج')}</th><th class="hide-m">${L('Type', 'النوع')}</th><th class="r">${L('Price', 'السعر')}</th><th class="hide-m">${L('Stock', 'المخزون')}</th><th class="r hide-t">${L('Sold · 30d', 'المباع · 30 يوم')}</th><th>${L('Active', 'نشط')}</th><th></th></tr></thead><tbody>${list.map(p => `<tr style="${p.active ? '' : 'color:var(--text-3)'}"><td><div class="strong" style="${p.active ? '' : 'color:var(--text-2)'}">${L(p.n[0], p.n[1])}</div><div class="sm faint mono">${p.sku}</div></td><td class="hide-m">${L(...PTYPES[p.type])}</td><td class="r amt">${money(p.price)}</td><td class="hide-m">${stockCell(p)}</td><td class="r num hide-t muted">${p.sold.toLocaleString('en-US')}</td><td><button type="button" class="switch ${p.active ? 'on' : ''}" role="switch" aria-checked="${p.active}" data-act="prodToggle" data-v="${p.id}" aria-label="${L('Active', 'نشط')}"></button></td><td class="r">${prodMenu(fs, p)}</td></tr>`).join('')}</tbody></table></div>`, { flush: true });
  return pageHead(L('Products', 'المنتجات'), L(`${fs.products.length} products · ${active} active · sold inside sessions and bookings`, `${fs.products.length} منتج · ${active} نشط · تُباع داخل الجلسات والحجوزات`), btn(L('Add product', 'إضافة منتج'), { kind: 'primary', icon: 'plus', act: 'open', v: 'product' }))
    + toolbar + body;
}

/* ======================================================= CREATE BOOKING */
const slotTime = i => DAY_START + i * 30;
function draftCalc(fs) {
  const d = fs.draft, r = ROOM[d.room];
  const hours = d.start !== null && d.end !== null ? (d.end - d.start) / 2 : 0;
  return { r, hours, price: hours * r.rate };
}
function scrCreate(fs, c) {
  const d = fs.draft, r = ROOM[d.room];
  const busy = busySlots(d.room, d.date);
  const bookable = ROOMS.filter(x => x.type !== 'shared');
  const rooms = `<div class="room-pick">${bookable.map(x => { const off = x.status === 'maintenance'; return `<button type="button" class="rp ${x.id === d.room ? 'on' : ''}" style="--tc:var(--t-${x.type})${off ? ';opacity:.5' : ''}" data-act="dRoom" data-v="${x.id}" ${off ? 'disabled' : ''}><div class="rp-art art">${roomArt(x.type)}</div><b>${rn(x)}</b><small>${typeName(x.type)} · ${x.cap} ${L('people', 'شخص')}</small><small class="num">${off ? L('Out of service', 'خارج الخدمة') : `${money(x.rate)}/${L('hour', 'ساعة')}`}</small></button>`; }).join('')}</div>`;
  const dates = `<div class="dates">${Array.from({ length: 14 }, (_, i) => { const dt = dayDate(i); return `<button type="button" class="date ${d.date === i ? 'on' : ''}" data-act="dDate" data-v="${i}"><small>${i === 0 ? L('Today', 'اليوم') : fmtDate(dt, { weekday: 'short' })}</small><b>${dt.getDate()}</b><small>${fmtDate(dt, { month: 'short' })}</small></button>`; }).join('')}</div>`;

  // time selection
  const picking = d.start === null ? 'start' : d.end === null ? 'end' : 'done';
  let limit = SLOTS;
  if (d.start !== null) { for (let i = d.start; i < SLOTS; i++) if (busy[i]) { limit = i; break; } }
  const boundary = i => {
    const t = slotTime(i), label = fmtTime(t);
    const past = pastSlot(d.date, i);
    if (picking === 'end') {
      if (i <= d.start) return `<button type="button" class="slot ${i === d.start ? 'pick' : 'dim'}" data-act="dSlot" data-v="${i}"><span>${label}</span>${i === d.start ? `<small>${L('start', 'البداية')}</small>` : ''}</button>`;
      if (i > limit) return `<button type="button" class="slot dim" disabled><span>${label}</span></button>`;
      return `<button type="button" class="slot" data-act="dSlot" data-v="${i}"><span>${label}</span><small>${fmtDur((i - d.start) * 30)}</small></button>`;
    }
    const isBusy = i < SLOTS && busy[i];
    const inRange = d.start !== null && d.end !== null && i > d.start && i < d.end;
    const isPick = i === d.start || i === d.end;
    if (i === SLOTS && !isPick) return `<button type="button" class="slot dim" disabled><span>${label}</span></button>`;
    if ((isBusy || past) && !isPick) return `<button type="button" class="slot busy" disabled title="${past ? L('In the past', 'وقت مضى') : L('Already booked', 'محجوز بالفعل')}"><span>${label}</span></button>`;
    return `<button type="button" class="slot ${isPick ? 'pick' : ''} ${inRange ? 'in' : ''}" data-act="dSlot" data-v="${i}"><span>${label}</span>${i === d.start ? `<small>${L('start', 'البداية')}</small>` : i === d.end ? `<small>${L('end', 'النهاية')}</small>` : ''}</button>`;
  };
  const groups = [[L('Morning', 'صباحًا'), 0, 8], [L('Afternoon', 'بعد الظهر'), 8, 18], [L('Evening', 'مساءً'), 18, SLOTS + 1]];
  const slotsHtml = groups.map(([lab, a, b]) => `<div class="slot-group"><span class="flabel sm">${lab}</span><div class="slots">${Array.from({ length: b - a }, (_, k) => boundary(a + k)).join('')}</div></div>`).join('');
  const tlBusy = busy.map((x, i) => (x ? `<i class="busy" style="inset-inline-start:${i / SLOTS * 100}%;width:${100 / SLOTS}%"></i>` : '')).join('');
  const tlSel = d.start !== null && d.end !== null ? `<i class="sel" style="inset-inline-start:${d.start / SLOTS * 100}%;width:${(d.end - d.start) / SLOTS * 100}%"></i>` : '';
  const timeline = `<div class="tl">${tlBusy}${tlSel}</div><div class="tl-x"><span>8 ${L('AM', 'ص')}</span><span>11 ${L('AM', 'ص')}</span><span>2 ${L('PM', 'م')}</span><span>5 ${L('PM', 'م')}</span><span>10 ${L('PM', 'م')}</span></div>`;
  const helper = picking === 'start' ? L('Tap a start time.', 'اختر وقت البداية.') : picking === 'end' ? L(`Now tap an end time — each option shows the total duration. Free until ${fmtTime(slotTime(limit))}.`, `اختر وقت النهاية — كل خيار يعرض المدة الإجمالية. متاح حتى ${fmtTime(slotTime(limit))}.`) : L(`${fmtTime(slotTime(d.start))} – ${fmtTime(slotTime(d.end))} selected. Tap any time to start over.`, `تم اختيار ${fmtTime(slotTime(d.start))} – ${fmtTime(slotTime(d.end))}. اضغط أي وقت للبدء من جديد.`);
  const durs = [[2, '1h', '1 س'], [4, '2h', '2 س'], [6, '3h', '3 س'], [8, L('Half day · 4h', 'نصف يوم · 4 س'), ''], [16, L('Full day · 8h', 'يوم كامل · 8 س'), '']];
  const durChips = d.start !== null ? `<div class="dur-chips"><span class="sm muted" style="margin-inline-end:4px">${L('Quick duration', 'مدة سريعة')}</span>${durs.map(([n, en, ar]) => { const ok = d.start + n <= limit; const on = d.end !== null && d.end - d.start === n; return `<button type="button" class="chip ${on ? 'on' : ''}" data-act="dDur" data-v="${n}" ${ok ? '' : 'disabled style="opacity:.4;cursor:not-allowed"'}>${ar ? L(en, ar) : en}</button>`; }).join('')}</div>` : '';

  // customer
  const q = d.custQ.trim().toLowerCase();
  const custs = PKEYS.filter(k => !q || (PEOPLE[k].join(' ')).toLowerCase().includes(q)).slice(0, q ? 6 : 4);
  const custHtml = d.newCust
    ? `<div class="grid g-2e"><div class="field"><label>${L('Full name', 'الاسم بالكامل')}</label><input class="input" placeholder="${L('e.g. Youssef Adel', 'مثال: يوسف عادل')}"></div><div class="field"><label>${L('Mobile number', 'رقم الموبايل')}</label><input class="input num" placeholder="01x xxxx xxxx" dir="ltr"><span class="hint">${L('Also used as their Wi-Fi username', 'يُستخدم أيضًا كاسم مستخدم الواي فاي')}</span></div></div><button type="button" class="link sm" style="margin-top:10px" data-act="dNewCust">${L('← Pick an existing customer', '← اختر عميلًا موجودًا')}</button>`
    : `<div class="search" style="margin-bottom:10px">${ic('search')}<input class="input" data-inp="d.custQ" value="${esc(d.custQ)}" placeholder="${L('Search by name or mobile', 'ابحث بالاسم أو الموبايل')}"></div><div class="flabel sm faint" style="margin-bottom:6px">${q ? L('Results', 'النتائج') : L('Recent customers', 'عملاء حديثون')}</div><div class="cust-list">${custs.map(k => `<button type="button" class="cust ${d.cust === k ? 'on' : ''}" data-act="dCust" data-v="${k}"><span class="avatar sm">${initials(k)}</span><span class="grow"><b style="font-weight:600">${pn(k)}</b><br><span class="sm faint num" dir="ltr">${PEOPLE[k][2]}</span></span>${d.cust === k ? ic('check') : ''}</button>`).join('') || `<span class="sm muted">${L('No match.', 'لا نتائج.')}</span>`}</div><button type="button" class="link sm" style="margin-top:10px" data-act="dNewCust">+ ${L('New customer', 'عميل جديد')}</button>`;

  const k = draftCalc(fs);
  const ready = d.start !== null && d.end !== null && (d.cust || d.newCust);
  const missing = d.start === null ? L('Pick a start time', 'اختر وقت البداية') : d.end === null ? L('Pick an end time', 'اختر وقت النهاية') : '';
  const stepDone = [true, true, d.start !== null && d.end !== null, !!d.cust || d.newCust];
  const stepN = (i, n) => `<span class="stepn ${stepDone[i] ? 'done' : ''}">${stepDone[i] ? ic('check') : n}</span>`;
  const summary = `<aside class="summary card"><div class="card-b" style="display:grid;gap:4px"><h3 class="sec-title" style="margin-bottom:8px">${L('Summary', 'الملخص')}</h3>
    <div class="sum-row"><span>${L('Room', 'الغرفة')}</span><span>${rn(r)}</span></div>
    <div class="sum-row"><span>${L('Date', 'التاريخ')}</span><span>${fmtDate(dayDate(d.date), { weekday: 'short', day: 'numeric', month: 'short' })}</span></div>
    <div class="sum-row"><span>${L('Time', 'الوقت')}</span><span class="num">${d.start !== null ? fmtTime(slotTime(d.start)) : '—'} – ${d.end !== null ? fmtTime(slotTime(d.end)) : '—'}</span></div>
    <div class="sum-row"><span>${L('Duration', 'المدة')}</span><span>${k.hours ? fmtDur(k.hours * 60) : '—'}</span></div>
    <div class="sum-row"><span>${L('Customer', 'العميل')}</span><span>${d.newCust ? L('New customer', 'عميل جديد') : d.cust ? pn(d.cust) : '—'}</span></div>
    <div class="sum-row"><span>${L('Rate', 'السعر')}</span><span class="num">${money(r.rate)} × ${k.hours || 0} ${L('h', 'س')}</span></div>
    <div class="sum-total"><span class="muted">${L('Total', 'الإجمالي')}</span><b>${money(k.price)}</b></div>
    <div class="sm faint" style="margin:4px 0 14px">${L('Paid at check-out. Products added during the booking are billed separately.', 'يُدفع عند الخروج. المنتجات المضافة أثناء الحجز تُحاسب منفصلة.')}</div>
    ${btn(L('Confirm booking', 'تأكيد الحجز'), { kind: 'primary', block: true, act: 'dConfirm', disabled: !ready })}
    ${missing ? `<div class="sm muted" style="text-align:center;margin-top:8px">${missing}</div>` : ''}
    ${btn(L('Cancel', 'إلغاء'), { kind: 'ghost', block: true, act: 'nav', v: 'bookings', cls: '' })}
  </div></aside>`;
  return pageHead(L('New booking', 'حجز جديد'), L('Reserve a room for a customer. Takes about 20 seconds.', 'احجز غرفة لعميل. يستغرق حوالي 20 ثانية.'), btn(L('Walk-in? Start a session instead', 'حضور مباشر؟ ابدأ جلسة'), { kind: 'ghost', act: 'open', v: 'start' }))
    + `<div class="wiz"><div>
      <section class="wsec"><div class="wsec-h">${stepN(0, 1)}<h3>${L('Room', 'الغرفة')}</h3><span class="muted">${L('Shared seats are sold as sessions', 'مقاعد المساحة المشتركة تُباع كجلسات')}</span></div>${rooms}</section>
      <section class="wsec"><div class="wsec-h">${stepN(1, 2)}<h3>${L('Date', 'التاريخ')}</h3></div>${dates}</section>
      <section class="wsec"><div class="wsec-h">${stepN(2, 3)}<h3>${L('Start & end time', 'وقت البداية والنهاية')}</h3><span class="muted num">${d.start !== null && d.end !== null ? `${fmtDur(k.hours * 60)} · ${money(k.price)}` : ''}</span></div>
        ${timeline}<div class="tl-legend"><span><i style="background:var(--ok-soft);box-shadow:inset 0 0 0 1px var(--border)"></i>${L('Free', 'متاح')}</span><span><i style="background:repeating-linear-gradient(45deg,var(--surface-2) 0 3px,var(--border) 3px 4px)"></i>${L('Booked', 'محجوز')}</span><span><i style="background:var(--accent)"></i>${L('Your selection', 'اختيارك')}</span></div>
        <div class="helper">${ic('clock')}<span>${helper}</span></div>${durChips}${slotsHtml}</section>
      <section class="wsec"><div class="wsec-h">${stepN(3, 4)}<h3>${L('Customer', 'العميل')}</h3></div>${custHtml}</section>
    </div>${summary}</div>`;
}

/* ============================================================= OVERLAYS */
const MODALS = {
  addItems(fs, m) {
    const s = fs.sessions.find(x => String(x.id) === String(m.arg)) || fs.sessions[0] || SESSIONS0[0];
    m.qty = m.qty || {};
    const q = (m.q || '').trim().toLowerCase();
    const prods = fs.products.filter(p => p.active && (!q || p.n.join(' ').toLowerCase().includes(q)));
    const n = Object.values(m.qty).reduce((a, x) => a + x, 0);
    const sum = Object.entries(m.qty).reduce((a, [id, x]) => a + pprice(fs, id) * x, 0);
    return { cls: 'wide', html: `<div class="modal-h"><div><h3>${L('Add products', 'إضافة منتجات')}</h3><div class="sub">${L(`To ${pn(s.who)}’s session · ${rn(ROOM[s.room])}`, `إلى جلسة ${pn(s.who)} · ${rn(ROOM[s.room])}`)}</div></div>${closeBtn()}</div>
      <div class="modal-b"><div class="search">${ic('search')}<input class="input" data-inp="m.q" value="${esc(m.q || '')}" placeholder="${L('Search products', 'ابحث عن منتج')}"></div>
      <div>${prods.length ? prods.slice(0, 7).map(p => { const x = m.qty[p.id] || 0; return `<div class="pline"><div class="grow" style="min-width:0"><div class="strong trunc">${L(p.n[0], p.n[1])}</div><div class="sm faint">${L(...PTYPES[p.type])}${p.stock !== null ? ` · ${p.stock} ${L('left', 'متبقي')}` : ''}</div></div><span class="num muted">${money(p.price)}</span>${x ? `<div class="stepper"><button type="button" data-act="qty" data-v="${p.id}:-1" aria-label="-">−</button><span>${x}</span><button type="button" data-act="qty" data-v="${p.id}:1" aria-label="+">+</button></div>` : btn(L('Add', 'إضافة'), { sm: true, act: 'qty', v: p.id + ':1' })}</div>`; }).join('') : emptyState('search', L('No products found', 'لا توجد منتجات'), L('Try another name.', 'جرّب اسمًا آخر.'))}</div></div>
      <div class="modal-f"><span class="sm muted">${n ? L(`${n} items · <b class="num" style="color:var(--text)">${money(sum)}</b>`, `${n} منتج · <b class="num" style="color:var(--text)">${money(sum)}</b>`) : L('Nothing selected yet', 'لم يتم اختيار شيء')}</span><div class="push">${btn(L('Cancel', 'إلغاء'), { kind: 'ghost', act: 'close' })}${btn(L('Add to session', 'إضافة للجلسة'), { kind: 'primary', act: 'addItemsConfirm', disabled: !n })}</div></div>` };
  },
  checkout(fs, m) {
    const s = fs.sessions.find(x => String(x.id) === String(m.arg)) || fs.sessions[0] || SESSIONS0[0];
    const k = sessCalc(fs, s), r = ROOM[s.room];
    const disc = Math.min(Number(m.disc) || 0, k.total);
    const pay = m.pay || 'cash';
    const timeLbl = s.kind === 'shared' ? `${fmtDur(k.billMin)} × ${s.party} ${L('seats', 'مقعد')} × ${money(r.rate)}/${L('h', 'س')}` : `${fmtDur(k.billMin)} × ${money(r.rate)}/${L('h', 'س')}`;
    return { cls: '', html: `<div class="modal-h"><div><h3>${L('Check out', 'إنهاء وتحصيل')}</h3><div class="sub">${pn(s.who)} · ${rn(r)} · ${L('since', 'منذ')} ${fmtTime(s.start)}</div></div>${closeBtn()}</div>
      <div class="modal-b"><dl class="kv"><dt>${L('Room time', 'وقت الغرفة')}<div class="sm faint num">${timeLbl}</div></dt><dd>${money(k.time)}</dd>${s.items.map(i => `<dt>${i.q}× ${pname(fs, i.p)}</dt><dd>${money(pprice(fs, i.p) * i.q)}</dd>`).join('')}${disc ? `<dt>${L('Discount', 'خصم')}</dt><dd style="color:var(--ok)">− ${money(disc)}</dd>` : ''}</dl>
      ${m.discOpen ? `<div class="field"><label>${L('Discount amount', 'قيمة الخصم')}</label><div class="input-affix"><input class="input num" data-inp="m.disc" value="${esc(m.disc || '')}" inputmode="numeric" placeholder="0"><span>${L('EGP', 'ج.م')}</span></div></div>` : `<button type="button" class="link sm" style="justify-self:start" data-act="discOpen">+ ${L('Add discount', 'إضافة خصم')}</button>`}
      <div class="field"><span class="flabel">${L('Payment method', 'طريقة الدفع')}</span>${seg(Object.entries(METHODS).map(([v, l]) => [v, L(...l)]), pay, 'pay')}</div>
      <div class="sum-total"><span class="muted">${L('Total to collect', 'المبلغ المطلوب')}</span><b>${money(k.total - disc)}</b></div></div>
      <div class="modal-f">${btn(L('Keep session open', 'إبقاء الجلسة مفتوحة'), { kind: 'ghost', act: 'close' })}<div class="push">${btn(L(`Collect ${money(k.total - disc)}`, `تحصيل ${money(k.total - disc)}`), { kind: 'primary', act: 'checkoutConfirm' })}</div></div>` };
  },
  quickBook(fs, m) {
    m.room = m.room || 'sb'; m.date = m.date === undefined ? 0 : Number(m.date); m.s = m.s === undefined ? 16 : Number(m.s); m.e = m.e === undefined ? 18 : Number(m.e); m.cust = m.cust || 'laila';
    const busy = busySlots(m.room, m.date);
    let conflict = m.e <= m.s;
    for (let i = m.s; i < m.e; i++) if (busy[i] || pastSlot(m.date, i)) conflict = true;
    const hours = Math.max(0, (m.e - m.s) / 2);
    const opt = (from, to, cur) => Array.from({ length: to - from + 1 }, (_, k) => from + k).map(i => `<option value="${i}" ${i === cur ? 'selected' : ''}>${fmtTime(slotTime(i))}</option>`).join('');
    return { cls: '', html: `<div class="modal-h"><div><h3>${L('Create booking', 'إنشاء حجز')}</h3><div class="sub">${L('Quick booking without leaving this page', 'حجز سريع دون مغادرة الصفحة')}</div></div>${closeBtn()}</div>
      <div class="modal-b"><div class="field"><label>${L('Room', 'الغرفة')}</label><select class="input" data-inp="m.room">${ROOMS.filter(r => r.type !== 'shared' && r.status !== 'maintenance').map(r => `<option value="${r.id}" ${r.id === m.room ? 'selected' : ''}>${rn(r)} · ${money(r.rate)}/${L('h', 'س')}</option>`).join('')}</select></div>
      <div class="field"><label>${L('Date', 'التاريخ')}</label><select class="input" data-inp="m.date">${[0, 1, 2, 3].map(i => `<option value="${i}" ${i === m.date ? 'selected' : ''}>${i === 0 ? L('Today', 'اليوم') + ' · ' : i === 1 ? L('Tomorrow', 'غدًا') + ' · ' : ''}${fmtDate(dayDate(i), { weekday: 'short', day: 'numeric', month: 'short' })}</option>`).join('')}</select></div>
      <div class="grid g-2e" style="gap:12px"><div class="field"><label>${L('From', 'من')}</label><select class="input" data-inp="m.s">${opt(0, SLOTS - 1, m.s)}</select></div><div class="field"><label>${L('To', 'إلى')}</label><select class="input ${conflict ? 'is-error' : ''}" data-inp="m.e">${opt(1, SLOTS, m.e)}</select></div></div>
      ${conflict ? `<div class="err-text">${ic('alert').replace('<svg', '<svg style="width:13px;height:13px;vertical-align:-2px;margin-inline-end:4px"')}${L('This room isn’t free for the whole time. Pick another time or room.', 'الغرفة غير متاحة طوال هذا الوقت. اختر وقتًا أو غرفة أخرى.')}</div>` : `<div class="sm" style="color:var(--ok)">${ic('check').replace('<svg', '<svg style="width:13px;height:13px;vertical-align:-2px;margin-inline-end:4px"')}${L(`Available · ${fmtDur(hours * 60)} · ${money(hours * ROOM[m.room].rate)}`, `متاحة · ${fmtDur(hours * 60)} · ${money(hours * ROOM[m.room].rate)}`)}</div>`}
      <div class="field"><label>${L('Customer', 'العميل')}</label><select class="input" data-inp="m.cust">${PKEYS.map(k => `<option value="${k}" ${k === m.cust ? 'selected' : ''}>${pn(k)} · ${PEOPLE[k][2]}</option>`).join('')}</select></div></div>
      <div class="modal-f">${btn(L('Open full booking page', 'فتح صفحة الحجز الكاملة'), { kind: 'ghost', act: 'nav', v: 'create' })}<div class="push">${btn(L('Create booking', 'إنشاء الحجز'), { kind: 'primary', act: 'quickConfirm', disabled: conflict })}</div></div>` };
  },
  customer(fs, m) {
    const k = m.arg && PEOPLE[m.arg] ? m.arg : 'ahmed';
    const h = hash(k);
    const s = fs.sessions.find(x => x.who === k);
    const visits = 6 + h % 40, spent = 900 + (h % 60) * 145;
    const since = fmtDate(new Date(2024 + (h % 2), h % 12, 1), { month: 'short', year: 'numeric' });
    const hist = [0, 1, 2, 3].map(i => { const r = ROOMS[(h + i * 3) % 7]; const dd = 2 + i * 5 + (h % 4); return `<div class="li" style="padding-inline:0"><div class="grow"><div class="strong">${rn(r)}</div><div class="sm faint">${fmtDate(dayDate(-dd), { day: 'numeric', month: 'short' })} · ${fmtDur((2 + (h + i) % 4) * 60)}</div></div><span class="num">${money(r.rate * (2 + (h + i) % 4))}</span></div>`; }).join('');
    return { kind: 'drawer', html: `<div class="modal-h" style="padding-bottom:4px"><div class="row" style="gap:14px"><div class="avatar lg">${initials(k)}</div><div><h3>${pn(k)}</h3><div class="sub num" dir="ltr" style="text-align:start">${PEOPLE[k][2]}</div></div></div>${closeBtn()}</div>
      <div class="modal-b"><div class="row" style="flex-wrap:wrap">${badge('b-neutral', L(`Customer since ${since}`, `عميل منذ ${since}`), { dot: false })}${badge('b-ok', L('Wi-Fi active', 'الواي فاي مفعل'))}</div>
      <div class="grid g-3" style="gap:0;border:1px solid var(--border);border-radius:var(--r-card)"><div class="stat"><div class="stat-l">${L('Visits', 'الزيارات')}</div><div class="stat-v">${visits}</div></div><div class="stat"><div class="stat-l">${L('Total spent', 'إجمالي الإنفاق')}</div><div class="stat-v">${money(spent)}</div></div><div class="stat"><div class="stat-l">${L('Avg. stay', 'متوسط المدة')}</div><div class="stat-v">${fmtDur(120 + h % 150)}</div></div></div>
      ${s ? `<div class="card card-b" style="display:grid;gap:8px;background:var(--surface-2);border:0"><div class="between"><span class="strong">${L('In a session now', 'في جلسة الآن')}</span>${sessBadge(s)}</div><div class="sm muted">${rn(ROOM[s.room])} · ${L('since', 'منذ')} ${fmtTime(s.start)} · <span data-t="dur" data-s="${s.start}">${fmtDur(nowMin() - s.start)}</span></div><div>${btn(L('Check out', 'إنهاء وتحصيل'), { sm: true, kind: 'primary', act: 'open', v: 'checkout:' + s.id })}</div></div>` : ''}
      <div><h3 class="sec-title" style="margin-bottom:4px">${L('Recent visits', 'الزيارات الأخيرة')}</h3><div class="list">${hist}</div></div>
      <div class="field"><label>${L('Notes', 'ملاحظات')}</label><textarea class="input" placeholder="${L('Prefers the window desk in the shared area', 'يفضل المكتب بجوار الشباك في المساحة المشتركة')}"></textarea></div></div>
      <div class="modal-f">${btn(L('Suspend Wi-Fi', 'إيقاف الواي فاي'), { kind: 'danger-quiet', icon: 'wifi', act: 'open', v: 'del:wifi:' + k })}<div class="push">${btn(L('Edit', 'تعديل'), { act: 'toast', v: L('Customer editor opens here', 'محرر العميل يفتح هنا') })}${btn(L('New booking', 'حجز جديد'), { kind: 'primary', act: 'bookFor', v: k })}</div></div>` };
  },
  confirmed(fs, m) {
    const b = m.b || { room: 'tr2', date: 1, s: 4, e: 10, cust: 'ahmed' };
    const r = ROOM[b.room], hours = (b.e - b.s) / 2;
    return { cls: 'narrow', html: `<div class="modal-b" style="padding-top:24px;justify-items:center;text-align:center"><div class="icon-ok">${ic('check')}</div><h3 style="font-size:calc(var(--fs-h3) + 3px)">${L('Booking confirmed', 'تم تأكيد الحجز')}</h3><p class="muted">${L(`${pn(b.cust)} will get an SMS confirmation.`, `سيصل تأكيد برسالة SMS إلى ${pn(b.cust)}.`)}</p>
      <dl class="kv" style="width:100%;text-align:start;border-top:1px solid var(--border-soft);padding-top:14px;margin-top:4px"><dt>${L('Room', 'الغرفة')}</dt><dd>${rn(r)}</dd><dt>${L('When', 'الموعد')}</dt><dd>${fmtDate(dayDate(b.date), { weekday: 'short', day: 'numeric', month: 'short' })}, ${fmtTime(slotTime(b.s))} – ${fmtTime(slotTime(b.e))}</dd><dt>${L('Total', 'الإجمالي')}</dt><dd>${money(hours * r.rate)}</dd><dt>${L('Reference', 'المرجع')}</dt><dd class="mono">BK-2419</dd></dl></div>
      <div class="modal-f">${btn(L('Print', 'طباعة'), { icon: 'printer', act: 'toast', v: L('Sent to printer', 'تم الإرسال للطابعة') })}<div class="push">${btn(L('Done', 'تم'), { kind: 'primary', act: 'confirmDone' })}</div></div>` };
  },
  del(fs, m) {
    if (String(m.arg).startsWith('wifi:')) {
      const k = m.arg.split(':')[1];
      return { cls: 'narrow', html: `<div class="modal-b" style="padding-top:22px"><div class="icon-danger">${ic('wifi')}</div><h3 style="font-size:calc(var(--fs-h3) + 2px)">${L(`Suspend Wi-Fi for ${pn(k)}?`, `إيقاف الواي فاي لـ ${pn(k)}؟`)}</h3><p class="muted">${L('They will be disconnected from the router immediately. You can turn it back on at any time.', 'سيتم فصله من الراوتر فورًا. يمكنك إعادة التفعيل في أي وقت.')}</p></div><div class="modal-f">${btn(L('Cancel', 'إلغاء'), { act: 'close' })}<div class="push">${btn(L('Suspend access', 'إيقاف الوصول'), { kind: 'danger', act: 'delConfirm' })}</div></div>` };
    }
    const p = fs.products.find(x => x.id === m.arg) || PRODUCTS0.find(x => x.id === 'energy');
    return { cls: 'narrow', html: `<div class="modal-b" style="padding-top:22px"><div class="icon-danger">${ic('trash')}</div><h3 style="font-size:calc(var(--fs-h3) + 2px)">${L(`Delete “${p.n[0]}”?`, `حذف «${p.n[1]}»؟`)}</h3><p class="muted">${L('It will be removed from your catalog and from checkout. Past sales and receipts keep their records. This can’t be undone.', 'سيُحذف من الكتالوج ومن شاشة الدفع. المبيعات والإيصالات السابقة تحتفظ بسجلاتها. لا يمكن التراجع.')}</p>${p.active ? `<div class="banner warn" style="margin-top:4px">${ic('alert')}<div>${L('Just want to stop selling it? ', 'تريد فقط إيقاف بيعه؟ ')}<button type="button" class="link" data-act="hideInstead" data-v="${p.id}">${L('Hide it instead', 'أخفِه بدلًا من ذلك')}</button></div></div>` : ''}</div>
      <div class="modal-f">${btn(L('Cancel', 'إلغاء'), { act: 'close' })}<div class="push">${btn(L('Delete product', 'حذف المنتج'), { kind: 'danger', icon: 'trash', act: 'delConfirm' })}</div></div>` };
  },
  start(fs, m) {
    m.room = m.room || 'sh'; m.party = m.party || 1; m.cust = m.cust || 'karim';
    const opts = ROOMS.filter(r => r.type === 'shared' || r.status === 'available');
    const r = ROOM[m.room];
    return { cls: '', html: `<div class="modal-h"><div><h3>${L('Start session', 'بدء جلسة')}</h3><div class="sub">${L('For walk-in customers — billed by the minute from now', 'للعملاء المباشرين — المحاسبة بالدقيقة من الآن')}</div></div>${closeBtn()}</div>
      <div class="modal-b"><div class="field"><span class="flabel">${L('Where', 'المكان')}</span><div class="room-pick" style="grid-template-columns:repeat(auto-fill,minmax(130px,1fr))">${opts.map(x => `<button type="button" class="rp ${x.id === m.room ? 'on' : ''}" data-act="mSet" data-v="room:${x.id}"><b>${rn(x)}</b><small>${x.type === 'shared' ? L(`${x.cap - 23} seats free`, `${x.cap - 23} مقعد متاح`) : roomState(x).detail}</small></button>`).join('')}</div></div>
      <div class="field"><label>${L('Customer', 'العميل')}</label><select class="input" data-inp="m.cust">${PKEYS.map(k => `<option value="${k}" ${k === m.cust ? 'selected' : ''}>${pn(k)}</option>`).join('')}</select><button type="button" class="link sm" style="justify-self:start" data-act="toast" data-v="${esc(L('Quick-add customer opens here', 'إضافة عميل سريعة تفتح هنا'))}">+ ${L('New customer', 'عميل جديد')}</button></div>
      ${r.type === 'shared' ? `<div class="between"><div><div class="flabel">${L('People', 'عدد الأشخاص')}</div><div class="hint">${money(r.rate)} ${L('per seat per hour', 'للمقعد في الساعة')}</div></div><div class="stepper"><button type="button" data-act="mParty" data-v="-1">−</button><span>${m.party}</span><button type="button" data-act="mParty" data-v="1">+</button></div></div>` : `<div class="helper" style="margin:0">${ic('clock')}<span>${L(`Private use · ${money(r.rate)}/hour. ${roomState(r).detail}.`, `استخدام خاص · ${money(r.rate)}/ساعة. ${roomState(r).detail}.`)}</span></div>`}</div>
      <div class="modal-f">${btn(L('Cancel', 'إلغاء'), { kind: 'ghost', act: 'close' })}<div class="push">${btn(L('Start session', 'بدء الجلسة'), { kind: 'primary', icon: 'play', act: 'startConfirm' })}</div></div>` };
  },
  product(fs, m) {
    const p = m.arg ? fs.products.find(x => x.id === m.arg) : null;
    if (m.init !== true) { Object.assign(m, { init: true, name: p ? p.n[LANG === 'ar' ? 1 : 0] : '', ptype: p ? p.type : 'drink', price: p ? p.price : '', track: p ? p.stock !== null : false, stock: p && p.stock !== null ? p.stock : '', active: p ? p.active : true }); }
    const err = m.err ? `<span class="err-text">${L('Required', 'مطلوب')}</span>` : '';
    return { kind: 'drawer', html: `<div class="modal-h"><div><h3>${p ? L('Edit product', 'تعديل منتج') : L('Add product', 'إضافة منتج')}</h3><div class="sub">${L('Products can be added to any session or booking', 'يمكن إضافة المنتجات لأي جلسة أو حجز')}</div></div>${closeBtn()}</div>
      <div class="modal-b"><div class="field"><label>${L('Name', 'الاسم')}</label><input class="input ${m.err && !m.name ? 'is-error' : ''}" data-inp="m.name" value="${esc(m.name)}" placeholder="${L('e.g. Iced Latte', 'مثال: آيس لاتيه')}">${m.err && !m.name ? err : ''}</div>
      <div class="field"><span class="flabel">${L('Type', 'النوع')}</span>${seg(Object.keys(PTYPES).map(t => [t, L(...PTYPES[t])]), m.ptype, 'mType')}</div>
      <div class="field"><label>${L('Price', 'السعر')}</label><div class="input-affix"><input class="input num ${m.err && !m.price ? 'is-error' : ''}" data-inp="m.price" value="${esc(m.price)}" inputmode="decimal" placeholder="0"><span>${L('EGP', 'ج.م')}</span></div>${m.err && !m.price ? err : `<span class="hint">${L('Shown to staff at checkout', 'يظهر للموظفين عند الدفع')}</span>`}</div>
      <div class="between"><div><div class="flabel">${L('Track stock', 'تتبع المخزون')}</div><div class="hint">${L('Get a warning when it runs low', 'تنبيه عند انخفاض الكمية')}</div></div><button type="button" class="switch ${m.track ? 'on' : ''}" data-act="mToggle" data-v="track" role="switch" aria-checked="${!!m.track}"></button></div>
      ${m.track ? `<div class="field"><label>${L('Quantity in stock', 'الكمية بالمخزون')}</label><input class="input num" data-inp="m.stock" value="${esc(m.stock)}" inputmode="numeric" placeholder="0"></div>` : ''}
      <div class="between"><div><div class="flabel">${L('Active', 'نشط')}</div><div class="hint">${L('Inactive products are hidden from checkout', 'المنتجات غير النشطة لا تظهر عند الدفع')}</div></div><button type="button" class="switch ${m.active ? 'on' : ''}" data-act="mToggle" data-v="active" role="switch" aria-checked="${!!m.active}"></button></div></div>
      <div class="modal-f">${p ? btn(L('Delete', 'حذف'), { kind: 'danger-quiet', icon: 'trash', act: 'open', v: 'del:' + p.id }) : ''}<div class="push">${btn(L('Cancel', 'إلغاء'), { kind: 'ghost', act: 'close' })}${btn(L('Save product', 'حفظ المنتج'), { kind: 'primary', act: 'productSave' })}</div></div>` };
  },
  cmdk(fs, m) {
    const q = (m.q || '').trim().toLowerCase();
    const items = [
      ...NAV.map(n => ({ g: L('Go to', 'انتقل إلى'), ic: n.ic, t: L(n.en, n.ar), a: 'nav', v: n.k, k: 'G ' + n.key })),
      { g: L('Actions', 'إجراءات'), ic: 'plus', t: L('New booking', 'حجز جديد'), a: 'nav', v: 'create', k: 'N' },
      { g: L('Actions', 'إجراءات'), ic: 'play', t: L('Start walk-in session', 'بدء جلسة حضور'), a: 'open', v: 'start', k: 'W' },
      { g: L('Actions', 'إجراءات'), ic: 'box', t: L('Add product to catalog', 'إضافة منتج للكتالوج'), a: 'open', v: 'product', k: '' },
      ...['ahmed', 'mariam', 'omar'].map(k => ({ g: L('Customers', 'العملاء'), ic: 'users', t: pn(k), a: 'open', v: 'customer:' + k, k: '' })),
    ].filter(x => !q || x.t.toLowerCase().includes(q));
    let lastG = '';
    const list = items.map((x, i) => { const g = x.g !== lastG ? `<div class="sm faint" style="padding:8px 10px 4px">${x.g}</div>` : ''; lastG = x.g; return g + `<button type="button" class="cmdk-item ${i === 0 ? 'on' : ''}" data-act="${x.a}" data-v="${x.v}">${ic(x.ic)}<span>${x.t}</span>${x.k ? `<span class="kbd">${x.k}</span>` : ''}</button>`; }).join('');
    return { cls: 'wide', top: true, html: `<input class="cmdk-in" data-inp="m.q" value="${esc(m.q || '')}" placeholder="${L('Type a command or search…', 'اكتب أمرًا أو ابحث…')}" autofocus><div class="cmdk-list">${list || `<div class="sm muted" style="padding:16px">${L('No results', 'لا نتائج')}</div>`}</div><div class="modal-f sm faint"><span><span class="kbd">↵</span> ${L('to select', 'للاختيار')}</span><span><span class="kbd">esc</span> ${L('to close', 'للإغلاق')}</span></div>` };
  },
};
const closeBtn = () => btn('', { kind: 'ghost', iconOnly: true, sm: true, icon: 'x', act: 'close', title: L('Close', 'إغلاق') });
function overlay(fs, m, isStatic) {
  const r = ((HOOKS.modal[fs.cid] || {})[m.type] || MODALS[m.type])(fs, m, isStatic);
  const inner = r.kind === 'drawer' ? `<aside class="drawer" role="dialog" aria-modal="true">${r.html}</aside>` : `<div class="modal ${r.cls || ''}" role="dialog" aria-modal="true">${r.html}</div>`;
  return `<div class="ovl ${r.kind === 'drawer' ? 'drawer-ovl' : ''} ${r.top ? 'top' : ''}" ${isStatic ? '' : 'data-act="bgclose"'}>${inner}</div>`;
}
function scrOverlays(fs, c) {
  const demos = [
    ['addItems', L('Add Product', 'إضافة منتج'), L('Modal · from a session card', 'نافذة · من بطاقة الجلسة'), { type: 'addItems', arg: 1, qty: { cap: 2, crois: 1 } }],
    ['checkout', L('Check Out', 'إنهاء وتحصيل'), L('Modal · payment summary', 'نافذة · ملخص الدفع'), { type: 'checkout', arg: 1, pay: 'card' }],
    ['quickBook', L('Create Booking', 'إنشاء حجز'), L('Modal · with live availability check', 'نافذة · مع فحص التوفر'), { type: 'quickBook' }],
    ['customer', L('Customer details', 'تفاصيل العميل'), L('Drawer · keeps context visible', 'درج جانبي · يحافظ على السياق'), { type: 'customer', arg: 'mariam' }],
    ['confirmed', L('Confirmation', 'تأكيد'), L('Success dialog', 'نافذة نجاح'), { type: 'confirmed' }],
    ['del', L('Delete confirmation', 'تأكيد الحذف'), L('Destructive · separated and explicit', 'إجراء حذف · منفصل وواضح'), { type: 'del', arg: 'mark' }],
  ];
  return pageHead(L('Modals & drawers', 'النوافذ والأدراج'), L('Modals for short, focused tasks. Drawers when the page behind should stay visible.', 'النوافذ للمهام القصيرة. الأدراج عندما يجب أن تبقى الصفحة ظاهرة.'), '')
    + `<div class="stages">${demos.map(([k, t, s, m]) => `<div class="stage-wrap"><div class="between"><div><div class="strong">${t}</div><div class="sm muted">${s}</div></div>${btn(L('Open live', 'فتح مباشر'), { sm: true, act: 'open', v: k === 'addItems' || k === 'checkout' ? k + ':1' : k === 'customer' ? 'customer:mariam' : k === 'del' ? 'del:mark' : k })}</div><div class="stage">${overlay(fs, clone(m), true)}</div></div>`).join('')}</div>`;
}

/* ========================================================= DESIGN SYSTEM */
function dsHTML(c) {
  if (HOOKS.ds[c.id]) return HOOKS.ds[c.id](c);
  const fsx = newState(c.id, 'dashboard');
  const dir = LANG === 'ar' ? 'rtl' : 'ltr';
  const sw = c.swatches.map(([n, h]) => `<div><i style="background:${h}"></i>${n}<code>${h}</code></div>`).join('');
  const status = [['var(--ok)', L('Success', 'نجاح'), 'ok'], ['var(--info)', L('Info / live', 'معلومة'), 'info'], ['var(--warn)', L('Warning', 'تحذير'), 'warn'], ['var(--danger)', L('Danger', 'خطر'), 'danger']].map(([v, n]) => `<div><i style="background:${v}"></i>${n}</div>`).join('');
  const r = ROOMS[0];
  return `<div class="theme ds c-${c.id}" dir="${dir}" lang="${LANG}">
    <div class="ds-grid">
      <div class="ds-sec"><h5>Typography</h5><div class="ds-type">${c.scale.map(([l, spec, st, sample]) => `<div><span style="${st}">${sample}</span><code>${l} · ${spec}</code></div>`).join('')}</div><div class="sm faint" style="margin-top:10px">${c.type}</div></div>
      <div class="ds-sec"><h5>Colour</h5><div class="ds-sw">${sw}</div><h5 style="margin-top:18px">Status (meaning only)</h5><div class="ds-sw">${status}</div></div>
      <div class="ds-sec"><h5>Radius · border · elevation</h5><div class="ds-rad">${c.radii.map(([v, l]) => `<div style="border-radius:${Math.min(+v, 26)}px">${v === '999' ? 'pill' : v + 'px'}<br>${l}</div>`).join('')}</div><p class="sm muted" style="margin:12px 0">${c.borders}</p><div class="ds-row" style="gap:14px"><div class="card" style="width:84px;height:52px;display:grid;place-items:center;font-size:11px;color:var(--text-3)">Card</div><div style="width:84px;height:52px;display:grid;place-items:center;font-size:11px;color:var(--text-3);background:var(--surface);border-radius:var(--r-ctl);box-shadow:var(--shadow-pop)">Popover</div><div style="width:84px;height:52px;display:grid;place-items:center;font-size:11px;color:var(--text-3);background:var(--surface);border-radius:var(--r-modal);box-shadow:var(--shadow-modal)">Modal</div></div></div>
      <div class="ds-sec"><h5>Buttons</h5><div class="ds-row">${btn(L('Check out', 'إنهاء وتحصيل'), { kind: 'primary' })}${btn(L('Add product', 'إضافة منتج'), { icon: 'plus' })}${btn(L('Cancel', 'إلغاء'), { kind: 'ghost' })}</div><div class="ds-row" style="margin-top:8px">${btn(L('Delete', 'حذف'), { kind: 'danger', icon: 'trash' })}${btn(L('Remove', 'إزالة'), { kind: 'danger-quiet' })}${btn(L('Disabled', 'معطل'), { kind: 'primary', disabled: true })}${btn('', { iconOnly: true, icon: 'more', title: 'More' })}</div><div class="ds-row" style="margin-top:8px">${btn(L('Small', 'صغير'), { sm: true, kind: 'primary' })}${btn(L('Small', 'صغير'), { sm: true })}${seg([['day', L('Day', 'يوم')], ['week', L('Week', 'أسبوع')], ['month', L('Month', 'شهر')]], 'week', 'x')}</div></div>
      <div class="ds-sec"><h5>Inputs</h5><div class="stack" style="gap:10px"><div class="field"><label>${L('Customer name', 'اسم العميل')}</label><input class="input" value="${L('Ahmed Mohamed', 'أحمد محمد')}"></div><div class="field"><label>${L('Focused', 'نشط')}</label><input class="input is-focus" placeholder="${L('Search products', 'ابحث عن منتج')}"></div><div class="field"><label>${L('Price', 'السعر')}</label><input class="input is-error" placeholder="0"><span class="err-text">${L('Enter a price greater than 0', 'أدخل سعرًا أكبر من صفر')}</span></div><div class="row"><select class="input"><option>${L('All payment methods', 'كل طرق الدفع')}</option></select><button type="button" class="switch on" aria-label="on"></button></div></div></div>
      <div class="ds-sec"><h5>Status & badges</h5><div class="ds-row">${badge('b-ok', L('Available', 'متاحة'))}${badge('b-info', L('In use', 'مشغولة'))}${badge('b-warn', L('Reserved', 'محجوزة'))}${badge('b-danger', L('Overtime', 'تجاوز الوقت'))}${badge('b-neutral', L('Out of service', 'خارج الخدمة'))}${badge('b-ok', L('Live', 'جارية'), { pulse: true })}</div><div class="ds-row" style="margin-top:12px">${chips([['a', L('All Rooms', 'كل الغرف'), 6], ['b', L('Studio', 'استوديو'), 1], ['c', L('Shared Room', 'مساحة مشتركة'), 3]], 'a', 'x')}</div><div class="ds-row" style="margin-top:12px"><span class="count-pill">6</span><span class="kbd">⌘K</span><span class="avatar sm">AM</span><span class="avatar">MH</span></div></div>
      <div class="ds-sec"><h5>Card</h5>${sessCard(fsx, c, fsx.sessions[1])}</div>
      <div class="ds-sec"><h5>Table</h5>${card('', txTable(TX.slice(0, 4), true), { flush: true })}</div>
      <div class="ds-sec"><h5>Modal</h5><div class="ds-stage" style="min-height:300px">${overlay(fsx, { type: 'del', arg: 'energy' }, true)}</div></div>
      <div class="ds-sec ds-wide"><h5>Empty · loading · error · success</h5><div class="ds-states">
        <div class="card">${emptyState('live', L('No active sessions', 'لا توجد جلسات نشطة'), L('Check-ins appear here with a live timer.', 'تظهر الجلسات هنا مع مؤقت مباشر.'), btn(L('Start session', 'بدء جلسة'), { sm: true, kind: 'primary' }))}</div>
        <div class="card card-b" style="display:grid;gap:12px;align-content:start"><div class="row"><div class="skel" style="width:34px;height:34px;border-radius:50%"></div><div class="grow"><div class="skel" style="width:60%"></div><div class="skel" style="width:40%;margin-top:8px"></div></div></div><div class="skel" style="height:40px"></div><div class="skel" style="width:80%"></div><div class="skel" style="width:55%"></div><span class="sm faint">${L('Loading keeps the layout — no jumping.', 'التحميل يحافظ على التخطيط — بدون قفزات.')}</span></div>
        <div style="display:grid;gap:10px;align-content:start"><div class="banner err">${ic('alert')}<div><b>${L('Couldn’t reach the router', 'تعذر الاتصال بالراوتر')}</b><span class="muted">${L('Sessions still run. Wi-Fi changes will sync when it’s back.', 'الجلسات مستمرة. ستتم مزامنة الواي فاي عند عودته.')}</span><div style="margin-top:8px">${btn(L('Retry', 'إعادة المحاولة'), { sm: true })}</div></div></div><div class="banner warn">${ic('clock')}<div><b>${L('Subscription ends in 5 days', 'الاشتراك ينتهي خلال 5 أيام')}</b><span class="muted">${L('Renew to keep bookings open.', 'جدد للإبقاء على الحجوزات.')}</span></div></div></div>
        <div style="position:relative;min-height:150px" class="card"><div class="card-b sm muted">${L('Success is quiet: a toast with undo, never a blocking dialog for routine actions.', 'النجاح هادئ: إشعار مع تراجع، دون نافذة معطلة للإجراءات الروتينية.')}</div><div class="toast" style="position:absolute;bottom:16px">${ic('check')}<span>${L('Session closed · EGP 1,480 collected', 'تم إنهاء الجلسة · تحصيل 1,480 ج.م')}</span><button type="button">${L('Undo', 'تراجع')}</button></div></div>
      </div></div>
    </div>
    <div class="ds-principles">${c.principles.map(([t, d]) => `<div><b>${t}</b>${d}</div>`).join('')}</div>
  </div>`;
}

/* ================================================================= PAGE */
function frameBlock(c, s, i) {
  const note = (c.notes && c.notes[s.k]) || s.note;
  return `<div class="pg-screen" data-screen="${s.k}"><div class="pg-sub"><h3><span>${String(i + 1).padStart(2, '0')}</span>${s.en}</h3><p>${note}</p></div><div class="pg-frame"><div class="pg-frame-bar"><span><i></i><i></i><i></i></span><span class="url">app.linkspace.io/${s.path}</span><span style="width:44px"></span></div><div class="pg-host" id="host-${c.id}-${s.k}"></div></div></div>`;
}
function buildPage() {
  const featured = CONCEPTS.filter(c => c.featured), rest = CONCEPTS.filter(c => !c.featured);
  const choice = c => `<a class="pg-choice ${c.featured ? 'pg-featured' : ''}" href="#concept-${c.id}"><div class="pg-thumb"><div class="pg-thumb-inner" id="thumb-${c.id}"></div></div><div class="pg-choice-b">${c.featured ? '<span class="pg-flag">Selected direction · refined</span>' : ''}<span class="pg-choice-n">CONCEPT ${c.n}</span><h3>${c.name}</h3><p>${c.short}</p><span class="pg-for">${c.ref}</span><div class="pg-swatches">${c.swatches.slice(0, 6).map(([, h]) => `<span style="background:${h}"></span>`).join('')}</div></div></a>`;
  $('#featured').innerHTML = featured.map(choice).join('');
  $('#choices').innerHTML = rest.map(choice).join('');
  $('#jump').innerHTML = CONCEPTS.map(c => `<a href="#concept-${c.id}" title="${c.name}">${c.featured ? '★' : c.n} ${c.tag}</a>`).join('');
  $('#screenSel').innerHTML = `<option value="all">All screens</option>` + SCREENS.concat(EXTRA_SCREENS).map(s => `<option value="${s.k}">${s.en}</option>`).join('');
  const section = c => `
    <section class="pg-concept ${c.featured ? 'pg-concept-featured' : ''}" id="concept-${c.id}">
      <div class="pg-chead"><div><span class="pg-choice-n">CONCEPT ${c.n}</span><h2>${c.name}</h2><div class="pg-ref">Reference direction: ${c.ref}</div><p style="margin-top:14px">${c.philosophy}</p></div>
        <dl class="pg-meta"><div><dt>Optimised for</dt><dd>${c.optimized}</dd></div><div><dt>Typography</dt><dd>${c.type}</dd></div><div><dt>Feel</dt><dd>${c.feel}</dd></div></dl></div>
      ${HOOKS.section[c.id] ? HOOKS.section[c.id](c) : ''}
      <div class="pg-ds-block"><div class="pg-sub"><h3><span>00</span>Design system</h3><p>Tokens and components exactly as the screens below use them.</p></div><div id="ds-${c.id}"></div></div>
      ${screensFor(c).map((s, i) => frameBlock(c, s, i)).join('')}
      <div class="pg-why"><div><span class="pg-choice-n">CONCEPT ${c.n}</span><h3>Why this direction works</h3></div><ul>${c.why.map(([t, d]) => `<li><b>${t}</b><span>${d}</span></li>`).join('')}<li class="pg-trade"><b>${c.featured ? 'What stays the same' : 'Trade-offs to weigh'}</b><span>${c.trade}</span></li></ul></div>
    </section>`;
  $('#concepts').innerHTML = featured.map(section).join('') + (rest.length ? `<div class="pg-divider" id="originals"><span class="pg-eyebrow">Original explorations</span><h2>The five directions you compared</h2><p>Kept unchanged for reference.</p></div>` : '') + rest.map(section).join('');
  CONCEPTS.forEach(c => {
    screensFor(c).forEach(s => { FS[`${c.id}-${s.k}`] = newState(c.id, s.k); });
    FS[`${c.id}-thumb`] = newState(c.id, 'sessions');
  });
  renderAll();
}
function renderAll() {
  CONCEPTS.forEach(c => {
    $(`#ds-${c.id}`).innerHTML = dsHTML(c);
    screensFor(c).forEach(s => rerender(`${c.id}-${s.k}`));
    $(`#thumb-${c.id}`).innerHTML = frameHTML(`${c.id}-thumb`);
  });
  fitThumbs();
  tick();
}
function rerender(fid) {
  const host = document.getElementById('host-' + fid);
  if (!host) return;
  const content = host.querySelector('.content');
  const scroll = content ? content.scrollTop : 0;
  const calEl = host.querySelector('.cal'), calScroll = calEl ? [calEl.scrollTop, calEl.scrollLeft] : null;
  const ae = document.activeElement;
  let focusKey = null, sel = null;
  if (ae && host.contains(ae) && ae.dataset && ae.dataset.inp) { focusKey = ae.dataset.inp; try { sel = ae.selectionStart; } catch (e) { sel = null; } }
  host.innerHTML = frameHTML(fid);
  const c2 = host.querySelector('.content'); if (c2) c2.scrollTop = scroll;
  const cal2 = host.querySelector('.cal'); if (cal2 && calScroll && FS[fid].screen === 'bookings') { cal2.scrollTop = calScroll[0]; cal2.scrollLeft = calScroll[1]; }
  if (focusKey) { const el = host.querySelector(`[data-inp="${focusKey}"]`); if (el) { el.focus(); if (sel !== null && el.setSelectionRange) try { el.setSelectionRange(sel, sel); } catch (e) { /* select */ } } }
  else if (FS[fid].modal && FS[fid].modal.type === 'cmdk') { const el = host.querySelector('.cmdk-in'); if (el) el.focus(); }
  const url = host.parentElement.querySelector('.url');
  if (url) { const s = screenDef(FS[fid].screen); url.textContent = 'app.linkspace.io/' + (s ? s.path : FS[fid].screen); }
  tickIn(host);
}
function fitThumbs() { $$('.pg-thumb').forEach(t => { const inner = t.firstElementChild; inner.style.transform = `scale(${t.clientWidth / 1280})`; }); }

/* ---------------------------------------------------------- live ticking */
function tickIn(root) {
  const n = nowMin();
  root.querySelectorAll('[data-t]').forEach(el => {
    const t = el.dataset.t, s = +el.dataset.s;
    if (t === 'dur') el.textContent = fmtDur(n - s);
    else if (t === 'clock') el.textContent = fmtClock(n - s);
    else if (t === 'now') el.textContent = fmtTime(n);
    else if (t === 'prog') { const e = +el.dataset.e; el.style.width = Math.max(2, Math.min(100, (n - s) / (e - s) * 100)) + '%'; el.classList.toggle('over', n > e); }
    else if (t === 'left') { const d = Math.round(+el.dataset.e - n); el.textContent = d >= 0 ? L(`${fmtDur(d)} left`, `متبقي ${fmtDur(d)}`) : L(`${fmtDur(-d)} over`, `متأخر ${fmtDur(-d)}`); el.classList.toggle('over', d < 0); el.classList.toggle('soon', d >= 0 && d <= 30); }
    else if (t === 'amt') { const bill = Math.max(n - s, +el.dataset.min); el.textContent = money(+el.dataset.rate * bill / 60 + +el.dataset.x); }
  });
}
function tick() { tickIn(document); }
setInterval(tick, 1000);

/* ---------------------------------------------------------------- toasts */
const toastTimers = {};
function toast(fid, msg, undo) {
  const fs = FS[fid];
  fs.toast = { msg, undo };
  clearTimeout(toastTimers[fid]);
  toastTimers[fid] = setTimeout(() => { fs.toast = null; rerender(fid); }, undo ? 6000 : 3000);
}

/* ---------------------------------------------------------------- events */
function openModal(fs, v) {
  const [type, ...rest] = String(v).split(':');
  fs.menu = null;
  fs.modal = { type, arg: rest.join(':') || null };
}
function onAction(fid, act, v, el, e) {
  const fs = FS[fid];
  const m = fs.modal;
  if (act !== 'menu') fs.menu = null;
  if (HOOKS.action[fs.cid]) { const res = HOOKS.action[fs.cid](fid, act, v, el, e); if (res === 'stop') return; if (res === true) { rerender(fid); return; } }
  switch (act) {
    case 'bgclose': if (e.target !== el) return; fs.modal = null; break;
    case 'close': fs.modal = null; break;
    case 'nav': fs.screen = v; fs.modal = null; if (v === 'create' && !fs.draftTouched) fs.draft = newDraft(); { const c = document.getElementById('host-' + fid).querySelector('.content'); if (c) c.scrollTop = 0; } break;
    case 'collapse': fs.collapsed = !fs.collapsed; break;
    case 'menu': fs.menu = fs.menu === v ? null : v; break;
    case 'open': openModal(fs, v); break;
    case 'toast': fs.modal = null; toast(fid, v); break;
    case 'sessFilter': fs.sessFilter = v; break;
    case 'sessReset': fs.sessFilter = 'all'; fs.sessQ = ''; break;
    case 'sessExp': fs.sessExp[v] = !fs.sessExp[v]; break;
    case 'qty': { const [id, d] = v.split(':'); m.qty[id] = Math.max(0, (m.qty[id] || 0) + Number(d)); if (!m.qty[id]) delete m.qty[id]; break; }
    case 'addItemsConfirm': {
      const s = fs.sessions.find(x => String(x.id) === String(m.arg));
      if (s) Object.entries(m.qty).forEach(([id, q]) => { const it = s.items.find(i => i.p === id); if (it) it.q += q; else s.items.push({ p: id, q }); });
      const n = Object.values(m.qty).reduce((a, x) => a + x, 0);
      fs.modal = null; if (s) fs.sessExp[s.id] = true;
      toast(fid, L(`${n} items added to ${s ? pn(s.who) : ''}’s session`, `تمت إضافة ${n} منتج لجلسة ${s ? pn(s.who) : ''}`));
      break;
    }
    case 'discOpen': m.discOpen = true; break;
    case 'pay': m.pay = v; break;
    case 'checkoutConfirm': {
      const idx = fs.sessions.findIndex(x => String(x.id) === String(m.arg));
      if (idx < 0) { fs.modal = null; break; }
      const s = fs.sessions[idx];
      const total = sessCalc(fs, s).total - Math.min(Number(m.disc) || 0, sessCalc(fs, s).total);
      fs.lastRemoved = { s, idx };
      fs.sessions.splice(idx, 1);
      fs.modal = null;
      toast(fid, L(`Session closed · ${money(total)} collected (${L(...METHODS[m.pay || 'cash'])})`, `تم إنهاء الجلسة · تحصيل ${money(total)} (${L(...METHODS[m.pay || 'cash'])})`), true);
      break;
    }
    case 'undo': if (fs.undoFn) { fs.undoFn(); fs.undoFn = null; } else if (fs.lastRemoved) { fs.sessions.splice(fs.lastRemoved.idx, 0, fs.lastRemoved.s); fs.lastRemoved = null; } fs.toast = null; break;
    case 'calView': fs.calView = v; break;
    case 'calRoom': fs.calRoom = v; break;
    case 'calNav': { const step = fs.calView === 'day' ? 1 : fs.calView === 'week' ? 7 : 30; fs.calOff = Number(v) === 0 ? 0 : fs.calOff + Number(v) * step; break; }
    case 'calDay': fs.calOff = Number(v); fs.calView = 'day'; break;
    case 'calRoomGo': fs.screen = 'bookings'; fs.calRoom = v === 'shared' ? 'all' : v; fs.calView = 'day'; break;
    case 'calSlot': { const [room, i] = v.split(':'); fs.draft = { ...newDraft(), room, date: Math.max(0, fs.calOff), start: Number(i), end: null }; fs.draftTouched = true; fs.screen = 'create'; break; }
    case 'bookRoom': fs.draft = { ...newDraft(), room: v, start: null, end: null }; fs.draftTouched = true; fs.screen = 'create'; break;
    case 'bookFor': fs.draft = { ...newDraft(), cust: v, start: null, end: null }; fs.draftTouched = true; fs.modal = null; fs.screen = 'create'; break;
    case 'roomType': fs.roomType = v; break;
    case 'finPeriod': fs.finPeriod = Number(v); fs.loading = true; setTimeout(() => { fs.loading = false; rerender(fid); }, 450); break;
    case 'finKind': fs.finKind = v; break;
    case 'export': toast(fid, L(`Preparing ${v} for the last ${fs.finPeriod} days…`, `جارٍ تجهيز ${v} لآخر ${fs.finPeriod} يومًا…`)); break;
    case 'prodType': fs.prodType = v; break;
    case 'prodView': fs.prodView = v; break;
    case 'prodClear': fs.prodQ = ''; fs.prodType = 'all'; break;
    case 'prodToggle': { const p = fs.products.find(x => x.id === v); p.active = !p.active; toast(fid, p.active ? L(`${p.n[0]} is visible at checkout`, `${p.n[1]} ظاهر عند الدفع`) : L(`${p.n[0]} hidden from checkout`, `${p.n[1]} مخفي من الدفع`)); break; }
    case 'hideInstead': { const p = fs.products.find(x => x.id === v); if (p) p.active = false; fs.modal = null; toast(fid, L(`${p.n[0]} hidden from checkout`, `${p.n[1]} مخفي من الدفع`)); break; }
    case 'delConfirm': {
      if (String(m.arg).startsWith('wifi:')) { fs.modal = null; toast(fid, L('Wi-Fi access suspended', 'تم إيقاف الواي فاي')); break; }
      const p = fs.products.find(x => x.id === m.arg);
      fs.products = fs.products.filter(x => x.id !== m.arg); fs.modal = null;
      toast(fid, L(`“${p ? p.n[0] : ''}” deleted`, `تم حذف «${p ? p.n[1] : ''}»`));
      break;
    }
    case 'mType': m.ptype = v; break;
    case 'mToggle': m[v] = !m[v]; break;
    case 'productSave': {
      if (!String(m.name).trim() || !String(m.price).trim()) { m.err = true; break; }
      const existing = m.arg ? fs.products.find(x => x.id === m.arg) : null;
      const data = { n: [m.name, m.name], type: m.ptype, price: Number(m.price) || 0, active: !!m.active, stock: m.track ? Number(m.stock) || 0 : null };
      if (existing) Object.assign(existing, data); else fs.products.unshift({ id: 'new' + (fs.nextId++), sku: 'NEW-' + fs.nextId, sold: 0, ...data });
      fs.modal = null; fs.prodQ = ''; fs.prodType = 'all';
      toast(fid, existing ? L('Product updated', 'تم تحديث المنتج') : L(`${m.name} added`, `تمت إضافة ${m.name}`));
      break;
    }
    case 'mSet': { const [k, val] = v.split(':'); m[k] = val; if (k === 'room') m.party = 1; break; }
    case 'mParty': m.party = Math.max(1, Math.min(12, (m.party || 1) + Number(v))); break;
    case 'startConfirm': {
      const id = fs.nextId++;
      const r = ROOM[m.room];
      fs.sessions.push(r.type === 'shared' ? { id, who: m.cust, room: m.room, kind: 'shared', start: Math.floor(nowMin()), party: m.party || 1, items: [] } : { id, who: m.cust, room: m.room, kind: 'exclusive', start: Math.floor(nowMin()), end: Math.floor(nowMin()) + 120, party: 1, items: [] });
      fs.modal = null; fs.screen = 'sessions'; fs.sessFilter = 'all';
      toast(fid, L(`Session started for ${pn(m.cust)} · ${rn(r)}`, `بدأت جلسة ${pn(m.cust)} · ${rn(r)}`));
      break;
    }
    case 'quickConfirm': fs.modal = { type: 'confirmed', b: { room: m.room, date: m.date, s: m.s, e: m.e, cust: m.cust } }; break;
    case 'confirmDone': fs.modal = null; if (fs.screen === 'create') { fs.draft = newDraft(); fs.draftTouched = false; fs.screen = 'bookings'; } break;
    /* booking wizard */
    case 'dRoom': fs.draft.room = v; fs.draft.start = null; fs.draft.end = null; fs.draftTouched = true; break;
    case 'dDate': fs.draft.date = Number(v); fs.draft.start = null; fs.draft.end = null; fs.draftTouched = true; break;
    case 'dSlot': {
      const d = fs.draft, i = Number(v); fs.draftTouched = true;
      if (d.start === null || d.end !== null) { if (i < SLOTS) { d.start = i; d.end = null; } }
      else if (i <= d.start) { d.start = i; }
      else d.end = i;
      break;
    }
    case 'dDur': { const d = fs.draft; d.end = d.start + Number(v); fs.draftTouched = true; break; }
    case 'dCust': fs.draft.cust = v; fs.draft.newCust = false; break;
    case 'dNewCust': fs.draft.newCust = !fs.draft.newCust; if (fs.draft.newCust) fs.draft.cust = null; else fs.draft.cust = fs.draft.cust || 'ahmed'; break;
    case 'dConfirm': { const d = fs.draft; fs.modal = { type: 'confirmed', b: { room: d.room, date: d.date, s: d.start, e: d.end, cust: d.cust || 'ahmed' } }; break; }
    default: return;
  }
  rerender(fid);
}
document.addEventListener('click', e => {
  const el = e.target.closest('[data-act]');
  const host = e.target.closest('.pg-host');
  if (!host) return;
  const fid = host.id.slice(5);
  if (!el || el.closest('.stage') && !el.closest('.stage-wrap > .between')) {
    // click outside any action closes an open menu
    if (FS[fid].menu && !e.target.closest('.menu')) { FS[fid].menu = null; rerender(fid); }
    return;
  }
  if (el.disabled) return;
  onAction(fid, el.dataset.act, el.dataset.v, el, e);
});
document.addEventListener('input', e => {
  const el = e.target;
  if (!el.dataset || !el.dataset.inp) return;
  const host = el.closest('.pg-host'); if (!host) return;
  const fs = FS[host.id.slice(5)];
  const key = el.dataset.inp;
  if (key.startsWith('m.')) {
    const k = key.slice(2);
    fs.modal[k] = ['s', 'e', 'date'].includes(k) ? Number(el.value) : el.value;
    if (k === 'room' && fs.modal.type === 'start') fs.modal.party = 1;
  } else if (key.startsWith('d.')) fs.draft[key.slice(2)] = el.value;
  else if (key.includes('.')) { const [o, k] = key.split('.'); fs[o][k] = el.value; }
  else fs[key] = el.value;
  rerender(host.id.slice(5));
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') Object.keys(FS).forEach(fid => { if (FS[fid].modal || FS[fid].menu) { FS[fid].modal = null; FS[fid].menu = null; rerender(fid); } });
  if (e.key === 'Enter' && e.target.classList && e.target.classList.contains('cmdk-in')) {
    const first = e.target.closest('.modal').querySelector('.cmdk-item');
    if (first) first.click();
  }
});

/* ------------------------------------------------------ playground chrome */
function setSeg(group, val) { $$(`[data-pg="${group}"] button`).forEach(b => b.classList.toggle('on', b.dataset.v === val)); }
document.addEventListener('click', e => {
  const b = e.target.closest('[data-pg] button');
  if (!b) return;
  const group = b.parentElement.dataset.pg, v = b.dataset.v;
  setSeg(group, v);
  if (group === 'vp') { document.body.dataset.vp = v; }
  if (group === 'lang') { LANG = v; renderAll(); }
});
$('#screenSel').addEventListener('change', e => {
  const v = e.target.value;
  document.body.dataset.screen = v;
  $$('.pg-screen').forEach(s => s.classList.toggle('show', s.dataset.screen === v));
});
window.addEventListener('resize', fitThumbs);
const io = new IntersectionObserver(entries => entries.forEach(en => { if (en.isIntersecting) $$('#jump a').forEach(a => a.classList.toggle('on', a.getAttribute('href') === '#' + en.target.id)); }), { rootMargin: '-40% 0px -55% 0px' });

window.PG = {
  get LANG() { return LANG; }, L, esc, clone, hash, nowMin, dayDate, fmtDate, fmtTime, fmtHour, fmtDur, fmtClock, fmtHours, money, ic, I, roomArt,
  TYPES, typeName, ROOMS, ROOM, rn, PEOPLE, PKEYS, pn, initials, PTYPES, PRODUCTS0, SESSIONS0, BOOKINGS_TODAY, bookingsFor, busySlots, pastSlot,
  DAY_START, DAY_END, SLOTS, slotTime, REV, TX, METHODS, ACTIVITY, NAV, NAVMAP, btn, badge, seg, chips, pageHead, card, emptyState,
  sessStatus, sessCalc, sessBadge, roomState, bookingStatus, txStatus, chartHTML, chartLegend, calDay, calWeek, calMonth, scrCreate, txTable, stockCell,
  upNextList, roomsNowList, activityList, kpiData, MODALS, overlay, closeBtn, sidebar, FS, rerender, toast, newDraft, HOOKS, CONCEPTS, CMAP, addConcept, SCREENS, EXTRA_SCREENS,
};
document.addEventListener('DOMContentLoaded', init);
function init() {
/* Deep links: ?concept=calm&screen=sessions&lang=ar&vp=mobile */
const qs = new URLSearchParams(location.search);
if (qs.get('lang') === 'ar') { LANG = 'ar'; setSeg('lang', 'ar'); }
if (['tablet', 'mobile'].includes(qs.get('vp'))) { document.body.dataset.vp = qs.get('vp'); setSeg('vp', qs.get('vp')); }
buildPage();
$$('.pg-concept').forEach(s => io.observe(s));
if (qs.get('screen') && screenDef(qs.get('screen'))) { const sel = $('#screenSel'); sel.value = qs.get('screen'); sel.dispatchEvent(new Event('change')); }
if (CMAP[qs.get('concept')]) {
  $$('.pg-concept').forEach(s => { if (s.id !== 'concept-' + qs.get('concept')) s.style.display = 'none'; });
  $('.pg-hero').style.display = 'none';
  const d = $('#originals'); if (d) d.style.display = 'none';
}
}
})();
