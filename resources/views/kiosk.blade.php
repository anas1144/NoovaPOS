<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Order Kiosk</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{font-family:system-ui,sans-serif}</style>
</head>
<body class="bg-slate-100">
<div id="app" class="max-w-5xl mx-auto p-4">
    <h1 id="title" class="text-2xl font-bold mb-4">Welcome — tap to order</h1>

    <div id="step-products">
        <div id="catalog" class="grid grid-cols-2 md:grid-cols-4 gap-3"></div>
    </div>

    <div id="cart" class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg p-4">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <div><span id="cart-count">0</span> item(s) · <b id="cart-total">0.00</b></div>
            <button onclick="nextStep()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold">Next →</button>
        </div>
    </div>

    <div id="step-table" class="hidden bg-white rounded-xl p-4 mt-4">
        <h2 class="text-lg font-semibold mb-3">Choose your table & seats</h2>
        <div class="mb-3">
            <label class="block text-sm mb-1">Your name (optional)</label>
            <input id="cust-name" class="border rounded px-3 py-2 w-full max-w-sm"/>
        </div>
        <div id="tables" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4"></div>
        <div id="seats-box" class="hidden mb-4">
            <label class="block text-sm mb-1">Seats</label>
            <div id="seats" class="flex flex-wrap gap-2"></div>
        </div>
        <div class="flex gap-3">
            <button onclick="backToProducts()" class="px-5 py-2 border rounded-lg">← Back</button>
            <button id="place-btn" onclick="placeOrder()" class="px-6 py-3 bg-green-600 text-white rounded-lg font-semibold">Place order</button>
        </div>
    </div>

    <div id="step-done" class="hidden bg-white rounded-xl p-8 mt-4 text-center">
        <h2 class="text-2xl font-bold text-green-600 mb-2">Order placed!</h2>
        <p class="text-slate-600 mb-2">Show this token at the counter:</p>
        <div id="token" class="text-4xl font-mono font-bold mb-4"></div>
        <button onclick="location.reload()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg">New order</button>
    </div>
</div>

<script>
const TOKEN = @json($token);
const API = '/api/public/kiosk/' + TOKEN;
let cfg = null, cart = [], selTable = null, selSeats = [];

function money(n){ return Number(n||0).toFixed(2); }

async function load(){
    const res = await fetch(API + '/config');
    const json = await res.json();
    cfg = json.data;
    if(!cfg){ document.getElementById('title').textContent = 'Display not available'; return; }
    document.getElementById('title').textContent = cfg.display.name || 'Order here';
    const items = [...(cfg.products||[]), ...(cfg.deals||[])];
    document.getElementById('catalog').innerHTML = items.map((it,i)=>`
        <button onclick="add(${i})" class="bg-white rounded-xl p-3 text-left shadow hover:ring-2 ring-indigo-400">
            <div class="font-semibold">${it.name}</div>
            <div class="text-sm text-slate-500">${money(it.price)} ${it.type==='deal'?'· Deal':''}</div>
        </button>`).join('');
    window._items = items;
}

function add(i){
    const it = window._items[i];
    const ex = cart.find(c => c.type===it.type && c.id===it.id);
    if(ex){ ex.quantity++; } else { cart.push({...it, quantity:1}); }
    render();
}
function render(){
    document.getElementById('cart-count').textContent = cart.reduce((a,c)=>a+c.quantity,0);
    document.getElementById('cart-total').textContent = money(cart.reduce((a,c)=>a+c.price*c.quantity,0));
}
function nextStep(){
    if(cart.length===0){ alert('Add something first'); return; }
    document.getElementById('step-products').classList.add('hidden');
    document.getElementById('cart').classList.add('hidden');
    document.getElementById('step-table').classList.remove('hidden');
    const tables = cfg.tables||[];
    document.getElementById('tables').innerHTML = tables.map((t,i)=>`
        <button onclick="pickTable(${i})" id="tbl-${t.id}" class="bg-white rounded-xl p-3 shadow ${t.state==='occupied'?'opacity-50':''}">
            <div class="font-semibold">${t.name}</div>
            <div class="text-xs text-slate-500">${t.seats||0} seats</div>
        </button>`).join('') || '<div class="text-slate-500">No tables — takeaway</div>';
}
function backToProducts(){
    document.getElementById('step-table').classList.add('hidden');
    document.getElementById('step-products').classList.remove('hidden');
    document.getElementById('cart').classList.remove('hidden');
}
function pickTable(i){
    const t = cfg.tables[i];
    selTable = t.id; selSeats = [];
    document.querySelectorAll('[id^=tbl-]').forEach(e=>e.classList.remove('ring-2','ring-indigo-500'));
    document.getElementById('tbl-'+t.id).classList.add('ring-2','ring-indigo-500');
    const box = document.getElementById('seats-box');
    box.classList.remove('hidden');
    document.getElementById('seats').innerHTML = Array.from({length:t.seats||0}).map((_,n)=>`
        <button onclick="toggleSeat(${n+1})" id="seat-${n+1}" class="w-10 h-10 border rounded-lg">${n+1}</button>`).join('');
}
function toggleSeat(n){
    const el = document.getElementById('seat-'+n);
    if(selSeats.includes(n)){ selSeats=selSeats.filter(x=>x!==n); el.classList.remove('bg-indigo-600','text-white'); }
    else { selSeats.push(n); el.classList.add('bg-indigo-600','text-white'); }
}
async function placeOrder(){
    document.getElementById('place-btn').disabled = true;
    const payload = {
        customer_name: document.getElementById('cust-name').value || null,
        table_id: selTable, seats: selSeats,
        items: cart.map(c=>({type:c.type, id:c.id, name:c.name, price:c.price, quantity:c.quantity})),
    };
    const res = await fetch(API + '/order', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
        body: JSON.stringify(payload),
    });
    const json = await res.json();
    if(res.ok && json.data){
        document.getElementById('step-table').classList.add('hidden');
        document.getElementById('step-done').classList.remove('hidden');
        document.getElementById('token').textContent = json.data.token_no;
    } else {
        alert(json.message || 'Failed to place order');
        document.getElementById('place-btn').disabled = false;
    }
}
load();
</script>
</body>
</html>
