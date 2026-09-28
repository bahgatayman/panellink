/* ==========================================================================
   Link Space Panel — "Premium Business Operations — Refined" (NOT production)
   Plugs into playground.js through window.PG hooks. Same data, same screens,
   refined system: warmer surfaces, friendlier voice, fewer clicks, subtle motion.
   ========================================================================== */
(() => {
'use strict';
const P = window.PG;
const {
  L, esc, ic, I, money, fmtTime, fmtDur, fmtDate, dayDate, nowMin, ROOMS, ROOM, rn, PEOPLE, PKEYS, pn, initials, PTYPES,
  typeName, btn, badge, seg, card, sessStatus, sessCalc, roomState, bookingStatus, txStatus, chartHTML, chartLegend,
  calDay, calWeek, calMonth, bookingsFor, BOOKINGS_TODAY, DAY_START, DAY_END, SLOTS, slotTime, TX, METHODS, REV,
  FS, rerender, toast, HOOKS, MODALS, closeBtn, hash, overlay, roomArt,
} = P;
const ID = 'refined';

/* -------------------------------------------------------------- icons */
Object.assign(I, {
  cash: '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9.5h.01M18 14.5h.01"/>',
  card: '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19M6 15h4"/>',
  phone: '<rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18h2"/>',
  send: '<path d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/>',
  extend: '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 1.5M9 2.5h6"/>',
  move: '<path d="M7 7h12l-3-3M17 17H5l3 3"/>',
  user: '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4.3-6 8-6s7 2 8 6"/>',
  refund: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/>',
  checkCircle: '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16.5 9.5"/>',
  sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2.5 12h2M19.5 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
  qr: '<rect x="3.5" y="3.5" width="6" height="6" rx="1"/><rect x="14.5" y="3.5" width="6" height="6" rx="1"/><rect x="3.5" y="14.5" width="6" height="6" rx="1"/><path d="M14.5 14.5h2v2M20.5 14.5v6h-6M17.5 18.5h.01"/>',
});

/* ------------------------------------------------------ illustrations */
function illo(name) {
  const st = 'fill="none" stroke="var(--illo-ink)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';
  let g = '<ellipse cx="60" cy="72" rx="42" ry="4.5" fill="var(--illo-fill)"/>';
  if (name === 'quiet') g += `<path d="M38 38h36v12a14 14 0 0 1-14 14h-8a14 14 0 0 1-14-14z" fill="#fff" ${st}/><path d="M74 42h3.5a6 6 0 0 1 0 12H73" ${st}/><path class="steam" d="M48 30c-3-4 3-6 0-10M56 28c-3-4 3-6 0-10M64 30c-3-4 3-6 0-10" fill="none" stroke="var(--illo-acc)" stroke-width="1.6" stroke-linecap="round"/>`;
  else if (name === 'calendar') g += `<rect x="34" y="18" width="52" height="46" rx="7" fill="#fff" ${st}/><path d="M34 30h52" ${st}/><rect x="35" y="19" width="50" height="11" rx="6" fill="var(--illo-fill)"/><path d="M46 13v10M74 13v10" ${st}/><path class="draw" d="m50 47 7 7 13-14" fill="none" stroke="var(--illo-acc)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>`;
  else if (name === 'search') g += `<rect x="28" y="18" width="48" height="44" rx="6" fill="#fff" ${st}/><path d="M36 30h26M36 38h18M36 46h22" stroke="var(--illo-fill)" stroke-width="4" stroke-linecap="round"/><circle cx="72" cy="46" r="12" fill="#fff" ${st}/><path d="m81 55 8 8" ${st} stroke-width="2.6"/>`;
  else if (name === 'receipt') g += `<path d="M40 14h40v52l-5-3.5-5 3.5-5-3.5-5 3.5-5-3.5-5 3.5-5-3.5-5 3.5z" fill="#fff" ${st}/><path d="M48 26h24M48 34h16M48 42h20" stroke="var(--illo-fill)" stroke-width="4" stroke-linecap="round"/><path d="M62 52h10" ${st}/>`;
  else if (name === 'box') g += `<path d="M34 32 60 22l26 10v26L60 68 34 58z" fill="#fff" ${st}/><path d="M34 32l26 10 26-10M60 42v26" ${st}/><path d="M47 27l26 10" stroke="var(--illo-acc)" stroke-width="1.6"/>`;
  else if (name === 'people') g += `<circle cx="50" cy="32" r="10" fill="#fff" ${st}/><path d="M32 64c2-10 9-15 18-15s16 5 18 15" fill="#fff" ${st}/><circle cx="76" cy="36" r="8" fill="var(--illo-fill)" stroke="var(--illo-acc)" stroke-width="1.6"/><path d="M68 50c6-2 16 0 20 12" fill="none" stroke="var(--illo-acc)" stroke-width="1.6" stroke-linecap="round"/>`;
  else g += `<circle cx="60" cy="38" r="24" fill="var(--illo-fill)"/><path class="draw" d="m49 38.5 7.5 7.5L72 31" fill="none" stroke="var(--illo-ink)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="92" cy="20" r="2.5" fill="var(--illo-acc)"/><circle cx="28" cy="26" r="2" fill="var(--illo-acc)"/><circle cx="96" cy="50" r="1.8" fill="var(--illo-ink)" opacity=".4"/>`;
  return `<div class="illo"><svg viewBox="0 0 120 80" aria-hidden="true">${g}</svg></div>`;
}
const rEmpty = (il, title, text, actions = '') => `<div class="empty r-empty">${illo(il)}<h4>${title}</h4><p>${text}</p>${actions ? `<div class="actions">${actions}</div>` : ''}</div>`;

/* ------------------------------------------------------------ helpers */
const rchips = (items, cur, act) => `<div class="chips">${items.map(([v, l, n, dot]) => `<button type="button" class="chip ${String(v) === String(cur) ? 'on' : ''}" data-act="${act}" data-v="${v}" aria-pressed="${String(v) === String(cur)}">${String(v) === String(cur) ? ic('check', 'chk') : ''}${dot ? `<span class="cdot" style="background:${dot}"></span>` : ''}${l}${n !== undefined ? `<span class="count">${n}</span>` : ''}</button>`).join('')}</div>`;
const rsearch = (key, val, ph, w = 240) => `<div class="search r-search" style="width:${w}px;max-width:100%">${ic('search')}<input class="input" data-inp="${key}" value="${esc(val)}" placeholder="${ph}" aria-label="${ph}">${val ? `<button type="button" class="clear" data-act="clearQ" data-v="${key}" aria-label="${L('Clear search', 'مسح البحث')}">${ic('x')}</button>` : '<span class="kbd slash">/</span>'}</div>`;
const head = (title, sub, actions = '', eyebrow = '') => `<div class="page-head r-head"><div>${eyebrow ? `<div class="eyebrow">${eyebrow}</div>` : ''}<h1>${title}</h1>${sub ? `<div class="sub">${sub}</div>` : ''}</div>${actions ? `<div class="actions">${actions}</div>` : ''}</div>`;
const go = label => `${label}${ic('arrowR', 'arrow flip')}`;
function spark(vals, color = 'var(--data1)') {
  const w = 96, h = 28, mx = Math.max(...vals), mn = Math.min(...vals);
  const pts = vals.map((v, i) => [i / (vals.length - 1) * w, h - 3 - (v - mn) / (mx - mn || 1) * (h - 6)]);
  const d = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join('');
  const last = pts[pts.length - 1];
  return `<svg class="spark" viewBox="0 0 ${w} ${h}" aria-hidden="true"><path d="${d}" fill="none" stroke="${color}" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/><circle cx="${last[0].toFixed(1)}" cy="${last[1].toFixed(1)}" r="2.6" fill="${color}"/></svg>`;
}
function custInfo(fs, k) {
  const h = hash(k);
  const s = fs.sessions.find(x => x.who === k);
  return { visits: 4 + h % 38, spent: 600 + (h % 70) * 130, last: s ? 0 : 1 + h % 12, isNew: ['hana', 'mahmoud'].includes(k), suspended: k === 'rana', session: s };
}
const QUICK = ['cap', 'water', 'tea'];
const pShort = (fs, id) => { const p = fs.products.find(x => x.id === id); return p ? L(p.n[0], p.n[1]) : id; };
const pPrice = (fs, id) => { const p = fs.products.find(x => x.id === id) || P.PRODUCTS0.find(x => x.id === id); return p ? p.price : 0; };
const findSess = (fs, id) => fs.sessions.find(x => String(x.id) === String(id));
const moreMenu = (fs, id, items) => `<div class="rel">${btn('', { iconOnly: true, icon: 'more', act: 'menu', v: id, title: L('More actions', 'إجراءات أخرى'), sm: true, kind: 'ghost' })}${fs.menu === id ? `<div class="menu">${items}</div>` : ''}</div>`;
const mi = (icon, label, act, v, desc = '', danger = false) => `<button type="button" class="${danger ? 'danger' : ''}" data-act="${act}" data-v="${esc(v)}">${ic(icon)}<span class="mi"><span>${label}</span>${desc ? `<small>${desc}</small>` : ''}</span></button>`;

/* ------------------------------------------------------------ concept */
P.addConcept({
  id: ID, tag: 'Refined', n: '03 · REFINED', featured: true, refined: true,
  name: 'Premium Business Operations — Refined',
  ref: 'Stripe · Ramp · Mercury clarity, with modern-workspace warmth',
  short: 'Stripe-level clarity with a friendlier voice, faster everyday actions and quiet, satisfying feedback.',
  philosophy: 'The same trustworthy foundation — navy brand, tabular figures, precise tables — made warmer and easier to live with. Content sits on one calm sheet instead of many boxes, the panel speaks like a helpful colleague, the next action is always one click away, and every action answers back with a small, fast confirmation.',
  optimized: 'Front-desk staff running the space all day, and owners who need to trust the money. Built for scanning in seconds and acting in one or two clicks.',
  type: 'Inter with tabular figures · IBM Plex Sans Arabic · IBM Plex Mono for receipts and references',
  feel: 'Trustworthy · calm · quietly friendly',
  shell: 'sidebar', bigAmount: true, prodView: 'list',
  screens: ['dashboard', 'sessions', 'bookings', 'rooms', 'financials', 'products', 'customers', 'create', 'checkout', 'overlays'],
  navGroups: [[['Today', 'اليوم'], ['dashboard', 'sessions', 'bookings']], [['Manage', 'الإدارة'], ['rooms', 'customers', 'products']], [['Money', 'المال'], ['financials']]],
  notes: {
    dashboard: 'Greets you, then answers “what needs me?” first — every attention item carries its own one-click fix. Try resolving them all.',
    sessions: 'Quick-add chips add a product in one click (with Undo). Progress bars show booked time; “Check out” runs a short loading state and the card leaves gracefully.',
    bookings: '“Free right now” and pending requests sit above the calendar. Click any booking for its drawer; click an empty hour to book it.',
    rooms: 'Filter by live status. Each card says who is inside, shows today’s timeline and offers the one action that fits its state.',
    financials: 'A friendly one-line summary, sparklines, payment split, cash drawer, and clickable rows that open the receipt.',
    products: 'Low-stock nudges, search with clear and “/” hint, on-sale switches with instant feedback, hover row actions.',
    customers: 'New screen. Segments like “Here now” and “Regulars”, hover actions, and a profile drawer on click.',
    create: 'Suggested free windows appear above the time grid; the summary explains exactly what happens next.',
    checkout: 'The full checkout: editable receipt, payment tiles, cash change calculator, loading state and a real success moment.',
    overlays: 'Every modal and drawer in the refined style — each says what will happen before you commit.',
  },
  swatches: [['Navy · primary', '#163c85'], ['Blue · accent', '#2f5bb7'], ['Canvas', '#f5f6f4'], ['Sheet', '#ffffff'], ['Brand tint', '#eef3fb'], ['Border', '#e5e7e3'], ['Ink', '#101a2c'], ['Text 2', '#4d5766']],
  why: [
    ['Trust is kept, coldness is removed', 'Navy, tabular figures and precise tables stay. Warm-neutral surfaces, softer radii and a human voice remove the “enterprise” chill.'],
    ['The next action is always visible', 'Attention items, contextual room actions, quick-add chips and inline confirms mean most daily tasks take one or two clicks.'],
    ['Organised without being boxed in', 'One content sheet, dividers inside panels, summary strips instead of card grids — hierarchy comes from type and spacing.'],
    ['Every action answers back', 'Pressed states, loading buttons, graceful exits, drawn check marks and toasts with Undo make the system feel responsive and safe.'],
  ],
  trade: 'Brand navy #163c85 as the primary action, Inter with tabular figures, right-aligned money, 1px borders, restrained shadows, no gradients or glass. It is still recognisably the Operations direction you chose.',
});

HOOKS.state[ID] = st => Object.assign(st, {
  sessAttn: false, sessSort: 'attention', custQ: '', custSeg: 'all', coId: '1', co: { pay: 'cash', received: '', disc: '', discOpen: false, busy: false, done: null },
  leaving: null, flash: null, roomStatus: 'all', prodLow: false, attnDone: {}, busy: null,
});

/* ------------------------------------------------------------- shell */
HOOKS.topbar[ID] = (fs) => {
  const title = { dashboard: L('Home', 'الرئيسية'), sessions: L('Active sessions', 'الجلسات النشطة'), bookings: L('Bookings', 'الحجوزات'), rooms: L('Rooms', 'الغرف'), financials: L('Financials', 'المالية'), products: L('Products', 'المنتجات'), customers: L('Customers', 'العملاء'), create: L('New booking', 'حجز جديد'), checkout: L('Checkout', 'الدفع'), overlays: L('Modals & drawers', 'النوافذ') }[fs.screen] || '';
  return `<header class="topbar">
    ${btn('', { kind: 'ghost', iconOnly: true, icon: 'side', act: 'collapse', title: L('Collapse sidebar', 'طي القائمة'), cls: 'hide-t' })}
    <div class="show-m"><div class="logo" style="width:26px;height:26px">LS</div></div><div class="show-m strong" style="font-size:15px">${title}</div>
    <button type="button" class="cmd hide-m" data-act="open" data-v="cmdk">${ic('search')}<span class="grow">${L('Search customers, bookings, receipts…', 'ابحث عن عملاء، حجوزات، إيصالات…')}</span><span class="kbd">⌘K</span></button>
    <div class="grow"></div>
    <span class="live-pill hide-m"><span class="dot pulse"></span><span data-t="now"></span> · ${L('Nasr City', 'مدينة نصر')}</span>
    <button type="button" class="btn btn-ghost btn-icon iconbtn" aria-label="${L('Notifications', 'الإشعارات')}" data-act="toast" data-v="${esc(L('You’re up to date — 3 new notifications', 'لديك 3 إشعارات جديدة'))}">${ic('bell')}<span class="ndot"></span></button>
  </header>`;
};

/* ============================================================ DASHBOARD */
function attnItems(fs) {
  const out = [];
  const over = fs.sessions.find(s => sessStatus(s) === 'over');
  if (over && !fs.attnDone.over) out.push({ k: 'over', tone: 'danger', title: L(`${pn(over.who)} is ${Math.round(nowMin() - over.end)} min over in ${rn(ROOM[over.room])}`, `${pn(over.who)} متأخر ${Math.round(nowMin() - over.end)} دقيقة في ${rn(ROOM[over.room])}`), sub: L(`${pn('mahmoud')} is booked there at ${fmtTime(810)}.`, `${pn('mahmoud')} لديه حجز هناك الساعة ${fmtTime(810)}.`), acts: [btn(L(`Check out ${PEOPLE[over.who][P.LANG === 'ar' ? 1 : 0].split(' ')[0]}`, `إنهاء جلسة ${PEOPLE[over.who][1].split(' ')[0]}`), { sm: true, kind: 'primary', act: 'open', v: 'checkout:' + over.id }), btn(L('Move Mahmoud to Office 02', 'نقل محمود لمكتب 02'), { sm: true, act: 'attn', v: 'over:move' })] });
  if (!fs.attnDone.pending) out.push({ k: 'pending', tone: 'warn', title: L(`${pn('mostafa')} wants Training Room 02 at 4 PM`, `${pn('mostafa')} يطلب قاعة التدريب 02 الساعة 4 م`), sub: L('4 hours · EGP 1,120 · requested at 11:48 AM', '4 ساعات · 1,120 ج.م · طُلب الساعة 11:48 ص'), acts: [btn(L('Confirm', 'تأكيد'), { sm: true, kind: 'primary', act: 'attn', v: 'pending:confirm' }), btn(L('Decline', 'رفض'), { sm: true, kind: 'ghost', act: 'attn', v: 'pending:decline' })] });
  const ending = fs.sessions.find(s => sessStatus(s) === 'ending');
  if (ending) out.push({ k: 'ending', tone: 'warn', title: L(`${pn(ending.who)}’s time in ${rn(ROOM[ending.room])} ends at ${fmtTime(ending.end)}`, `وقت ${pn(ending.who)} في ${rn(ROOM[ending.room])} ينتهي ${fmtTime(ending.end)}`), sub: L('The room is free until 5 PM — a good moment to offer more time.', 'الغرفة متاحة حتى 5 م — وقت مناسب لعرض التمديد.'), acts: [btn(L('Extend 30 min', 'تمديد 30 دقيقة'), { sm: true, act: 'extend', v: ending.id, icon: 'extend' })] });
  const low = fs.products.find(p => p.active && p.stock !== null && p.stock <= 5);
  if (low && !fs.attnDone.stock) out.push({ k: 'stock', tone: 'neutral', title: L(`${low.n[0]} is running low`, `${low.n[1]} على وشك النفاد`), sub: L(`${low.stock} left · usually ${Math.max(6, Math.round(low.sold / 4))} sold a week`, `متبقي ${low.stock} · يُباع عادة ${Math.max(6, Math.round(low.sold / 4))} أسبوعيًا`), acts: [btn(L('Mark restocked', 'تم إعادة التخزين'), { sm: true, act: 'attn', v: 'stock:restock:' + low.id })] });
  if (!fs.attnDone.deposit) out.push({ k: 'deposit', tone: 'info', title: L(`${pn('laila')}’s deposit is still pending`, `عربون ${pn('laila')} ما زال معلقًا`), sub: L('EGP 400 by InstaPay · for today at 5 PM in Studio A', '400 ج.م عبر إنستاباي · لحجز اليوم 5 م في استوديو A'), acts: [btn(L('Mark as paid', 'تحديد كمدفوع'), { sm: true, act: 'attn', v: 'deposit:paid' })] });
  return out;
}
function attnCard(fs) {
  const items = attnItems(fs);
  const body = items.length
    ? `<div class="attn">${items.map(a => `<div class="attn-i t-${a.tone}"><span class="attn-dot"></span><div class="grow" style="min-width:0"><div class="strong">${a.title}</div><div class="sm muted">${a.sub}</div></div><div class="attn-acts">${a.acts.join('')}</div></div>`).join('')}</div>`
    : rEmpty('done', L('You’re all caught up', 'لا شيء يحتاجك الآن'), L('Nothing needs you right now. We’ll surface anything urgent here the moment it happens.', 'سنعرض هنا أي أمر عاجل فور حدوثه.'));
  return card(`${L('Needs your attention', 'يحتاج انتباهك')} ${items.length ? `<span class="count-pill attn-count">${items.length}</span>` : ''}`, body, { flush: true, cls: 'attn-card' });
}
function kpiRow(fs, items) {
  return `<section class="rk-row">${items.map(k => `<div class="rk"><div class="between" style="align-items:flex-start"><div class="kpi-l">${k.l}</div>${k.viz || ''}</div><div class="kpi-v">${k.v}</div><div class="kpi-d">${k.d}</div></div>`).join('')}</section>`;
}
function avatars(fs) {
  const s = fs.sessions.slice(0, 3);
  return `<span class="av-stack">${s.map(x => `<span class="avatar sm" title="${esc(pn(x.who))}">${initials(x.who)}</span>`).join('')}${fs.sessions.length > 3 ? `<span class="avatar sm more">+${fs.sessions.length - 3}</span>` : ''}</span>`;
}
function rDash(fs) {
  const hr = Math.floor(nowMin() / 60);
  const greet = hr < 12 ? L('Good morning', 'صباح الخير') : hr < 17 ? L('Good afternoon', 'مساء الخير') : L('Good evening', 'مساء الخير');
  const n = attnItems(fs).length;
  const running = fs.sessions.reduce((a, s) => a + sessCalc(fs, s).total, 0);
  const tot = REV.b.map((b, i) => b + REV.p[i]).slice(-14);
  const kpis = kpiRow(fs, [
    { l: L('Revenue today', 'إيرادات اليوم'), v: money(8420), d: `<span class="up">↑ ${money(910)}</span> ${L('vs last Sunday', 'عن الأحد الماضي')}`, viz: spark(tot) },
    { l: L('Bookings today', 'حجوزات اليوم'), v: '14', d: L('9 done · 4 to come · 1 waiting for you', '9 تمت · 4 قادمة · 1 بانتظارك'), viz: spark([9, 11, 8, 13, 12, 7, 9, 12, 13, 11, 14, 15, 10, 14], 'var(--data2)') },
    { l: L('Here right now', 'موجودون الآن'), v: String(fs.sessions.length), d: L(`Running bill ${money(running)}`, `الفاتورة الجارية ${money(running)}`), viz: avatars(fs) },
    { l: L('Occupancy', 'الإشغال'), v: '68%', d: L('Usually peaks around 3 PM', 'الذروة عادةً حوالي 3 م'), viz: `<span class="ring" style="--p:68"></span>` },
  ]);
  const rooms = `<div class="list">${ROOMS.map(r => { const st = roomState(r, fs); return `<button type="button" class="li li-btn" data-act="nav" data-v="rooms"><span class="sdot" style="background:${st.sc}"></span><span class="grow" style="min-width:0"><span class="strong trunc" style="display:block">${rn(r)}</span><span class="sm muted trunc" style="display:block">${st.detail}</span></span><span class="sm" style="color:${st.sc};font-weight:500">${st.label}</span></button>`; }).join('')}</div>`;
  const now = nowMin();
  const next = BOOKINGS_TODAY.filter(b => b.s > now).sort((a, b) => a.s - b.s).slice(0, 4);
  const upnext = `<div class="list">${next.map(b => `<div class="li"><span class="num strong" style="width:70px">${fmtTime(b.s)}</span><span class="grow" style="min-width:0"><span class="strong trunc" style="display:block">${pn(b.who)}</span><span class="sm muted">${rn(ROOM[b.room])} · ${fmtDur(b.e - b.s)}</span></span>${b.st === 'pending' && !fs.attnDone.pending ? bookingStatus('pending') : `<span class="sm faint">${L(`in ${fmtDur(b.s - now)}`, `بعد ${fmtDur(b.s - now)}`)}</span>`}</div>`).join('')}</div>`;
  return head(`${greet}, ${L('Nada', 'ندى')}`, n ? L(`Here’s how today is going. ${n === 1 ? 'One thing needs' : n + ' things need'} you.`, `إليك سير اليوم. ${n} أمور تحتاجك.`) : L('Here’s how today is going. Everything is under control.', 'إليك سير اليوم. كل شيء تحت السيطرة.'),
    btn(L('Start walk-in', 'بدء جلسة حضور'), { icon: 'play', act: 'open', v: 'start' }) + btn(L('New booking', 'حجز جديد'), { kind: 'primary', icon: 'plus', act: 'nav', v: 'create' }),
    `${fmtDate(dayDate(0), { weekday: 'long', day: 'numeric', month: 'long' })} · ${L('Nasr City branch', 'فرع مدينة نصر')}`)
    + kpis
    + `<div class="grid g-2" style="margin-top:var(--gap)"><div class="stack">${attnCard(fs)}${card(L('Revenue · last 14 days', 'الإيرادات · آخر 14 يومًا'), chartHTML(14) + `<div style="margin-top:12px">${chartLegend()}</div>`, { right: btn(go(L('Financials', 'المالية')), { sm: true, kind: 'ghost', act: 'nav', v: 'financials' }) })}</div>
      <div class="stack">${card(L('Rooms right now', 'الغرف الآن'), rooms, { flush: true, right: `<span class="sm faint">${L('Live', 'مباشر')}</span>` })}${card(L('Up next', 'التالي'), upnext, { flush: true, right: btn(go(L('Calendar', 'التقويم')), { sm: true, kind: 'ghost', act: 'nav', v: 'bookings' }) })}${card(L('Recent activity', 'آخر النشاط'), P.activityList(4), { flush: true })}</div></div>`;
}

/* ======================================================= ACTIVE SESSIONS */
function rsCard(fs, s) {
  const r = ROOM[s.room], st = sessStatus(s), k = sessCalc(fs, s), ex = s.kind === 'exclusive';
  const amt = `data-t="amt" data-s="${s.start}" data-rate="${k.rate}" data-min="${ex ? s.end - s.start : 0}" data-x="${k.items}"`;
  const nItems = s.items.reduce((a, i) => a + i.q, 0);
  const exp = fs.sessExp[s.id];
  const items = s.items.length
    ? `<button type="button" class="toggle" data-act="sessExp" data-v="${s.id}" aria-expanded="${!!exp}"><span>${nItems} ${L(nItems === 1 ? 'product' : 'products', 'منتج')} · <b class="num" style="color:var(--text)">${money(k.items)}</b></span>${ic(exp ? 'chevD' : 'chevR', 'flip')}</button>${exp ? `<ul>${s.items.map(i => `<li class="${fs.flash === s.id + ':' + i.p ? 'flash' : ''}"><span>${i.q}× ${pShort(fs, i.p)}</span><span class="num">${money(pPrice(fs, i.p) * i.q)}</span></li>`).join('')}</ul>` : ''}`
    : `<span class="faint">${L('No products yet — add one below', 'لا توجد منتجات بعد — أضف من الأسفل')}</span>`;
  const menuId = 'rs-' + s.id;
  const menu = (ex ? mi('extend', L('Extend 30 min', 'تمديد 30 دقيقة'), 'extend', s.id) : '') + mi('move', L('Move to another room', 'نقل لغرفة أخرى'), 'toast', L('Room change opens here', 'تغيير الغرفة يفتح هنا')) + mi('user', L('Customer details', 'تفاصيل العميل'), 'open', 'customer:' + s.who) + mi('receipt', L('Full checkout', 'الدفع الكامل'), 'toCheckout', s.id) + '<hr>' + mi('x', L('End without charge…', 'إنهاء بدون تحصيل…'), 'open', 'del:endfree:' + s.id, '', true);
  const pct = ex ? Math.max(2, Math.min(100, (nowMin() - s.start) / (s.end - s.start) * 100)) : 0;
  let warn = '';
  if (st === 'over') { const nb = BOOKINGS_TODAY.find(b => b.room === s.room && b.s >= s.end); warn = `<div class="rs-warn">${ic('alert')}<span>${nb ? L(`${pn(nb.who)} is booked here at ${fmtTime(nb.s)}`, `${pn(nb.who)} لديه حجز هنا ${fmtTime(nb.s)}`) : L('Past the booked time', 'تجاوز الوقت المحجوز')}</span></div>`; }
  return `<article class="card rs ${st} ${String(fs.leaving) === String(s.id) ? 'leaving' : ''}">
    <div class="rs-h"><div class="avatar">${initials(s.who)}</div><div class="grow" style="min-width:0"><button type="button" class="scard-name trunc" data-act="open" data-v="customer:${s.who}">${pn(s.who)}</button><div class="sm muted trunc">${rn(r)} · ${ex ? L('Private', 'خاص') : L(`Shared · ${s.party} ${s.party > 1 ? 'people' : 'person'}`, `مشترك · ${s.party} ${s.party > 1 ? 'أشخاص' : 'شخص'}`)}</div></div>${P.sessBadge(s)}</div>
    <div class="rs-money"><div><div class="stat-l">${L('Time', 'الوقت')}</div><div class="rs-big" data-t="dur" data-s="${s.start}">${fmtDur(k.el)}</div></div><div style="text-align:end"><div class="stat-l">${L('Bill so far', 'الفاتورة حتى الآن')}</div><div class="rs-big" ${amt}>${money(k.total)}</div></div></div>
    ${ex ? `<div class="rs-prog"><i data-t="prog" data-s="${s.start}" data-e="${s.end}" class="${st === 'over' ? 'over' : ''}" style="width:${pct}%"></i></div><div class="rs-meta"><span class="num">${fmtTime(s.start)} – ${fmtTime(s.end)}</span><span class="rs-left" data-t="left" data-e="${s.end}"></span></div>` : `<div class="rs-meta"><span>${L('Since', 'منذ')} <span class="num">${fmtTime(s.start)}</span> · ${money(r.rate)}/${L('seat·h', 'مقعد·س')}</span><span class="faint">${L('per minute', 'بالدقيقة')}</span></div>`}
    ${warn}
    <div class="rs-items">${items}</div>
    <div class="rs-quick"><span class="sm faint">${L('Quick add', 'إضافة سريعة')}</span>${QUICK.map(p => `<button type="button" class="qa" data-act="qa" data-v="${s.id}:${p}">+ ${pShort(fs, p)}</button>`).join('')}<button type="button" class="qa more" data-act="open" data-v="addItems:${s.id}">${L('More…', 'المزيد…')}</button></div>
    <div class="rs-f">${ex && st !== 'live' ? btn(L('+30 min', '+30 د'), { act: 'extend', v: s.id, icon: 'extend' }) : ''}${moreMenu(fs, menuId, menu)}${btn(L('Check out', 'إنهاء وتحصيل'), { kind: 'primary', act: 'open', v: 'checkout:' + s.id, cls: 'grow' })}</div>
  </article>`;
}
function rSessions(fs) {
  const list = fs.sessions;
  const types = ['training', 'studio', 'office', 'shared'];
  const tl = { training: L('Training Room', 'قاعة تدريب'), studio: L('Studio', 'استوديو'), office: L('Office', 'مكتب'), shared: L('Shared Room', 'مساحة مشتركة') };
  const attn = s => sessStatus(s) !== 'live';
  let shown = list.filter(s => (fs.sessFilter === 'all' || ROOM[s.room].type === fs.sessFilter) && (!fs.sessAttn || attn(s)));
  const q = fs.sessQ.trim().toLowerCase();
  if (q) shown = shown.filter(s => (PEOPLE[s.who].join(' ') + ' ' + ROOM[s.room].name.join(' ')).toLowerCase().includes(q));
  const rank = s => ({ over: 0, ending: 1, live: 2 })[sessStatus(s)];
  if (fs.sessSort === 'longest') shown.sort((a, b) => a.start - b.start);
  else if (fs.sessSort === 'amount') shown.sort((a, b) => sessCalc(fs, b).total - sessCalc(fs, a).total);
  else shown.sort((a, b) => rank(a) - rank(b) || a.start - b.start);
  const running = list.reduce((a, s) => a + sessCalc(fs, s).total, 0);
  const prods = list.reduce((a, s) => a + sessCalc(fs, s).items, 0);
  const nAttn = list.filter(attn).length;
  const filtered = fs.sessFilter !== 'all' || fs.sessAttn || q;
  const strip = `<div class="strip"><div><span class="l">${L('Here now', 'موجودون الآن')}</span><b>${list.length}</b></div><div><span class="l">${L('Running bill', 'الفاتورة الجارية')}</span><b class="num">${money(running)}</b></div><div class="hide-m"><span class="l">${L('Products in bills', 'منتجات في الفواتير')}</span><b class="num">${money(prods)}</b></div><button type="button" class="${fs.sessAttn ? 'on' : ''}" data-act="sessAttn" aria-pressed="${fs.sessAttn}"><span class="l">${L('Needs attention', 'تحتاج انتباه')}</span><b style="color:${nAttn ? 'var(--danger)' : 'inherit'}">${nAttn}</b><span class="sm link">${fs.sessAttn ? L('Show all', 'عرض الكل') : L('Show only these', 'عرض هذه فقط')}</span></button></div>`;
  const toolbar = `<div class="toolbar">${rchips([['all', L('All Rooms', 'كل الغرف'), list.length], ...types.map(t => [t, tl[t], list.filter(s => ROOM[s.room].type === t).length])], fs.sessFilter, 'sessFilter')}<div class="grow"></div><select class="input hide-m" style="width:172px" data-inp="sessSort" aria-label="${L('Sort', 'ترتيب')}"><option value="attention" ${fs.sessSort === 'attention' ? 'selected' : ''}>${L('Needs attention first', 'الأهم أولًا')}</option><option value="longest" ${fs.sessSort === 'longest' ? 'selected' : ''}>${L('Longest running', 'الأطول مدة')}</option><option value="amount" ${fs.sessSort === 'amount' ? 'selected' : ''}>${L('Highest bill', 'الأعلى فاتورة')}</option></select>${rsearch('sessQ', fs.sessQ, L('Find customer or room', 'ابحث عن عميل أو غرفة'), 190)}</div>`;
  const info = filtered ? `<div class="filter-info">${L(`Showing ${shown.length} of ${list.length} sessions`, `عرض ${shown.length} من ${list.length} جلسات`)} · <button type="button" class="link" data-act="sessReset">${L('Clear filters', 'مسح الفلاتر')}</button></div>` : '';
  let body;
  if (!list.length) body = card('', rEmpty('quiet', L('All quiet for now', 'هدوء تام الآن'), L('When someone checks in, they’ll appear here with a live timer and a running bill.', 'عند وصول أي عميل سيظهر هنا مع مؤقت وفاتورة مباشرة.'), btn(L('Start a session', 'بدء جلسة'), { kind: 'primary', icon: 'play', act: 'open', v: 'start' })));
  else if (!shown.length) body = card('', rEmpty('search', L('No sessions match', 'لا توجد جلسات مطابقة'), q ? L(`Nobody called “${esc(fs.sessQ)}” is checked in right now.`, `لا يوجد «${esc(fs.sessQ)}» حاليًا.`) : L('Nothing here right now — which is good news.', 'لا شيء هنا الآن — وهذا خبر جيد.'), btn(L('Clear filters', 'مسح الفلاتر'), { act: 'sessReset' })));
  else body = `<div class="grid g-sessions">${shown.map(s => rsCard(fs, s)).join('')}</div>`;
  return head(`${L('Active sessions', 'الجلسات النشطة')} <span class="count-pill head-count">${list.length}</span>`, L('Everyone in the space right now. Timers and bills update live.', 'كل من في المكان الآن. المؤقتات والفواتير تتحدث مباشرة.'), btn(L('Start session', 'بدء جلسة'), { kind: 'primary', icon: 'play', act: 'open', v: 'start' }))
    + strip + toolbar + info + body;
}

/* ============================================================== BOOKINGS */
function rBookings(fs) {
  const d = dayDate(fs.calOff);
  const rel = fs.calOff === 0 ? L('Today', 'اليوم') : fs.calOff === 1 ? L('Tomorrow', 'غدًا') : fs.calOff === -1 ? L('Yesterday', 'أمس') : '';
  const label = fs.calView === 'day' ? `${rel ? rel + ' · ' : ''}${fmtDate(d, { weekday: 'short', day: 'numeric', month: 'short' })}` : fs.calView === 'week' ? `${fmtDate(dayDate(fs.calOff), { day: 'numeric', month: 'short' })} – ${fmtDate(dayDate(fs.calOff + 6), { day: 'numeric', month: 'short' })}` : fmtDate(d, { month: 'long', year: 'numeric' });
  const free = ROOMS.filter(r => r.status === 'available' || r.type === 'shared').map(r => { const st = roomState(r, fs); return `<button type="button" class="fn" data-act="${r.type === 'shared' ? 'startIn' : 'bookRoom'}" data-v="${r.id}"><b>${rn(r)}</b><span>${r.type === 'shared' ? L(`${r.cap - st.used} seats free`, `${r.cap - st.used} مقعد متاح`) : st.detail}</span><span class="go">${r.type === 'shared' ? L('Seat someone', 'استقبال') : L('Book', 'احجز')} ${ic('arrowR', 'flip')}</span></button>`; }).join('');
  const pending = !fs.attnDone.pending ? `<div class="pend">${ic('clock')}<div class="grow"><b>${L(`${pn('mostafa')} asked for Training Room 02`, `${pn('mostafa')} طلب قاعة التدريب 02`)}</b><span class="muted"> · ${L('today 4:00 – 8:00 PM · EGP 1,120', 'اليوم 4:00 – 8:00 م · 1,120 ج.م')}</span></div>${btn(L('Decline', 'رفض'), { sm: true, kind: 'ghost', act: 'attn', v: 'pending:decline' })}${btn(L('Confirm', 'تأكيد'), { sm: true, kind: 'primary', act: 'attn', v: 'pending:confirm' })}</div>` : '';
  const toolbar = `<div class="toolbar">${seg([['day', L('Day', 'يوم')], ['week', L('Week', 'أسبوع')], ['month', L('Month', 'شهر')]], fs.calView, 'calView')}<div class="row">${btn('', { iconOnly: true, icon: 'chevL', act: 'calNav', v: -1, sm: true, title: L('Previous', 'السابق'), flip: true })}${btn(L('Today', 'اليوم'), { sm: true, act: 'calNav', v: 0 })}${btn('', { iconOnly: true, icon: 'chevR', act: 'calNav', v: 1, sm: true, title: L('Next', 'التالي'), flip: true })}<b style="margin-inline-start:6px;white-space:nowrap">${label}</b></div><div class="grow"></div>${rchips([['all', L('All rooms', 'كل الغرف')], ['training', L('Training', 'تدريب')], ['studio', L('Studio', 'استوديو')], ['office', L('Office', 'مكتب')], ['meeting', L('Meeting', 'اجتماعات')]], fs.calRoom, 'calRoom')}</div>`;
  const view = fs.calView === 'day' ? calDay(fs) : fs.calView === 'week' ? calWeek(fs) : calMonth(fs);
  const legend = `<div class="legend" style="margin:12px 0 28px"><span><i style="--lg:var(--info)"></i>${L('In progress', 'جارٍ')}</span><span><i style="--lg:var(--ok)"></i>${L('Confirmed', 'مؤكد')}</span><span><i style="--lg:var(--warn)"></i>${L('Waiting for OK', 'بانتظار التأكيد')}</span><span><i style="--lg:var(--border-strong)"></i>${L('Completed', 'مكتمل')}</span><span class="hide-m faint">${L('Tip: click an empty hour to book it', 'نصيحة: اضغط ساعة فارغة لحجزها')}</span></div>`;
  const n = nowMin();
  const up = BOOKINGS_TODAY.filter(b => b.s > n).sort((a, b) => a.s - b.s);
  const upList = `<div class="list">${up.map(b => { const pend = b.st === 'pending' && !fs.attnDone.pending; return `<div class="li li-btn" data-act="open" data-v="booking:${b.who}:${b.room}"><span class="num strong" style="width:70px">${fmtTime(b.s)}</span><span class="grow" style="min-width:0"><span class="strong trunc" style="display:block">${pn(b.who)}</span><span class="sm muted">${rn(ROOM[b.room])} · ${fmtDur(b.e - b.s)} · ${money(ROOM[b.room].rate * (b.e - b.s) / 60)}</span></span>${pend ? bookingStatus('pending') : bookingStatus(b.st === 'pending' ? 'confirmed' : b.st)}</div>`; }).join('')}</div>`;
  const avail = `<div class="avail">${ROOMS.filter(r => r.type !== 'shared').map(r => { const bs = bookingsFor(r.id, 0); const off = r.status === 'maintenance'; return `<div class="avail-row"><div class="sm strong trunc">${rn(r)}</div><div class="mt ${off ? 'off' : ''}">${off ? '' : bs.map(b => `<i style="inset-inline-start:${(b.s - DAY_START) / (DAY_END - DAY_START) * 100}%;width:${(b.e - b.s) / (DAY_END - DAY_START) * 100}%"></i>`).join('')}<b style="inset-inline-start:${(n - DAY_START) / (DAY_END - DAY_START) * 100}%"></b></div></div>`; }).join('')}</div><div class="sm faint" style="margin-top:10px">${L('Light = free · dark = booked · line = now', 'فاتح = متاح · داكن = محجوز · الخط = الآن')}</div>`;
  return head(L('Bookings', 'الحجوزات'), fs.attnDone.pending ? L('14 today · all confirmed', '14 حجزًا اليوم · كلها مؤكدة') : L('14 today · 1 waiting for your OK', '14 حجزًا اليوم · 1 بانتظار تأكيدك'), btn(L('New booking', 'حجز جديد'), { kind: 'primary', icon: 'plus', act: 'nav', v: 'create' }))
    + `<div class="freenow"><span class="l">${ic('checkCircle')}${L('Free right now', 'متاح الآن')}</span>${free}</div>` + pending
    + toolbar + view + legend
    + `<div class="grid g-2">${card(L('Coming up today', 'القادم اليوم'), upList, { flush: true })}${card(L('Availability today', 'التوفر اليوم'), avail)}</div>`;
}

/* ================================================================= ROOMS */
function statusKey(r, fs) {
  if (r.status === 'maintenance') return 'off';
  if (r.type === 'shared' || r.status === 'occupied') return 'busy';
  if (r.status === 'reserved') return 'reserved';
  return 'free';
}
function rRooms(fs) {
  const keys = [['all', L('All', 'الكل')], ['free', L('Free', 'متاحة'), null, 'var(--ok)'], ['busy', L('In use', 'مشغولة'), null, 'var(--info)'], ['reserved', L('Reserved', 'محجوزة'), null, 'var(--warn)'], ['off', L('Out of service', 'خارج الخدمة'), null, 'var(--text-3)']];
  const cnt = k => ROOMS.filter(r => k === 'all' || statusKey(r, fs) === k).length;
  const shown = ROOMS.filter(r => (fs.roomStatus === 'all' || statusKey(r, fs) === fs.roomStatus) && (fs.roomType === 'all' || r.type === fs.roomType));
  const n = nowMin();
  const cards = shown.map(r => {
    const st = roomState(r, fs);
    const s = fs.sessions.find(x => x.room === r.id && r.type !== 'shared');
    const bs = bookingsFor(r.id, 0);
    let nowLine;
    if (r.type === 'shared') nowLine = `<div class="between sm"><span>${L(`${st.used} of ${r.cap} seats taken`, `${st.used} من ${r.cap} مقعد مشغول`)}</span><span class="faint">${Math.round(st.used / r.cap * 100)}%</span></div><div class="meter" style="margin-top:6px"><i style="width:${st.used / r.cap * 100}%"></i></div>`;
    else if (s) nowLine = `<div class="row sm"><span class="avatar sm">${initials(s.who)}</span><span class="grow trunc"><b>${pn(s.who)}</b> · ${st.detail}</span></div>`;
    else nowLine = `<div class="sm muted">${st.detail}</div>`;
    let primary;
    const sk = statusKey(r, fs);
    if (s && sessStatus(s) === 'over') primary = btn(L('Check out', 'إنهاء وتحصيل'), { sm: true, kind: 'primary', act: 'open', v: 'checkout:' + s.id });
    else if (sk === 'busy') primary = btn(r.type === 'shared' ? L('Seat someone', 'استقبال عميل') : L('View session', 'عرض الجلسة'), { sm: true, act: r.type === 'shared' ? 'startIn' : 'nav', v: r.type === 'shared' ? r.id : 'sessions' });
    else if (sk === 'reserved') primary = btn(L('Check in Karim', 'تسجيل دخول كريم'), { sm: true, act: 'toast', v: L('Karim Mostafa checked in to Studio B', 'تم تسجيل دخول كريم مصطفى') });
    else if (sk === 'off') primary = btn(L('Back in service', 'إعادة للخدمة'), { sm: true, act: 'toast', v: L(`${rn(r)} is available again`, `${rn(r)} متاحة مجددًا`) });
    else primary = btn(L('Book', 'احجز'), { sm: true, act: 'bookRoom', v: r.id }) + btn(L('Start session', 'بدء جلسة'), { sm: true, kind: 'primary', act: 'startIn', v: r.id });
    const menu = mi('edit', L('Edit room', 'تعديل الغرفة'), 'toast', L('Room editor opens here', 'محرر الغرفة يفتح هنا')) + mi('cal', L('See bookings', 'عرض الحجوزات'), 'calRoomGo', r.type) + '<hr>' + mi('eyeoff', L('Mark out of service', 'إيقاف عن الخدمة'), 'toast', L(`${rn(r)} marked out of service`, `تم إيقاف ${rn(r)}`), L('Hides it from new bookings', 'يخفيها من الحجوزات الجديدة'), true);
    const tlBar = r.type === 'shared' ? '' : `<div class="rr-tl"><div class="mt ${sk === 'off' ? 'off' : ''}">${sk === 'off' ? '' : bs.map(b => `<i style="inset-inline-start:${(b.s - DAY_START) / (DAY_END - DAY_START) * 100}%;width:${(b.e - b.s) / (DAY_END - DAY_START) * 100}%"></i>`).join('')}<b style="inset-inline-start:${(n - DAY_START) / (DAY_END - DAY_START) * 100}%"></b></div><div class="mt-x"><span>8 ${L('AM', 'ص')}</span><span>3 ${L('PM', 'م')}</span><span>10 ${L('PM', 'م')}</span></div></div>`;
    return `<article class="card rr">
      <div class="rr-h"><div class="rr-pic">${roomArt(r.type)}</div><div class="grow" style="min-width:0"><h3 class="trunc">${rn(r)}</h3><div class="sm muted">${typeName(r.type)} · ${r.cap} ${r.type === 'shared' ? L('seats', 'مقعد') : L('people', 'شخص')}</div></div>${badge(st.cls, st.label)}</div>
      <div class="rr-now">${nowLine}</div>${tlBar}
      <div class="rr-f"><span class="price">${money(r.rate)} <small>/ ${r.type === 'shared' ? L('seat · hour', 'مقعد · ساعة') : L('hour', 'ساعة')}</small></span><div class="row">${primary}${moreMenu(fs, 'room-' + r.id, menu)}</div></div>
    </article>`;
  }).join('');
  const types = ['all', 'training', 'studio', 'office', 'shared', 'meeting'];
  return head(L('Rooms', 'الغرف'), L(`${ROOMS.length} spaces · ${cnt('free')} free right now`, `${ROOMS.length} مساحات · ${cnt('free')} متاحة الآن`), btn(L('Add room', 'إضافة غرفة'), { kind: 'primary', icon: 'plus', act: 'toast', v: L('Room form opens here', 'نموذج الغرفة يفتح هنا') }))
    + `<div class="toolbar">${rchips(keys.map(([k, l, , dot]) => [k, l, cnt(k), dot]), fs.roomStatus, 'roomStatus')}<div class="grow"></div><select class="input hide-m" style="width:180px" data-inp="roomType" aria-label="${L('Room type', 'نوع الغرفة')}">${types.map(t => `<option value="${t}" ${fs.roomType === t ? 'selected' : ''}>${t === 'all' ? L('All room types', 'كل الأنواع') : typeName(t)}</option>`).join('')}</select></div>`
    + (shown.length ? `<div class="grid g-rooms">${cards}</div>` : card('', rEmpty('search', L('No rooms match', 'لا توجد غرف مطابقة'), L('Try another status or room type.', 'جرّب حالة أو نوعًا آخر.'), btn(L('Show all rooms', 'عرض كل الغرف'), { act: 'roomReset' }))));
}

/* ============================================================ FINANCIALS */
function rTxTable(fs, rows) {
  if (!rows.length) return rEmpty('receipt', L('No transactions here', 'لا توجد معاملات'), L('Nothing matches these filters yet. Try another period or payment method.', 'لا شيء يطابق هذه الفلاتر. جرّب فترة أو طريقة دفع أخرى.'), btn(L('Clear filters', 'مسح الفلاتر'), { act: 'finClear' }));
  const mIcon = { cash: 'cash', card: 'card', instapay: 'phone' };
  return `<div class="tbl-wrap"><table class="tbl r-tbl"><thead><tr><th>${L('Time', 'الوقت')}</th><th>${L('Customer', 'العميل')}</th><th class="hide-t">${L('What for', 'مقابل')}</th><th class="hide-m">${L('Paid with', 'طريقة الدفع')}</th><th class="hide-m">${L('Status', 'الحالة')}</th><th class="r">${L('Amount', 'المبلغ')}</th><th class="hide-m"></th></tr></thead><tbody>${rows.map(t => `<tr class="click" data-act="open" data-v="receipt:${t.id}" tabindex="0"><td class="num muted">${t.d === 0 ? '' : L('Yesterday ', 'أمس ')}${fmtTime(t.t)}</td><td><span class="row"><span class="avatar sm">${initials(t.who)}</span>${pn(t.who)}</span></td><td class="hide-t muted trunc" style="max-width:240px">${L(t.what[0], t.what[1])}</td><td class="hide-m"><span class="row muted">${ic(mIcon[t.m])}${L(...METHODS[t.m])}</span></td><td class="hide-m">${txStatus(t.st)}</td><td class="r amt" style="${t.amt < 0 ? 'color:var(--text-3)' : ''}">${t.amt < 0 ? '−' : ''}${money(Math.abs(t.amt))}</td><td class="r hide-m"><span class="row-act link sm">${L('Receipt', 'الإيصال')} ${ic('arrowR', 'flip')}</span></td></tr>`).join('')}</tbody></table></div>`;
}
function rFin(fs) {
  const n = fs.finPeriod;
  const b = REV.b.slice(-n).reduce((a, x) => a + x, 0), p = REV.p.slice(-n).reduce((a, x) => a + x, 0), total = b + p, txc = n * 23 + 7;
  const tx = TX.filter(t => (fs.finKind === 'all' || t.kind === fs.finKind) && (fs.finMethod === 'all' || t.m === fs.finMethod));
  const tot = REV.b.map((x, i) => x + REV.p[i]).slice(-n);
  const skel = `<div class="rk"><div class="skel" style="width:50%"></div><div class="skel" style="width:70%;height:24px;margin-top:14px"></div><div class="skel" style="width:40%;margin-top:10px"></div></div>`;
  const kpis = fs.loading ? `<section class="rk-row">${skel.repeat(4)}</section>` : kpiRow(fs, [
    { l: L('Total revenue', 'إجمالي الإيرادات'), v: money(total), d: `<span class="up">↑ 8.4%</span> ${L('vs the period before', 'عن الفترة السابقة')}`, viz: spark(tot) },
    { l: L('From bookings', 'من الحجوزات'), v: money(b), d: L(`${Math.round(b / total * 100)}% of revenue`, `${Math.round(b / total * 100)}% من الإيرادات`), viz: spark(REV.b.slice(-n)) },
    { l: L('From products', 'من المنتجات'), v: money(p), d: `<span class="up">↑ 14.2%</span> · ${L('coffee is your top seller', 'القهوة الأكثر مبيعًا')}`, viz: spark(REV.p.slice(-n), 'var(--data2)') },
    { l: L('Average bill', 'متوسط الفاتورة'), v: money(total / txc), d: L(`${txc.toLocaleString('en-US')} payments`, `${txc.toLocaleString('en-US')} عملية دفع`) },
  ]);
  const split = [['cash', 46, 'var(--data1)'], ['card', 38, 'var(--data2)'], ['instapay', 16, 'var(--data3)']];
  const payCard = card(L('How customers paid', 'طرق الدفع'), `<div class="split">${split.map(([k, pc, c]) => `<i style="width:${pc}%;background:${c}" title="${L(...METHODS[k])} ${pc}%"></i>`).join('')}</div><div class="list" style="margin-top:6px">${split.map(([k, pc, c]) => `<div class="li" style="padding-inline:0;min-height:40px"><span class="sw" style="background:${c}"></span><span class="grow">${L(...METHODS[k])}</span><span class="num muted">${pc}%</span><span class="num strong" style="min-width:96px;text-align:end">${money(total * pc / 100)}</span></div>`).join('')}</div>`);
  const drawer = card(L('Cash drawer today', 'درج الكاش اليوم'), `<div class="between"><div><div class="kpi-v" style="font-size:22px">${money(2315)}</div><div class="kpi-d">${L('Expected in the drawer · last counted 9:00 AM by Nada', 'المتوقع في الدرج · آخر عد 9:00 ص بواسطة ندى')}</div></div></div><div style="margin-top:14px">${btn(L('Count drawer', 'عد الدرج'), { act: 'toast', v: L('Drawer count started — enter the amounts you find', 'بدأ عد الدرج — أدخل المبالغ الموجودة') })}</div>`);
  const exportMenu = `<div class="rel">${btn(L('Export', 'تصدير'), { kind: 'primary', icon: 'download', act: 'menu', v: 'export' })}${fs.menu === 'export' ? `<div class="menu wide">${mi('download', L('CSV spreadsheet', 'ملف CSV'), 'export', 'CSV', L('For Excel or your accountant', 'لإكسل أو المحاسب'))}${mi('printer', L('PDF statement', 'كشف PDF'), 'export', 'PDF', L('Ready to print or share', 'جاهز للطباعة أو المشاركة'))}<hr>${mi('send', L('Email me every Monday', 'أرسل لي كل إثنين'), 'toast', L('Weekly report scheduled for Mondays at 9 AM', 'تمت جدولة التقرير الأسبوعي'), L('A summary of last week', 'ملخص الأسبوع الماضي'))}</div>` : ''}</div>`;
  const filtered = fs.finKind !== 'all' || fs.finMethod !== 'all';
  return head(L('Financials', 'المالية'), L(`You’ve earned <b>${money(total)}</b> in the last ${n} days — 8% more than the ${n} days before.`, `حققت <b>${money(total)}</b> في آخر ${n} يومًا — بزيادة 8% عن الفترة السابقة.`), exportMenu)
    + `<div class="toolbar">${seg([[7, L('7 days', '7 أيام')], [14, L('14 days', '14 يومًا')], [30, L('30 days', '30 يومًا')]], fs.finPeriod, 'finPeriod')}${rchips([['all', L('All revenue', 'كل الإيرادات')], ['booking', L('Bookings', 'الحجوزات')], ['product', L('Products', 'المنتجات')]], fs.finKind, 'finKind')}<div class="grow"></div>${filtered ? `<button type="button" class="link sm" data-act="finClear">${L('Clear filters', 'مسح الفلاتر')}</button>` : ''}<select class="input hide-m" style="width:180px" data-inp="finMethod" aria-label="${L('Payment method', 'طريقة الدفع')}"><option value="all">${L('All payment methods', 'كل طرق الدفع')}</option>${Object.entries(METHODS).map(([v, l]) => `<option value="${v}" ${fs.finMethod === v ? 'selected' : ''}>${L(...l)}</option>`).join('')}</select></div>`
    + kpis
    + `<div class="grid g-2" style="margin-top:var(--gap)">${card(L(`Revenue by day`, 'الإيرادات اليومية'), fs.loading ? '<div class="skel" style="height:200px;border-radius:8px"></div>' : chartHTML(n, fs.finKind), { right: chartLegend(fs.finKind) })}<div class="stack">${payCard}${drawer}</div></div>`
    + `<div style="margin-top:var(--gap)">${card(L('Transactions', 'المعاملات'), rTxTable(fs, tx), { flush: true, right: `<span class="sm faint">${L('Click a row to see the receipt', 'اضغط على صف لعرض الإيصال')}</span>` })}</div>`;
}

/* ============================================================== PRODUCTS */
function rProducts(fs) {
  const q = fs.prodQ.trim().toLowerCase();
  const isLow = p => p.stock !== null && p.stock <= 5;
  const list = fs.products.filter(p => (fs.prodType === 'all' || p.type === fs.prodType) && (!fs.prodLow || isLow(p)) && (!q || (p.n.join(' ') + ' ' + p.sku).toLowerCase().includes(q)));
  const lowActive = fs.products.filter(p => p.active && isLow(p));
  const maxSold = Math.max(...fs.products.map(p => p.sold));
  const tIcon = { drink: 'cup', food: 'box', service: 'receipt', supply: 'edit' };
  const onSale = fs.products.filter(p => p.active).length;
  const banner = lowActive.length && !fs.prodLow ? `<div class="nudge">${ic('alert')}<span class="grow">${L(`<b>${lowActive[0].n[0]}</b> is running low — ${lowActive[0].stock} left.`, `<b>${lowActive[0].n[1]}</b> على وشك النفاد — متبقي ${lowActive[0].stock}.`)}</span><button type="button" class="link sm" data-act="prodLow">${L('Show low stock', 'عرض المخزون المنخفض')}</button></div>` : '';
  const row = p => `<tr class="${p.active ? '' : 'off'}"><td><span class="row"><span class="ptile">${ic(tIcon[p.type])}</span><span><span class="strong" style="display:block">${L(p.n[0], p.n[1])}</span><span class="sm faint mono">${p.sku}</span></span></span></td><td class="hide-m muted">${L(...PTYPES[p.type])}</td><td class="r amt">${money(p.price)}</td><td class="hide-m">${P.stockCell(p)}</td><td class="hide-t"><span class="soldbar"><i style="width:${p.sold / maxSold * 100}%"></i></span><span class="num sm muted">${p.sold.toLocaleString('en-US')}</span></td><td><span class="row"><button type="button" class="switch ${p.active ? 'on' : ''}" role="switch" aria-checked="${p.active}" data-act="prodToggle" data-v="${p.id}" aria-label="${L('On sale', 'معروض للبيع')}"></button><span class="sm ${p.active ? '' : 'faint'} hide-m">${p.active ? L('On sale', 'معروض') : L('Hidden', 'مخفي')}</span></span></td><td class="r"><span class="row" style="justify-content:flex-end"><span class="row-act">${btn(L('Edit', 'تعديل'), { sm: true, kind: 'ghost', act: 'open', v: 'product:' + p.id })}</span>${moreMenu(fs, 'prod-' + p.id, mi('edit', L('Edit', 'تعديل'), 'open', 'product:' + p.id) + mi('copy', L('Duplicate', 'نسخ'), 'toast', L('Product duplicated', 'تم نسخ المنتج')) + '<hr>' + mi('trash', L('Delete…', 'حذف…'), 'open', 'del:' + p.id, L('Past receipts are kept', 'الإيصالات السابقة محفوظة'), true))}</span></td></tr>`;
  let body;
  if (!list.length) body = card('', rEmpty(q ? 'search' : 'box', q ? L(`No products match “${esc(fs.prodQ)}”`, `لا منتجات تطابق «${esc(fs.prodQ)}»`) : L('Nothing here yet', 'لا شيء هنا بعد'), q ? L('Check the spelling, or add it as a new product — it only takes a moment.', 'تحقق من الكتابة، أو أضفه كمنتج جديد — يستغرق لحظة.') : L('Products you add show up at checkout right away.', 'المنتجات المضافة تظهر فورًا عند الدفع.'), btn(L('Clear search', 'مسح البحث'), { act: 'prodClear' }) + btn(L('Add product', 'إضافة منتج'), { kind: 'primary', icon: 'plus', act: 'open', v: 'product' })));
  else if (fs.prodView === 'grid') body = `<div class="grid g-products">${list.map(p => `<article class="card pcard ${p.active ? '' : 'off'}"><div class="between" style="align-items:flex-start"><span class="ptile">${ic(tIcon[p.type])}</span>${moreMenu(fs, 'prod-' + p.id, mi('edit', L('Edit', 'تعديل'), 'open', 'product:' + p.id) + '<hr>' + mi('trash', L('Delete…', 'حذف…'), 'open', 'del:' + p.id, '', true))}</div><div><div class="strong">${L(p.n[0], p.n[1])}</div><div class="sm faint">${L(...PTYPES[p.type])}</div></div><div class="price" style="font-size:20px">${money(p.price)}</div><div class="sm">${P.stockCell(p)}</div><div class="between pc-f"><span class="sm ${p.active ? '' : 'faint'}">${p.active ? L('On sale', 'معروض') : L('Hidden', 'مخفي')}</span><button type="button" class="switch ${p.active ? 'on' : ''}" role="switch" aria-checked="${p.active}" data-act="prodToggle" data-v="${p.id}" aria-label="${L('On sale', 'معروض للبيع')}"></button></div></article>`).join('')}</div>`;
  else body = card('', `<div class="tbl-wrap"><table class="tbl r-tbl"><thead><tr><th>${L('Product', 'المنتج')}</th><th class="hide-m">${L('Type', 'النوع')}</th><th class="r">${L('Price', 'السعر')}</th><th class="hide-m">${L('Stock', 'المخزون')}</th><th class="hide-t">${L('Sold · 30 days', 'المباع · 30 يومًا')}</th><th>${L('At checkout', 'عند الدفع')}</th><th></th></tr></thead><tbody>${list.map(row).join('')}</tbody></table></div>`, { flush: true });
  return head(L('Products', 'المنتجات'), L(`${fs.products.length} products · ${onSale} on sale · added to sessions and bookings at checkout`, `${fs.products.length} منتج · ${onSale} معروض · تُضاف للجلسات والحجوزات عند الدفع`), btn(L('Add product', 'إضافة منتج'), { kind: 'primary', icon: 'plus', act: 'open', v: 'product' }))
    + banner
    + `<div class="toolbar">${rsearch('prodQ', fs.prodQ, L('Search name or SKU', 'ابحث بالاسم أو الكود'), 250)}${rchips([['all', L('All', 'الكل'), fs.products.length], ...Object.keys(PTYPES).map(t => [t, L(...PTYPES[t]), fs.products.filter(p => p.type === t).length])], fs.prodType, 'prodType')}<button type="button" class="chip ${fs.prodLow ? 'on' : ''}" data-act="prodLow" aria-pressed="${fs.prodLow}">${fs.prodLow ? ic('check', 'chk') : '<span class="cdot" style="background:var(--warn)"></span>'}${L('Low stock', 'مخزون منخفض')}</button><div class="grow"></div><div class="hide-m">${seg([['list', L('List', 'قائمة')], ['grid', L('Grid', 'شبكة')]], fs.prodView, 'prodView')}</div></div>`
    + body;
}

/* ============================================================= CUSTOMERS */
function rCustomers(fs) {
  const q = fs.custQ.trim().toLowerCase();
  const segs = [['all', L('Everyone', 'الكل')], ['here', L('Here now', 'موجودون الآن')], ['regular', L('Regulars', 'دائمون')], ['new', L('New this month', 'جدد هذا الشهر')], ['suspended', L('Wi-Fi paused', 'واي فاي موقوف')]];
  const match = (k, s) => { const c = custInfo(fs, k); return s === 'all' || (s === 'here' && c.session) || (s === 'regular' && c.visits >= 20) || (s === 'new' && c.isNew) || (s === 'suspended' && c.suspended); };
  const list = PKEYS.filter(k => match(k, fs.custSeg) && (!q || PEOPLE[k].join(' ').toLowerCase().includes(q)));
  const rows = list.map(k => {
    const c = custInfo(fs, k);
    const status = c.session ? badge('b-info', L(`Here now · ${rn(ROOM[c.session.room])}`, `موجود · ${rn(ROOM[c.session.room])}`), { pulse: true }) : c.suspended ? badge('b-neutral', L('Wi-Fi paused', 'واي فاي موقوف')) : `<span class="sm muted row"><span class="sdot" style="background:var(--ok)"></span>${L('Wi-Fi active', 'واي فاي مفعل')}</span>`;
    const last = c.last === 0 ? L('Today', 'اليوم') : c.last === 1 ? L('Yesterday', 'أمس') : L(`${c.last} days ago`, `منذ ${c.last} أيام`);
    const acts = c.session ? btn(L('Check out', 'إنهاء'), { sm: true, act: 'open', v: 'checkout:' + c.session.id }) : btn(L('Book', 'احجز'), { sm: true, kind: 'ghost', act: 'bookFor', v: k }) + btn(L('Start session', 'بدء جلسة'), { sm: true, kind: 'ghost', act: 'startFor', v: k });
    return `<tr class="click" data-act="open" data-v="customer:${k}" tabindex="0"><td><span class="row"><span class="avatar">${initials(k)}</span><span><span class="strong" style="display:block">${pn(k)}${c.isNew ? ` <span class="new-tag">${L('New', 'جديد')}</span>` : ''}</span><span class="sm faint num" dir="ltr">${PEOPLE[k][2]}</span></span></span></td><td>${status}</td><td class="r num hide-m">${c.visits}</td><td class="hide-m muted">${last}</td><td class="r amt hide-t">${money(c.spent)}</td><td class="r"><span class="row-act row" style="justify-content:flex-end">${acts}</span></td></tr>`;
  }).join('');
  const here = PKEYS.filter(k => custInfo(fs, k).session).length;
  return head(L('Customers', 'العملاء'), L(`248 people · ${here} here right now`, `248 عميلًا · ${here} موجودون الآن`), btn(L('Add customer', 'إضافة عميل'), { kind: 'primary', icon: 'plus', act: 'toast', v: L('Quick-add customer opens here', 'إضافة عميل سريعة تفتح هنا') }))
    + `<div class="toolbar">${rsearch('custQ', fs.custQ, L('Search name or mobile', 'ابحث بالاسم أو الموبايل'), 260)}${rchips(segs.map(([k, l]) => [k, l, PKEYS.filter(x => match(x, k)).length]), fs.custSeg, 'custSeg')}</div>`
    + (list.length ? card('', `<div class="tbl-wrap"><table class="tbl r-tbl"><thead><tr><th>${L('Customer', 'العميل')}</th><th>${L('Status', 'الحالة')}</th><th class="r hide-m">${L('Visits', 'الزيارات')}</th><th class="hide-m">${L('Last visit', 'آخر زيارة')}</th><th class="r hide-t">${L('Total spent', 'إجمالي الإنفاق')}</th><th></th></tr></thead><tbody>${rows}</tbody></table></div><div class="tbl-foot sm muted">${L(`Showing ${list.length} of 248`, `عرض ${list.length} من 248`)} · <button type="button" class="link" data-act="toast" data-v="${esc(L('Loading more customers…', 'جارٍ تحميل المزيد…'))}">${L('Load more', 'تحميل المزيد')}</button></div>`, { flush: true })
      : card('', rEmpty(q ? 'search' : 'people', q ? L(`No one called “${esc(fs.custQ)}”`, `لا يوجد «${esc(fs.custQ)}»`) : L('No one in this group yet', 'لا أحد في هذه المجموعة بعد'), q ? L('Try their mobile number instead — it’s also their Wi-Fi username.', 'جرّب رقم الموبايل — وهو أيضًا اسم مستخدم الواي فاي.') : L('People appear here automatically as they visit.', 'يظهر العملاء هنا تلقائيًا مع زياراتهم.'), btn(L('Clear search', 'مسح البحث'), { act: 'custClear' }) + btn(L('Add customer', 'إضافة عميل'), { kind: 'primary', icon: 'plus', act: 'toast', v: L('Quick-add customer opens here', 'إضافة عميل سريعة تفتح هنا') }))));
}

/* ======================================================= CREATE BOOKING */
function rCreate(fs, c) {
  let html = P.scrCreate(fs, c);
  const d = fs.draft, busy = P.busySlots(d.room, d.date);
  const wins = [];
  for (let i = 0; i < SLOTS && wins.length < 3; i++) {
    if (busy[i] || P.pastSlot(d.date, i)) continue;
    let j = i; while (j < SLOTS && !busy[j] && j - i < 6) j++;
    if (j - i >= 4) { wins.push([i, j]); i = j; }
  }
  const sugg = wins.length ? `<div class="suggest"><span class="sm muted">${L('Suggested', 'مقترح')}</span>${wins.map(([a, b]) => `<button type="button" class="chip ${d.start === a && d.end === b ? 'on' : ''}" data-act="dSuggest" data-v="${a}:${b}">${fmtTime(slotTime(a))} – ${fmtTime(slotTime(b))} <span class="count">${fmtDur((b - a) * 30)}</span></button>`).join('')}</div>` : '';
  html = html.replace('<div class="helper">', sugg + '<div class="helper">');
  const who = d.newCust ? L('The new customer', 'العميل الجديد') : pn(d.cust || 'ahmed');
  html = html.replace(L('Paid at check-out. Products added during the booking are billed separately.', 'يُدفع عند الخروج. المنتجات المضافة أثناء الحجز تُحاسب منفصلة.'), `<b style="color:var(--text);font-weight:600">${L('What happens next', 'ماذا يحدث بعد ذلك')}</b><br>${L(`${who} gets an SMS confirmation right away. Payment is collected at check-out — products are added to the same bill.`, `يصل ${who} تأكيد SMS فورًا. الدفع عند الخروج — والمنتجات تُضاف لنفس الفاتورة.`)}`);
  return html;
}

/* ============================================================== CHECKOUT */
function payBlock(total, pay, received, prefix, dis = false) {
  const tiles = [['cash', 'cash', L('Cash', 'كاش')], ['card', 'card', L('Card', 'بطاقة')], ['instapay', 'phone', 'InstaPay']];
  const rec = Number(received) || 0, change = rec - total;
  const up = x => Math.ceil(total / x) * x;
  const quick = [...new Set([Math.ceil(total), up(50), up(100), up(500)])].slice(0, 4);
  let detail = '';
  if (pay === 'cash') detail = `<div class="field"><label>${L('Cash received', 'المبلغ المستلم')}</label><div class="input-affix"><input class="input num" data-inp="${prefix}received" value="${esc(received)}" inputmode="numeric" placeholder="${Math.ceil(total)}" ${dis ? 'tabindex="-1"' : ''}><span>${L('EGP', 'ج.م')}</span></div><div class="chips">${quick.map((x, i) => `<button type="button" class="chip" data-act="${prefix === 'm.' ? 'mRecv' : 'coRecv'}" data-v="${x}">${i === 0 ? L('Exact', 'بالضبط') : money(x)}</button>`).join('')}</div></div>${rec ? (change >= 0 ? `<div class="change ok"><span>${L('Change to give back', 'الباقي للعميل')}</span><b class="num">${money(change)}</b></div>` : `<div class="change short"><span>${L('Still short by', 'ناقص')}</span><b class="num">${money(-change)}</b></div>`) : ''}`;
  else if (pay === 'card') detail = `<div class="helper" style="margin:0">${ic('card')}<span>${L('Tap or insert the card on the POS terminal, then confirm here once it’s approved.', 'مرر البطاقة على جهاز الدفع، ثم أكد هنا بعد الموافقة.')}</span></div>`;
  else detail = `<div class="helper" style="margin:0">${ic('qr')}<span>${L('Ask the customer to send to <b>linkspace@instapay</b> and show you the confirmation.', 'اطلب من العميل التحويل إلى <b>linkspace@instapay</b> وإظهار التأكيد.')}</span></div>`;
  return { html: `<div class="field"><span class="flabel">${L('Payment method', 'طريقة الدفع')}</span><div class="pay-tiles">${tiles.map(([k, i, l]) => `<button type="button" class="pay ${pay === k ? 'on' : ''}" data-act="${prefix === 'm.' ? 'mPay' : 'coPay'}" data-v="${k}" aria-pressed="${pay === k}">${ic(i)}<span>${l}</span></button>`).join('')}</div></div>${detail}`, short: pay === 'cash' && rec > 0 && change < 0 };
}
function rCheckout(fs) {
  const co = fs.co;
  if (co.done) {
    const dn = co.done;
    const nextOver = fs.sessions.find(s => sessStatus(s) === 'over');
    return `<div class="done-view">${illo('done')}<h1>${L('All settled', 'تم الدفع بنجاح')}</h1><p class="muted">${L(`${money(dn.total)} collected from ${pn(dn.who)} by ${L(...METHODS[dn.pay]).toLowerCase()}${dn.change > 0 ? ` · give back ${money(dn.change)} change` : ''}.`, `تم تحصيل ${money(dn.total)} من ${pn(dn.who)} (${L(...METHODS[dn.pay])})${dn.change > 0 ? ` · الباقي ${money(dn.change)}` : ''}.`)}</p>
      <div class="card receipt-mini"><div class="between"><span class="muted">${L('Receipt', 'الإيصال')}</span><span class="mono">RC-2231</span></div><div class="between"><span class="muted">${L('Room', 'الغرفة')}</span><span>${rn(ROOM[dn.room])} · ${L('now free', 'أصبحت متاحة')}</span></div><div class="between"><span class="muted">${L('Total', 'الإجمالي')}</span><b class="num">${money(dn.total)}</b></div></div>
      <div class="actions" style="justify-content:center">${btn(L('Print receipt', 'طباعة الإيصال'), { icon: 'printer', act: 'toast', v: L('Sent to the front-desk printer', 'تم الإرسال للطابعة') })}${btn(L('Send on WhatsApp', 'إرسال واتساب'), { icon: 'send', act: 'toast', v: L(`Receipt sent to ${PEOPLE[dn.who][2]}`, `تم إرسال الإيصال إلى ${PEOPLE[dn.who][2]}`) })}${btn(L('Back to sessions', 'العودة للجلسات'), { kind: 'primary', act: 'coBack' })}</div>
      ${nextOver ? `<button type="button" class="next-up" data-act="coNext" data-v="${nextOver.id}">${ic('alert')}<span>${L(`Next: ${pn(nextOver.who)} is past their booking in ${rn(ROOM[nextOver.room])}`, `التالي: ${pn(nextOver.who)} تجاوز حجزه في ${rn(ROOM[nextOver.room])}`)}</span><span class="link">${L('Check out', 'إنهاء')} ${ic('arrowR', 'flip')}</span></button>` : ''}</div>`;
  }
  const s = findSess(fs, fs.coId) || fs.sessions[0];
  if (!s) return head(L('Checkout', 'الدفع'), '') + card('', rEmpty('quiet', L('No one to check out', 'لا يوجد من يدفع'), L('Everyone has settled up. New sessions will appear here when it’s time to pay.', 'الجميع دفع. ستظهر الجلسات هنا عند وقت الدفع.'), btn(L('Back to sessions', 'العودة للجلسات'), { act: 'nav', v: 'sessions' })));
  fs.coId = String(s.id);
  const k = sessCalc(fs, s), r = ROOM[s.room];
  const disc = Math.min(Number(co.disc) || 0, k.total), total = k.total - disc;
  const pb = payBlock(total, co.pay, co.received, 'co.');
  const lines = s.items.map(i => `<tr class="${fs.flash === s.id + ':' + i.p ? 'flash' : ''}"><td>${pShort(fs, i.p)}<div class="sm faint num">${money(pPrice(fs, i.p))} ${L('each', 'للواحد')}</div></td><td><div class="stepper"><button type="button" data-act="coQty" data-v="${i.p}:-1" aria-label="-">−</button><span>${i.q}</span><button type="button" data-act="coQty" data-v="${i.p}:1" aria-label="+">+</button></div></td><td class="r amt">${money(pPrice(fs, i.p) * i.q)}</td></tr>`).join('');
  const timeLbl = s.kind === 'shared' ? `${fmtDur(k.billMin)} × ${s.party} × ${money(r.rate)}/${L('h', 'س')}` : `${fmtDur(k.billMin)} × ${money(r.rate)}/${L('h', 'س')}${nowMin() < s.end ? L(' · booked time', ' · الوقت المحجوز') : ''}`;
  return head(L('Checkout', 'الدفع'), L('Review the bill, choose how they’re paying, and you’re done.', 'راجع الفاتورة واختر طريقة الدفع وانتهى الأمر.'), `<select class="input" style="width:260px" data-inp="coId" aria-label="${L('Session', 'الجلسة')}">${fs.sessions.map(x => `<option value="${x.id}" ${String(x.id) === String(s.id) ? 'selected' : ''}>${pn(x.who)} · ${rn(ROOM[x.room])}</option>`).join('')}</select>`)
    + `<div class="co"><div class="stack">
      <section class="card"><div class="card-b"><div class="row" style="gap:14px"><span class="avatar lg">${initials(s.who)}</span><div class="grow"><h3 style="font-size:17px">${pn(s.who)}</h3><div class="sm muted">${rn(r)} · ${L('since', 'منذ')} ${fmtTime(s.start)} · <span data-t="dur" data-s="${s.start}">${fmtDur(k.el)}</span></div></div>${P.sessBadge(s)}</div></div>
        <table class="tbl r-tbl co-tbl"><thead><tr><th>${L('Item', 'البند')}</th><th>${L('Qty', 'الكمية')}</th><th class="r">${L('Amount', 'المبلغ')}</th></tr></thead><tbody>
        <tr><td>${L('Room time', 'وقت الغرفة')}<div class="sm faint num">${timeLbl}</div></td><td class="faint">—</td><td class="r amt">${money(k.time)}</td></tr>${lines}
        <tr><td colspan="3"><button type="button" class="link sm" data-act="open" data-v="addItems:${s.id}">+ ${L('Add product', 'إضافة منتج')}</button>${co.discOpen ? '' : ` · <button type="button" class="link sm" data-act="coDisc">+ ${L('Add discount', 'إضافة خصم')}</button>`}</td></tr>
        ${co.discOpen ? `<tr><td>${L('Discount', 'خصم')}<div class="sm faint">${L('Shown on the receipt', 'يظهر في الإيصال')}</div></td><td><div class="input-affix" style="width:120px"><input class="input num" data-inp="co.disc" value="${esc(co.disc)}" inputmode="numeric" placeholder="0"><span>${L('EGP', 'ج.م')}</span></div></td><td class="r amt" style="color:var(--ok)">${disc ? '− ' + money(disc) : '—'}</td></tr>` : ''}
        </tbody></table>
        <div class="co-total"><span>${L('Total', 'الإجمالي')}</span><b class="num">${money(total)}</b></div></section>
      <div class="field"><label>${L('Note on receipt', 'ملاحظة على الإيصال')} <span class="faint">(${L('optional', 'اختياري')})</span></label><input class="input" placeholder="${L('e.g. Company invoice for Nile Tech', 'مثال: فاتورة لشركة نايل تك')}"></div>
    </div>
    <aside class="card co-pay"><div class="card-b" style="display:grid;gap:16px"><h3 class="sec-title">${L('Payment', 'الدفع')}</h3>${pb.html}
      <div class="sum-total"><span class="muted">${L('To collect', 'المطلوب')}</span><b>${money(total)}</b></div>
      ${btn(L(`Collect ${money(total)}`, `تحصيل ${money(total)}`), { kind: 'primary', block: true, act: 'coConfirm', disabled: pb.short, cls: co.busy ? 'is-loading' : '' })}
      <p class="sm faint" style="text-align:center">${L(`This closes the session, frees ${rn(r)} and creates the receipt.`, `سيُغلق الجلسة ويحرر ${rn(r)} وينشئ الإيصال.`)}</p></div></aside></div>`;
}

/* ============================================================= OVERLAYS */
function rOverlays(fs) {
  const demos = [
    [L('Add products', 'إضافة منتجات'), L('Popular items first · running total in the button', 'الأكثر طلبًا أولًا · الإجمالي في الزر'), { type: 'addItems', arg: 1, qty: { cap: 2, crois: 1 } }, 'addItems:1'],
    [L('Quick check out', 'إنهاء سريع'), L('Payment tiles · change calculator · says what happens', 'طرق الدفع · حساب الباقي · يوضح ما سيحدث'), { type: 'checkout', arg: 1, pay: 'cash', received: '2000' }, 'checkout:1'],
    [L('Create booking', 'إنشاء حجز'), L('Live availability as you choose', 'التوفر المباشر أثناء الاختيار'), { type: 'quickBook' }, 'quickBook'],
    [L('Customer', 'العميل'), L('Drawer · keeps the list visible', 'درج · يبقي القائمة ظاهرة'), { type: 'customer', arg: 'mariam' }, 'customer:mariam'],
    [L('Booking', 'الحجز'), L('Drawer · progress from request to done', 'درج · مراحل الحجز'), { type: 'booking', arg: 'mostafa:tr2' }, 'booking:mostafa:tr2'],
    [L('Receipt', 'الإيصال'), L('Drawer · refund kept apart', 'درج · الاسترداد منفصل'), { type: 'receipt', arg: 'INV-10482' }, 'receipt:INV-10482'],
    [L('Success', 'نجاح'), L('A real moment of completion', 'لحظة إنجاز واضحة'), { type: 'confirmed' }, 'confirmed'],
    [L('Delete confirmation', 'تأكيد الحذف'), L('Plain consequences · safer alternative offered', 'عواقب واضحة · بديل أكثر أمانًا'), { type: 'del', arg: 'mark' }, 'del:mark'],
  ];
  return head(L('Modals & drawers', 'النوافذ والأدراج'), L('Modals for quick decisions, drawers when the page behind still matters. Each one tells you what will happen before you commit.', 'النوافذ للقرارات السريعة، والأدراج عندما تهم الصفحة خلفها. كل واحدة توضح ما سيحدث قبل التأكيد.'))
    + `<div class="stages">${demos.map(([t, s, m, v]) => `<div class="stage-wrap"><div class="between"><div><div class="strong">${t}</div><div class="sm muted">${s}</div></div>${btn(L('Open live', 'فتح مباشر'), { sm: true, act: 'open', v })}</div><div class="stage">${overlay(fs, JSON.parse(JSON.stringify(m)), true)}</div></div>`).join('')}</div>`;
}

HOOKS.screen[ID] = (fs, c) => {
  const f = { dashboard: rDash, sessions: rSessions, bookings: rBookings, rooms: rRooms, financials: rFin, products: rProducts, customers: rCustomers, create: rCreate, checkout: rCheckout, overlays: rOverlays }[fs.screen];
  return f ? f(fs, c) : null;
};

/* ================================================================ MODALS */
const RM = {
  addItems(fs, m) {
    const s = findSess(fs, m.arg) || fs.sessions[0] || P.SESSIONS0[0];
    m.qty = m.qty || {};
    const q = (m.q || '').trim().toLowerCase();
    const active = fs.products.filter(p => p.active);
    const popular = [...active].sort((a, b) => b.sold - a.sold).slice(0, 4);
    const prods = active.filter(p => !q || p.n.join(' ').toLowerCase().includes(q));
    const n = Object.values(m.qty).reduce((a, x) => a + x, 0);
    const sum = Object.entries(m.qty).reduce((a, [id, x]) => a + pPrice(fs, id) * x, 0);
    const line = p => { const x = m.qty[p.id] || 0; return `<div class="pline ${x ? 'sel' : ''}"><div class="grow" style="min-width:0"><div class="strong trunc">${L(p.n[0], p.n[1])}</div><div class="sm faint">${money(p.price)}${p.stock !== null ? ` · ${p.stock <= 5 ? `<span style="color:var(--warn)">${L(`only ${p.stock} left`, `متبقي ${p.stock} فقط`)}</span>` : L(`${p.stock} left`, `متبقي ${p.stock}`)}` : ''}</div></div>${x ? `<div class="stepper"><button type="button" data-act="qty" data-v="${p.id}:-1" aria-label="-">−</button><span>${x}</span><button type="button" data-act="qty" data-v="${p.id}:1" aria-label="+">+</button></div>` : btn(L('Add', 'إضافة'), { sm: true, act: 'qty', v: p.id + ':1', icon: 'plus' })}</div>`; };
    return { cls: 'wide', html: `<div class="modal-h"><div><h3>${L('Add to the bill', 'إضافة للفاتورة')}</h3><div class="sub">${L(`${pn(s.who)} · ${rn(ROOM[s.room])}`, `${pn(s.who)} · ${rn(ROOM[s.room])}`)}</div></div>${closeBtn()}</div>
      <div class="modal-b"><div class="search r-search">${ic('search')}<input class="input" data-inp="m.q" value="${esc(m.q || '')}" placeholder="${L('Search products', 'ابحث عن منتج')}"></div>
      ${q ? '' : `<div><div class="flabel sm faint" style="margin-bottom:8px">${L('Popular right now', 'الأكثر طلبًا الآن')}</div><div class="chips">${popular.map(p => `<button type="button" class="chip" data-act="qty" data-v="${p.id}:1">+ ${L(p.n[0], p.n[1])}</button>`).join('')}</div></div>`}
      <div>${prods.length ? prods.slice(0, 6).map(line).join('') : rEmpty('search', L('Nothing found', 'لا نتائج'), L('Try another name — or add it to your catalog from Products.', 'جرّب اسمًا آخر — أو أضفه من المنتجات.'))}</div></div>
      <div class="modal-f"><span class="sm muted">${n ? L(`${n} item${n > 1 ? 's' : ''} · <b class="num" style="color:var(--text)">${money(sum)}</b>`, `${n} منتج · <b class="num" style="color:var(--text)">${money(sum)}</b>`) : L('Tap a product to add it', 'اضغط منتجًا لإضافته')}</span><div class="push">${btn(L('Cancel', 'إلغاء'), { kind: 'ghost', act: 'close' })}${btn(n ? L(`Add ${money(sum)} to bill`, `إضافة ${money(sum)} للفاتورة`) : L('Add to bill', 'إضافة للفاتورة'), { kind: 'primary', act: 'addItemsConfirm', disabled: !n })}</div></div>` };
  },
  checkout(fs, m) {
    const s = findSess(fs, m.arg) || fs.sessions[0] || P.SESSIONS0[0];
    const k = sessCalc(fs, s), r = ROOM[s.room];
    m.pay = m.pay || 'cash';
    const pb = payBlock(k.total, m.pay, m.received || '', 'm.');
    return { cls: '', html: `<div class="modal-h"><div class="row" style="gap:12px"><span class="avatar">${initials(s.who)}</span><div><h3>${L(`Check out ${pn(s.who)}`, `إنهاء جلسة ${pn(s.who)}`)}</h3><div class="sub">${rn(r)} · ${L('since', 'منذ')} ${fmtTime(s.start)} · ${fmtDur(k.el)}</div></div></div>${closeBtn()}</div>
      <div class="modal-b"><dl class="kv"><dt>${L('Room time', 'وقت الغرفة')}</dt><dd>${money(k.time)}</dd>${s.items.length ? `<dt>${L(`${s.items.reduce((a, i) => a + i.q, 0)} products`, `${s.items.reduce((a, i) => a + i.q, 0)} منتج`)}</dt><dd>${money(k.items)}</dd>` : ''}</dl>
      <div class="sum-total" style="margin-top:0"><span class="muted">${L('Total', 'الإجمالي')}</span><b>${money(k.total)}</b></div>
      ${pb.html}</div>
      <div class="modal-note">${ic('checkCircle')}${L(`Closes the session, frees ${rn(r)} and creates the receipt.`, `يُغلق الجلسة ويحرر ${rn(r)} وينشئ الإيصال.`)}</div>
      <div class="modal-f">${btn(L('Edit bill…', 'تعديل الفاتورة…'), { kind: 'ghost', act: 'toCheckout', v: s.id })}<div class="push">${btn(L(`Collect ${money(k.total)}`, `تحصيل ${money(k.total)}`), { kind: 'primary', act: 'checkoutConfirm', disabled: pb.short, cls: fs.busy === 'co' ? 'is-loading' : '' })}</div></div>` };
  },
  customer(fs, m) {
    const k = m.arg && PEOPLE[m.arg] ? m.arg : 'ahmed';
    const c = custInfo(fs, k), h = hash(k);
    const hist = [0, 1, 2].map(i => { const r = ROOMS[(h + i * 3) % 7]; const dd = 2 + i * 5 + (h % 4); const hrs = 2 + (h + i) % 4; return `<div class="li" style="padding-inline:0"><div class="grow"><div class="strong">${rn(r)}</div><div class="sm faint">${fmtDate(dayDate(-dd), { weekday: 'short', day: 'numeric', month: 'short' })} · ${fmtDur(hrs * 60)}</div></div><span class="num">${money(r.rate * hrs)}</span></div>`; }).join('');
    return { kind: 'drawer', html: `<div class="modal-h" style="padding-bottom:4px"><div class="row" style="gap:14px"><div class="avatar lg">${initials(k)}</div><div><h3>${pn(k)}</h3><button type="button" class="link sm num" dir="ltr" data-act="toast" data-v="${esc(L('Number copied', 'تم نسخ الرقم'))}">${PEOPLE[k][2]} ${ic('copy')}</button></div></div>${closeBtn()}</div>
      <div class="modal-b"><div class="row" style="flex-wrap:wrap;gap:6px">${c.visits >= 20 ? badge('b-info', L('Regular', 'عميل دائم'), { dot: false }) : ''}${c.suspended ? badge('b-neutral', L('Wi-Fi paused', 'واي فاي موقوف')) : badge('b-ok', L('Wi-Fi active', 'الواي فاي مفعل'))}</div>
      ${c.session ? `<div class="here-now"><div class="between"><span class="strong">${L(`Here now · ${rn(ROOM[c.session.room])}`, `موجود الآن · ${rn(ROOM[c.session.room])}`)}</span>${P.sessBadge(c.session)}</div><div class="sm muted">${L('since', 'منذ')} ${fmtTime(c.session.start)} · <span data-t="dur" data-s="${c.session.start}">${fmtDur(nowMin() - c.session.start)}</span> · ${L('bill', 'الفاتورة')} ${money(sessCalc(fs, c.session).total)}</div><div class="row">${btn(L('Add product', 'إضافة منتج'), { sm: true, icon: 'plus', act: 'open', v: 'addItems:' + c.session.id })}${btn(L('Check out', 'إنهاء وتحصيل'), { sm: true, kind: 'primary', act: 'open', v: 'checkout:' + c.session.id })}</div></div>` : ''}
      <div class="mini-stats"><div><span>${L('Visits', 'الزيارات')}</span><b>${c.visits}</b></div><div><span>${L('Spent', 'الإنفاق')}</span><b>${money(c.spent)}</b></div><div><span>${L('Usually', 'عادةً')}</span><b>${rn(ROOMS[h % 7]).split(' ')[0]}</b></div></div>
      <div><h3 class="sec-title" style="margin-bottom:2px">${L('Recent visits', 'الزيارات الأخيرة')}</h3><div class="list">${hist}</div></div>
      <div class="field"><label>${L('Notes for the team', 'ملاحظات للفريق')}</label><textarea class="input" placeholder="${L('e.g. Prefers the window desk; invoices go to Nile Tech', 'مثال: يفضل المكتب بجوار الشباك')}"></textarea><span class="hint">${L('Only visible to staff', 'تظهر للموظفين فقط')}</span></div></div>
      <div class="modal-f">${btn(L('Pause Wi-Fi', 'إيقاف الواي فاي'), { kind: 'danger-quiet', icon: 'wifi', act: 'open', v: 'del:wifi:' + k })}<div class="push">${btn(L('Book', 'احجز'), { act: 'bookFor', v: k })}${c.session ? '' : btn(L('Start session', 'بدء جلسة'), { kind: 'primary', icon: 'play', act: 'startFor', v: k })}</div></div>` };
  },
  booking(fs, m) {
    const [who, room] = String(m.arg || 'mostafa:tr2').split(':');
    const b = BOOKINGS_TODAY.find(x => x.who === who && (!room || x.room === room)) || BOOKINGS_TODAY[3];
    const r = ROOM[b.room];
    let st = b.st; if (st === 'pending' && fs.attnDone.pending) st = 'confirmed';
    const steps = [L('Requested', 'طُلب'), L('Confirmed', 'مؤكد'), L('Checked in', 'تسجيل الدخول'), L('Completed', 'مكتمل')];
    const idx = { pending: 0, confirmed: 1, live: 2, done: 3 }[st];
    const primary = st === 'pending' ? btn(L('Confirm booking', 'تأكيد الحجز'), { kind: 'primary', act: 'attn', v: 'pending:confirm' }) : st === 'confirmed' ? btn(L('Check in', 'تسجيل دخول'), { kind: 'primary', act: 'toast', v: L(`${pn(b.who)} checked in — session started`, `تم تسجيل دخول ${pn(b.who)} — بدأت الجلسة`) }) : st === 'live' ? btn(L('Open session', 'فتح الجلسة'), { kind: 'primary', act: 'nav', v: 'sessions' }) : btn(L('View receipt', 'عرض الإيصال'), { kind: 'primary', act: 'open', v: 'receipt:INV-10480' });
    return { kind: 'drawer', html: `<div class="modal-h"><div><h3>${rn(r)}</h3><div class="sub num">${fmtDate(dayDate(0), { weekday: 'long', day: 'numeric', month: 'short' })} · ${fmtTime(b.s)} – ${fmtTime(b.e)}</div></div>${closeBtn()}</div>
      <div class="modal-b"><div>${bookingStatus(st)}</div>
      <ol class="steps">${steps.map((s, i) => `<li class="${i < idx ? 'done' : i === idx ? 'now' : ''}"><span></span>${s}</li>`).join('')}</ol>
      <button type="button" class="who" data-act="open" data-v="customer:${b.who}"><span class="avatar">${initials(b.who)}</span><span class="grow"><b>${pn(b.who)}</b><span class="sm faint num" dir="ltr" style="display:block;text-align:start">${PEOPLE[b.who][2]}</span></span><span class="link sm">${L('Profile', 'الملف')} ${ic('arrowR', 'flip')}</span></button>
      <dl class="kv"><dt>${L('Duration', 'المدة')}</dt><dd>${fmtDur(b.e - b.s)}</dd><dt>${L('Rate', 'السعر')}</dt><dd>${money(r.rate)}/${L('hour', 'ساعة')}</dd><dt>${L('Total', 'الإجمالي')}</dt><dd>${money(r.rate * (b.e - b.s) / 60)}</dd><dt>${L('Paid', 'المدفوع')}</dt><dd>${b.who === 'laila' ? L('EGP 400 deposit (pending)', 'عربون 400 ج.م (معلق)') : L('At check-out', 'عند الخروج')}</dd><dt>${L('Booked by', 'بواسطة')}</dt><dd>${L('Nada · 11:48 AM', 'ندى · 11:48 ص')}</dd></dl></div>
      <div class="modal-f">${st === 'done' ? '' : btn(L('Cancel booking…', 'إلغاء الحجز…'), { kind: 'danger-quiet', act: 'open', v: 'del:cancel:' + b.who })}<div class="push">${st === 'done' ? '' : btn(L('Reschedule', 'تغيير الموعد'), { act: 'bookFor', v: b.who })}${primary}</div></div>` };
  },
  receipt(fs, m) {
    const t = TX.find(x => x.id === m.arg) || TX[0];
    const mIcon = { cash: 'cash', card: 'card', instapay: 'phone' };
    return { kind: 'drawer', html: `<div class="modal-h"><div><h3>${L('Receipt', 'الإيصال')} <span class="mono faint" style="font-weight:400">${t.id}</span></h3><div class="sub">${t.d === 0 ? L('Today', 'اليوم') : L('Yesterday', 'أمس')}, ${fmtTime(t.t)} · ${L('by Nada Samir', 'بواسطة ندى سمير')}</div></div>${closeBtn()}</div>
      <div class="modal-b"><div class="receipt-hero"><span class="muted sm">${t.amt < 0 ? L('Refunded', 'مسترد') : L('Amount paid', 'المبلغ المدفوع')}</span><b class="num">${money(Math.abs(t.amt))}</b>${txStatus(t.st)}</div>
      <div class="paper"><div class="between"><span>${L(t.what[0], t.what[1])}</span><span class="num">${money(Math.abs(t.amt))}</span></div><div class="between faint sm"><span>${L('VAT included', 'شامل الضريبة')}</span><span class="num">${money(Math.abs(t.amt) * 0.14 / 1.14)}</span></div><hr><div class="between strong"><span>${L('Total', 'الإجمالي')}</span><span class="num">${money(Math.abs(t.amt))}</span></div></div>
      <dl class="kv"><dt>${L('Customer', 'العميل')}</dt><dd>${pn(t.who)}</dd><dt>${L('Paid with', 'طريقة الدفع')}</dt><dd><span class="row" style="justify-content:flex-end">${ic(mIcon[t.m])}${L(...METHODS[t.m])}</span></dd><dt>${L('Branch', 'الفرع')}</dt><dd>${L('Nasr City', 'مدينة نصر')}</dd></dl></div>
      <div class="modal-f">${t.amt > 0 ? btn(L('Refund…', 'استرداد…'), { kind: 'danger-quiet', icon: 'refund', act: 'open', v: 'del:refund:' + t.id }) : ''}<div class="push">${btn(L('WhatsApp', 'واتساب'), { icon: 'send', act: 'toast', v: L(`Receipt sent to ${PEOPLE[t.who][2]}`, `تم الإرسال إلى ${PEOPLE[t.who][2]}`) })}${btn(L('Print', 'طباعة'), { kind: 'primary', icon: 'printer', act: 'toast', v: L('Sent to the front-desk printer', 'تم الإرسال للطابعة') })}</div></div>` };
  },
  confirmed(fs, m) {
    const b = m.b || { room: 'tr2', date: 1, s: 4, e: 10, cust: 'ahmed' };
    const r = ROOM[b.room], hours = (b.e - b.s) / 2;
    return { cls: 'narrow', html: `<div class="modal-b" style="padding-top:26px;justify-items:center;text-align:center">${illo('calendar')}<h3 style="font-size:19px">${L('You’re all set', 'تم الحجز')}</h3><p class="muted">${L(`${rn(r)} is booked for ${pn(b.cust)}. They’ll get an SMS confirmation in a moment.`, `تم حجز ${rn(r)} لـ ${pn(b.cust)}. ستصلهم رسالة تأكيد خلال لحظات.`)}</p>
      <div class="paper" style="width:100%;text-align:start"><div class="between"><span class="muted">${L('When', 'الموعد')}</span><span class="num">${fmtDate(dayDate(b.date), { weekday: 'short', day: 'numeric', month: 'short' })}, ${fmtTime(slotTime(b.s))} – ${fmtTime(slotTime(b.e))}</span></div><div class="between"><span class="muted">${L('Total at check-out', 'الإجمالي عند الخروج')}</span><b class="num">${money(hours * r.rate)}</b></div><div class="between"><span class="muted">${L('Reference', 'المرجع')}</span><span class="mono">BK-2419</span></div></div></div>
      <div class="modal-f">${btn(L('Send on WhatsApp', 'إرسال واتساب'), { icon: 'send', act: 'toast', v: L('Confirmation sent on WhatsApp', 'تم إرسال التأكيد عبر واتساب') })}<div class="push">${btn(L('Done', 'تم'), { kind: 'primary', act: 'confirmDone' })}</div></div>` };
  },
  del(fs, m) {
    const arg = String(m.arg || '');
    const frame = (icon, title, text, extra, cancel, confirm) => ({ cls: 'narrow', html: `<div class="modal-b" style="padding-top:22px"><div class="icon-danger">${ic(icon)}</div><h3 style="font-size:17px">${title}</h3><p class="muted">${text}</p>${extra || ''}</div><div class="modal-f">${btn(cancel, { act: 'close' })}<div class="push">${btn(confirm, { kind: 'danger', act: 'delConfirm' })}</div></div>` });
    if (arg.startsWith('wifi:')) { const k = arg.split(':')[1]; return frame('wifi', L(`Pause Wi-Fi for ${pn(k)}?`, `إيقاف الواي فاي لـ ${pn(k)}؟`), L('They’ll be disconnected from the router straight away. You can turn it back on any time from their profile.', 'سيتم فصله من الراوتر فورًا. يمكنك إعادة التفعيل من ملفه في أي وقت.'), '', L('Keep connected', 'إبقاء الاتصال'), L('Pause Wi-Fi', 'إيقاف الواي فاي')); }
    if (arg.startsWith('endfree:')) { const s = findSess(fs, arg.split(':')[1]) || fs.sessions[0]; return frame('x', L(`End ${pn(s.who)}’s session without charging?`, `إنهاء جلسة ${pn(s.who)} بدون تحصيل؟`), L(`The ${money(sessCalc(fs, s).total)} bill will be written off and noted in the activity log with your name.`, `سيتم إسقاط الفاتورة ${money(sessCalc(fs, s).total)} وتسجيل ذلك باسمك.`), '', L('Keep session', 'إبقاء الجلسة'), L('End without charge', 'إنهاء بدون تحصيل')); }
    if (arg.startsWith('refund:')) { const t = TX.find(x => x.id === arg.split(':')[1]) || TX[0]; return frame('refund', L(`Refund ${money(t.amt)} to ${pn(t.who)}?`, `استرداد ${money(t.amt)} لـ ${pn(t.who)}؟`), L(`The money goes back by ${L(...METHODS[t.m]).toLowerCase()}. The original receipt stays and a refund receipt is added.`, 'يُعاد المبلغ بنفس طريقة الدفع. يبقى الإيصال الأصلي ويُضاف إيصال استرداد.'), '', L('Don’t refund', 'عدم الاسترداد'), L('Refund', 'استرداد')); }
    if (arg.startsWith('cancel:')) { const k = arg.split(':')[1]; return frame('cal', L(`Cancel ${pn(k)}’s booking?`, `إلغاء حجز ${pn(k)}؟`), L('The time slot opens up for others and they get an SMS letting them know.', 'سيصبح الوقت متاحًا لغيره وستصله رسالة بذلك.'), '', L('Keep booking', 'إبقاء الحجز'), L('Cancel booking', 'إلغاء الحجز')); }
    const p = fs.products.find(x => x.id === arg) || P.PRODUCTS0.find(x => x.id === 'mark');
    return frame('trash', L(`Delete ${p.n[0]}?`, `حذف ${p.n[1]}؟`), L('It won’t appear at checkout anymore. Past receipts stay exactly as they are. This can’t be undone.', 'لن يظهر عند الدفع بعد الآن. الإيصالات السابقة تبقى كما هي. لا يمكن التراجع.'), p.active ? `<div class="nudge" style="margin:4px 0 0">${ic('eyeoff')}<span class="grow">${L('Just want to stop selling it?', 'تريد فقط إيقاف بيعه؟')}</span><button type="button" class="link sm" data-act="hideInstead" data-v="${p.id}">${L('Hide it instead', 'أخفه بدلًا من ذلك')}</button></div>` : '', L('Keep product', 'إبقاء المنتج'), L('Delete product', 'حذف المنتج'));
  },
};
HOOKS.modal[ID] = RM;

/* ================================================================ ACTIONS */
function later(fid, ms, fn) { setTimeout(() => { fn(); rerender(fid); }, ms); }
HOOKS.action[ID] = (fid, act, v, el, e) => {
  const fs = FS[fid], m = fs.modal;
  switch (act) {
    case 'qa': {
      const [sid, pid] = v.split(':'); const s = findSess(fs, sid); if (!s) return true;
      const it = s.items.find(i => i.p === pid); if (it) it.q++; else s.items.push({ p: pid, q: 1 });
      fs.sessExp[s.id] = true; fs.flash = s.id + ':' + pid; setTimeout(() => { if (fs.flash === s.id + ':' + pid) fs.flash = null; }, 1200);
      fs.undoFn = () => { const x = s.items.find(i => i.p === pid); if (x) { x.q--; if (!x.q) s.items.splice(s.items.indexOf(x), 1); } };
      toast(fid, L(`${pShort(fs, pid)} added to ${pn(s.who)}’s bill`, `تمت إضافة ${pShort(fs, pid)} لفاتورة ${pn(s.who)}`), true);
      return true;
    }
    case 'extend': {
      const s = findSess(fs, v); if (!s || s.kind !== 'exclusive') return true;
      const prev = s.end; s.end = Math.max(s.end, Math.ceil(nowMin())) + 30;
      fs.undoFn = () => { s.end = prev; }; fs.modal = null;
      toast(fid, L(`${pn(s.who)} now has until ${fmtTime(s.end)}`, `تم التمديد لـ ${pn(s.who)} حتى ${fmtTime(s.end)}`), true);
      return true;
    }
    case 'attn': {
      const [k, what, extra] = v.split(':');
      fs.attnDone[k] = true; fs.modal = null;
      let msg = '';
      if (k === 'over') msg = L('Mahmoud’s 1:30 PM booking moved to Office 02 — we’ll text him the change.', 'تم نقل حجز محمود لمكتب 02 — سنرسل له رسالة.');
      if (k === 'pending') msg = what === 'confirm' ? L('Confirmed — Mostafa will get an SMS.', 'تم التأكيد — ستصل مصطفى رسالة.') : L('Declined — Mostafa will be told politely.', 'تم الرفض — سيتم إبلاغ مصطفى.');
      if (k === 'stock') { const p = fs.products.find(x => x.id === extra); const prev = p.stock; p.stock = 24; msg = L(`${p.n[0]} restocked to 24.`, `تم تحديث مخزون ${p.n[1]} إلى 24.`); fs.undoFn = () => { p.stock = prev; delete fs.attnDone[k]; }; }
      if (k === 'deposit') msg = L('Laila’s deposit marked as paid.', 'تم تسجيل عربون ليلى كمدفوع.');
      if (!fs.undoFn || k !== 'stock') fs.undoFn = () => { delete fs.attnDone[k]; };
      toast(fid, msg, true);
      return true;
    }
    case 'open':
      if (fs.screen === 'bookings' && String(v).startsWith('customer:') && el.classList.contains('ev')) {
        const who = v.split(':')[1]; const b = BOOKINGS_TODAY.find(x => x.who === who);
        fs.modal = { type: 'booking', arg: who + ':' + (b ? b.room : '') }; return true;
      }
      return undefined;
    case 'toCheckout': fs.coId = String(v); fs.co = { pay: 'cash', received: '', disc: '', discOpen: false, busy: false, done: null }; fs.modal = null; fs.screen = 'checkout'; return true;
    case 'startIn': fs.modal = { type: 'start', room: v }; return true;
    case 'startFor': fs.modal = { type: 'start', cust: v }; return true;
    case 'clearQ': fs[v] = ''; return true;
    case 'sessAttn': fs.sessAttn = !fs.sessAttn; return true;
    case 'sessReset': fs.sessFilter = 'all'; fs.sessQ = ''; fs.sessAttn = false; return true;
    case 'roomStatus': fs.roomStatus = v; return true;
    case 'roomReset': fs.roomStatus = 'all'; fs.roomType = 'all'; return true;
    case 'custSeg': fs.custSeg = v; return true;
    case 'custClear': fs.custQ = ''; fs.custSeg = 'all'; return true;
    case 'prodLow': fs.prodLow = !fs.prodLow; return true;
    case 'prodClear': fs.prodQ = ''; fs.prodType = 'all'; fs.prodLow = false; return true;
    case 'finClear': fs.finKind = 'all'; fs.finMethod = 'all'; return true;
    case 'dSuggest': { const [a, b] = v.split(':').map(Number); fs.draft.start = a; fs.draft.end = b; fs.draftTouched = true; return true; }
    case 'mPay': m.pay = v; m.received = ''; return true;
    case 'mRecv': m.received = v; return true;
    case 'coPay': fs.co.pay = v; fs.co.received = ''; return true;
    case 'coRecv': fs.co.received = v; return true;
    case 'coDisc': fs.co.discOpen = true; return true;
    case 'coQty': {
      const s = findSess(fs, fs.coId); const [pid, d] = v.split(':'); const it = s && s.items.find(i => i.p === pid);
      if (it) { it.q += Number(d); if (it.q <= 0) { s.items.splice(s.items.indexOf(it), 1); toast(fid, L(`${pShort(fs, pid)} removed from the bill`, `تمت إزالة ${pShort(fs, pid)}`)); } }
      return true;
    }
    case 'coBack': fs.co.done = null; fs.screen = 'sessions'; return true;
    case 'coNext': fs.co = { pay: 'cash', received: '', disc: '', discOpen: false, busy: false, done: null }; fs.coId = String(v); return true;
    case 'coConfirm': {
      const s = findSess(fs, fs.coId); if (!s) return true;
      fs.co.busy = true; rerender(fid);
      later(fid, 700, () => {
        const k = sessCalc(fs, s), disc = Math.min(Number(fs.co.disc) || 0, k.total), total = k.total - disc;
        const rec = Number(fs.co.received) || 0;
        fs.sessions = fs.sessions.filter(x => x !== s);
        fs.co.done = { who: s.who, room: s.room, total, pay: fs.co.pay, change: fs.co.pay === 'cash' && rec > total ? rec - total : 0 };
        fs.co.busy = false;
      });
      return 'stop';
    }
    case 'checkoutConfirm': {
      const s = findSess(fs, m && m.arg); if (!s) { fs.modal = null; return true; }
      fs.busy = 'co'; rerender(fid);
      later(fid, 650, () => { fs.busy = null; fs.modal = null; fs.leaving = s.id; });
      setTimeout(() => {
        const total = sessCalc(fs, s).total, idx = fs.sessions.indexOf(s);
        fs.sessions = fs.sessions.filter(x => x !== s); fs.leaving = null;
        fs.undoFn = () => { fs.sessions.splice(idx, 0, s); };
        toast(fid, L(`All settled — ${money(total)} collected from ${pn(s.who)}`, `تم الدفع — تحصيل ${money(total)} من ${pn(s.who)}`), true);
        rerender(fid);
      }, 650 + 300);
      return 'stop';
    }
    case 'delConfirm': {
      const arg = String(m && m.arg || '');
      if (arg.startsWith('endfree:')) { const s = findSess(fs, arg.split(':')[1]); fs.sessions = fs.sessions.filter(x => x !== s); fs.modal = null; toast(fid, L(`${pn(s.who)}’s session ended — no charge`, `انتهت جلسة ${pn(s.who)} بدون تحصيل`)); return true; }
      if (arg.startsWith('refund:')) { fs.modal = null; toast(fid, L('Refund started — the refund receipt is in Transactions', 'بدأ الاسترداد — إيصال الاسترداد في المعاملات')); return true; }
      if (arg.startsWith('cancel:')) { fs.modal = null; toast(fid, L('Booking cancelled — the slot is free again', 'تم الإلغاء — الوقت متاح مجددًا')); return true; }
      if (arg.startsWith('wifi:')) { fs.modal = null; toast(fid, L('Wi-Fi paused — turn it back on any time', 'تم إيقاف الواي فاي — يمكنك التفعيل في أي وقت')); return true; }
      return undefined;
    }
    default: return undefined;
  }
};

/* ============================================================ SECTION: what changed */
HOOKS.section[ID] = () => {
  const groups = [
    ['More human', ['A greeting and a one-line summary of the day before any numbers', 'Microcopy that explains consequences: “Closes the session, frees Training Room 01”', 'Friendly empty states with small line illustrations — only on empty and success moments', 'Customer avatars on sessions, rooms and receipts so people come first']],
    ['Easier interactions', ['One primary action per screen, verbs with outcomes: “Collect EGP 1,948”', 'Search with “/” hint and a clear button; filters show a checkmark and “Clear filters”', 'Menus with short descriptions; destructive items separated at the bottom', 'Forms with helper text, inline validation and quick-fill chips (Exact / EGP 2,000)']],
    ['Clearer hierarchy', ['Content sits on one calm sheet over a warm canvas — fewer boxes', 'KPIs share one panel with dividers; sessions get a summary strip instead of more cards', 'Sentence-case table headers, avatar + name cells, money right-aligned', 'Status as small dots and soft pills; colour only when it means something']],
    ['Subtle delight', ['Pressed buttons settle by 1px; arrows nudge on hover', 'Loading buttons, skeletons that keep layout, cards that leave gracefully on checkout', 'Toasts with a drawn check mark and Undo for every reversible action', 'Live progress bars and “12 min left” counters that change colour as time runs out']],
    ['Faster operations', ['“Needs your attention” with one-click fixes (confirm, extend, move, restock)', 'Quick-add product chips on every session card', '“Free right now” and pending requests above the calendar', 'Contextual room actions, clickable rows that open drawers, full checkout with change calculator']],
  ];
  return `<div class="pg-changes"><div class="pg-sub" style="margin-top:0"><h3><span>Δ</span>What changed from the original Operations direction</h3><p>Same foundation — navy, tabular figures, precise tables. These are the refinements.</p></div><div class="pg-changes-grid">${groups.map(([t, items]) => `<div><b>${t}</b><ul>${items.map(i => `<li>${i}</li>`).join('')}</ul></div>`).join('')}</div></div>`;
};

/* ============================================================ DESIGN SYSTEM */
HOOKS.ds[ID] = (c) => {
  const fsx = P.FS[ID + '-dashboard'] ? JSON.parse(JSON.stringify(P.FS[ID + '-dashboard'])) : null;
  const dir = P.LANG === 'ar' ? 'rtl' : 'ltr';
  const fsd = fsx || { sessions: JSON.parse(JSON.stringify(P.SESSIONS0)), products: JSON.parse(JSON.stringify(P.PRODUCTS0)), sessExp: {}, attnDone: {}, menu: null };
  fsd.sessExp = {}; fsd.menu = null; fsd.flash = null; fsd.leaving = null; fsd.cid = ID;
  const sw = c.swatches.map(([n, h]) => `<div><i style="background:${h}"></i>${n}<code>${h}</code></div>`).join('');
  const status = [['var(--ok)', L('Success · free · paid', 'نجاح')], ['var(--info)', L('Live · in use', 'مباشر')], ['var(--warn)', L('Waiting · ending soon', 'انتظار')], ['var(--danger)', L('Overtime · destructive', 'خطر')]].map(([v, n]) => `<div><i style="background:${v}"></i>${n}</div>`).join('');
  const states = [['', L('Default', 'افتراضي')], ['is-hover', L('Hover', 'تمرير')], ['is-press', L('Pressed', 'ضغط')], ['is-focus', L('Focus', 'تركيز')], ['is-loading', L('Loading', 'تحميل')]];
  const voice = [
    ['No records found', 'No products match “xyz”. Check the spelling or add it as a new product.'],
    ['Session terminated successfully', 'All settled — EGP 1,948 collected from Ahmed.'],
    ['Are you sure?', 'Delete Whiteboard Markers? Past receipts stay exactly as they are.'],
    ['Error: connection failed', 'We couldn’t reach the router. Sessions keep running; Wi-Fi changes sync when it’s back.'],
    ['Submit', 'Confirm booking · EGP 840'],
    ['Status: Overdue', '35 min over · Mahmoud is booked here at 1:30 PM'],
  ];
  return `<div class="theme ds c-${ID}" dir="${dir}" lang="${P.LANG}">
    <div class="ds-grid">
      <div class="ds-sec"><h5>Typography</h5><div class="ds-type">${[['Greeting / title', '26 / 600 / −2%', 'font-size:26px;font-weight:600;letter-spacing:-.02em', L('Good afternoon, Nada', 'مساء الخير، ندى')], ['Money figure', '26 / 600 tabular', 'font-size:26px;font-weight:600;letter-spacing:-.02em;font-variant-numeric:tabular-nums', 'EGP 8,420'], ['Section', '15 / 600', 'font-size:15px;font-weight:600', L('Needs your attention', 'يحتاج انتباهك')], ['Body', '14 / 400', 'font-size:14px', L('Mahmoud is booked there at 1:30 PM.', 'محمود لديه حجز الساعة 1:30 م.')], ['Label', '12.5 / 500', 'font-size:12.5px;font-weight:500;color:var(--text-2)', L('Bill so far', 'الفاتورة حتى الآن')], ['Reference', 'Plex Mono 13', 'font-family:var(--font-mono);font-size:13px', 'RC-2231']].map(([l, s, st, x]) => `<div><span style="${st}">${x}</span><code>${l} · ${s}</code></div>`).join('')}</div><div class="sm faint" style="margin-top:10px">${c.type}</div></div>
      <div class="ds-sec"><h5>Colour</h5><div class="ds-sw">${sw}</div><h5 style="margin-top:18px">Status — meaning only</h5><div class="ds-sw">${status}</div></div>
      <div class="ds-sec"><h5>Surfaces · radius · elevation</h5><div class="ds-sheet"><div class="ds-sheet-side"></div><div class="ds-sheet-main"><div class="ds-sheet-card"></div><div class="ds-sheet-card"></div></div></div><p class="sm muted" style="margin:10px 0 12px">${L('Warm canvas → one white sheet → bordered panels. Borders do the work; shadows stay at 1–2px except popovers and modals.', 'خلفية دافئة ← ورقة بيضاء ← لوحات بحدود. الحدود تقوم بالعمل.')}</p><div class="ds-rad">${[['8', 'Controls'], ['12', 'Panels'], ['16', 'Modals'], ['999', 'Chips']].map(([v, l]) => `<div style="border-radius:${Math.min(+v, 22)}px">${v === '999' ? 'pill' : v + 'px'}<br>${l}</div>`).join('')}</div></div>
      <div class="ds-sec"><h5>Motion</h5><div class="motion">${[['80ms', L('Press feedback · 1px settle', 'الضغط')], ['140ms', L('Hover, toggles, chips', 'التمرير')], ['220ms', L('Modals, drawers, toasts', 'النوافذ')], ['300ms', L('Card leaving after checkout', 'خروج البطاقة')]].map(([t, d]) => `<div><code>${t}</code><span>${d}</span><i style="animation-duration:${parseInt(t) * 6}ms"></i></div>`).join('')}</div><p class="sm faint" style="margin-top:10px">${L('Easing cubic-bezier(.2,.8,.2,1). Nothing loops except live indicators. Reduced-motion users get instant changes.', 'لا حركة متكررة إلا المؤشرات المباشرة. احترام تقليل الحركة.')}</p></div>
      <div class="ds-sec"><h5>Button states</h5><div class="ds-row">${states.map(([cls, l]) => `<div class="st-col">${btn(L('Collect', 'تحصيل'), { kind: 'primary', cls })}<small>${l}</small></div>`).join('')}</div><div class="ds-row" style="margin-top:12px">${btn(L('Add product', 'إضافة منتج'), { icon: 'plus' })}${btn(go(L('Financials', 'المالية')), { kind: 'ghost' })}${btn(L('Refund…', 'استرداد…'), { kind: 'danger-quiet', icon: 'refund' })}${btn(L('Delete', 'حذف'), { kind: 'danger' })}${btn(L('Disabled', 'معطل'), { kind: 'primary', disabled: true })}</div></div>
      <div class="ds-sec"><h5>Inputs</h5><div class="stack" style="gap:10px">${rsearch('x', '', L('Search products', 'ابحث عن منتج'), 999)}${rsearch('x', 'Cappu', L('Search products', 'ابحث عن منتج'), 999)}<div class="field"><label>${L('Price', 'السعر')}</label><div class="input-affix"><input class="input is-error" value="0"><span>${L('EGP', 'ج.م')}</span></div><span class="err-text">${L('Add a price above 0 so it can be sold', 'أضف سعرًا أكبر من صفر ليمكن بيعه')}</span></div><div class="field"><label>${L('Mobile number', 'رقم الموبايل')}</label><input class="input is-focus num" value="0100 234 5567" dir="ltr"><span class="hint">${L('Also their Wi-Fi username', 'وهو اسم مستخدم الواي فاي')}</span></div></div></div>
      <div class="ds-sec"><h5>Filters · status · menus</h5>${rchips([['a', L('All Rooms', 'كل الغرف'), 6], ['b', L('Studio', 'استوديو'), 1]], 'a', 'x')}<div class="ds-row" style="margin-top:12px">${badge('b-ok', L('Available', 'متاحة'))}${badge('b-info', L('Live', 'مباشر'), { pulse: true })}${badge('b-warn', L('Ending soon', 'ينتهي قريبًا'))}${badge('b-danger', L('Overtime', 'تجاوز الوقت'))}${badge('b-neutral', L('Out of service', 'خارج الخدمة'))}</div><div class="menu ds-menu">${mi('extend', L('Extend 30 min', 'تمديد 30 دقيقة'), 'x', '')}${mi('download', L('CSV spreadsheet', 'ملف CSV'), 'x', '', L('For Excel or your accountant', 'لإكسل أو المحاسب'))}<hr>${mi('trash', L('Delete…', 'حذف…'), 'x', '', L('Past receipts are kept', 'الإيصالات السابقة محفوظة'), true)}</div></div>
      <div class="ds-sec"><h5>Session card</h5>${rsCard(fsd, fsd.sessions.find(s => s.id === 2) || fsd.sessions[0])}</div>
      <div class="ds-sec"><h5>Table</h5>${card('', rTxTable(fsd, TX.slice(0, 4)), { flush: true })}</div>
      <div class="ds-sec"><h5>Modal</h5><div class="ds-stage" style="min-height:330px">${overlay(Object.assign(fsd, { cid: ID }), { type: 'del', arg: 'mark' }, true)}</div></div>
      <div class="ds-sec ds-wide"><h5>Empty · loading · error · success</h5><div class="ds-states">
        <div class="card">${rEmpty('quiet', L('All quiet for now', 'هدوء تام الآن'), L('Check-ins appear here with a live timer.', 'تظهر الجلسات هنا مع مؤقت مباشر.'), btn(L('Start a session', 'بدء جلسة'), { sm: true, kind: 'primary' }))}</div>
        <div class="card card-b" style="display:grid;gap:12px;align-content:start"><div class="row"><div class="skel" style="width:34px;height:34px;border-radius:50%"></div><div class="grow"><div class="skel" style="width:60%"></div><div class="skel" style="width:40%;margin-top:8px"></div></div></div><div class="skel" style="height:40px"></div><div class="skel" style="width:80%"></div><span class="sm faint">${L('Skeletons keep the layout still while data loads.', 'الهياكل تحافظ على التخطيط أثناء التحميل.')}</span></div>
        <div style="display:grid;gap:10px;align-content:start"><div class="banner err">${ic('alert')}<div><b>${L('We couldn’t reach the router', 'تعذر الوصول للراوتر')}</b><span class="muted">${L('Sessions keep running. Wi-Fi changes will sync when it’s back.', 'الجلسات مستمرة. ستتم المزامنة عند العودة.')}</span><div style="margin-top:8px">${btn(L('Try again', 'حاول مجددًا'), { sm: true })}</div></div></div></div>
        <div class="card">${rEmpty('done', L('You’re all caught up', 'لا شيء يحتاجك'), L('Success is quiet: a check, one sentence, the next step.', 'النجاح هادئ: علامة، جملة، والخطوة التالية.'))}</div>
      </div></div>
      <div class="ds-sec ds-wide"><h5>Voice & tone — instead of… we say</h5><div class="voice">${voice.map(([a, b]) => `<div><s>${a}</s><span>${b}</span></div>`).join('')}</div></div>
    </div>
    <div class="ds-principles">${[['Say what happens', 'Buttons are verbs with outcomes. Before anything irreversible, one sentence explains the consequence.'], ['One obvious next step', 'Each screen has one primary action; attention items and cards carry their own fix.'], ['Answer every action', 'Press, load, confirm, undo. Fast, small and consistent — never flashy.']].map(([t, d]) => `<div><b>${t}</b>${d}</div>`).join('')}</div>
  </div>`;
};
})();
