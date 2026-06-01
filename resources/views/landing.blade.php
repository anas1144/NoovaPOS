<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>NoovaPOS | Enterprise POS &amp; ERP Solution</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "surface":"#0b1326","surface-dim":"#0b1326","surface-bright":"#31394d",
        "surface-container-lowest":"#060e20","surface-container-low":"#131b2e",
        "surface-container":"rgba(15,23,42,0.8)","surface-container-high":"#222a3d",
        "surface-container-highest":"#2d3449","on-surface":"#dae2fd",
        "on-surface-variant":"#c3c6d7","outline":"#8d90a0","outline-variant":"#434655",
        "primary":"#b4c5ff","on-primary":"#002a78","primary-container":"#2563eb",
        "secondary":"#7bd0ff","tertiary":"#4edea3","error":"#ffb4ab",
        "background":"#0b1326","on-background":"#dae2fd","surface-variant":"#2d3449",
        "glass-border":"rgba(255,255,255,0.1)","text-primary":"#FFFFFF","text-secondary":"#94A3B8",
      },
      borderRadius:{"DEFAULT":"0.5rem","lg":"0.75rem","xl":"1rem","2xl":"1.5rem","full":"9999px"},
      fontFamily:{sans:["Inter","sans-serif"]},
      spacing:{"margin-desktop":"40px","margin-mobile":"16px","gutter":"24px","container-max":"1440px"},
      fontSize:{
        "display-lg":["48px",{lineHeight:"56px",letterSpacing:"-0.02em",fontWeight:"700"}],
        "headline-lg":["32px",{lineHeight:"40px",letterSpacing:"-0.01em",fontWeight:"600"}],
        "headline-lg-mobile":["24px",{lineHeight:"32px",fontWeight:"600"}],
        "title-md":["20px",{lineHeight:"28px",fontWeight:"600"}],
        "body-lg":["16px",{lineHeight:"24px",fontWeight:"400"}],
        "body-sm":["14px",{lineHeight:"20px",fontWeight:"400"}],
        "label-caps":["12px",{lineHeight:"16px",letterSpacing:"0.05em",fontWeight:"600"}],
      },
    }
  }
}
</script>
<style>
  body { background-color: #0b1326; color: #dae2fd; scroll-behavior: smooth; font-family: 'Inter', sans-serif; }
  .glass-card { background: rgba(15,23,42,0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
  .glass-card:hover { border-color: #2563eb; box-shadow: 0 0 20px rgba(37,99,235,0.15); }
  .electric-gradient-bg { background: linear-gradient(135deg, #2563EB 0%, #38BDF8 100%); }
  .text-gradient { background: linear-gradient(135deg, #b4c5ff 0%, #38BDF8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
  .sr { opacity: 0; transform: translateY(24px); transition: opacity 0.7s ease, transform 0.7s ease; }
  .sr.visible { opacity: 1; transform: none; }
  /* Mobile menu */
  #mobile-menu { display: none; }
  #mobile-menu.open { display: block; }
  /* Modal */
  #demo-modal { display: none; align-items: center; justify-content: center; }
  #demo-modal.open { display: flex; }
  /* Pricing skeleton */
  .price-skeleton { background: rgba(255,255,255,0.06); border-radius: 12px; animation: pulse 1.5s ease-in-out infinite; }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
  /* PKR tab hidden by default — shown only for Pakistan IP via JS */
  #btn-pkr { display: none; }
</style>
</head>
<body class="text-base">

{{-- ======================================================
     NAVIGATION
     ====================================================== --}}
<nav class="fixed top-0 w-full z-50 bg-surface-container/80 backdrop-blur-md border-b border-glass-border shadow-lg">
  <div class="flex justify-between items-center px-5 md:px-10 py-4 max-w-[1440px] mx-auto">

    {{-- Logo --}}
    <a href="/" class="flex items-center gap-3 shrink-0">
      <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="40" height="40" rx="8" fill="#2563EB"/>
        <path d="M10 28V12H14L20 20L26 12H30V28H26V18L21 25H19L14 18V28H10Z" fill="white"/>
        <path d="M12 14H28V16H12V14Z" fill="white" fill-opacity="0.3"/>
      </svg>
      <span class="text-xl font-bold text-[#b4c5ff]">NoovaPOS</span>
    </a>

    {{-- Desktop links --}}
    <div class="hidden md:flex items-center gap-8">
      <a class="text-[#c3c6d7] hover:text-[#dae2fd] transition-colors text-sm" href="#features">Features</a>
      <a class="text-[#c3c6d7] hover:text-[#dae2fd] transition-colors text-sm" href="#business-types">Business Types</a>
      <a class="text-[#c3c6d7] hover:text-[#dae2fd] transition-colors text-sm" href="#pricing">Pricing</a>
      <a class="text-[#c3c6d7] hover:text-[#dae2fd] transition-colors text-sm" href="#fbr">FBR Pakistan</a>
      <a class="text-[#c3c6d7] hover:text-[#dae2fd] transition-colors text-sm" href="#contact">Contact</a>
    </div>

    {{-- CTA buttons --}}
    <div class="flex items-center gap-3">
      <a href="/login" class="text-sm px-5 py-2 border border-[rgba(255,255,255,0.1)] rounded-lg text-[#7bd0ff] hover:bg-[rgba(37,99,235,0.12)] transition-all">Login</a>
      <button onclick="openDemoModal()" class="text-sm px-5 py-2 electric-gradient-bg text-white rounded-lg font-semibold hover:shadow-[0_0_15px_rgba(37,99,235,0.4)] transition-all active:scale-95">Book Demo</button>
      <a href="/register-tenant" class="hidden md:block text-sm px-5 py-2 bg-[rgba(255,255,255,0.07)] border border-[rgba(255,255,255,0.1)] rounded-lg text-[#dae2fd] hover:bg-[rgba(255,255,255,0.12)] transition-all">Free Trial</a>
    </div>

    {{-- Hamburger --}}
    <button id="hamburger-btn" onclick="toggleMobileMenu()" class="md:hidden ml-3 p-2 rounded-lg hover:bg-[rgba(255,255,255,0.08)] transition-colors" aria-label="Toggle menu">
      <span id="ham-icon" class="material-symbols-outlined">menu</span>
    </button>
  </div>

  {{-- Mobile menu --}}
  <div id="mobile-menu" class="md:hidden bg-[#0d1630] border-t border-[rgba(255,255,255,0.07)] px-5 py-4 space-y-3">
    <a href="#features" onclick="toggleMobileMenu()" class="block text-[#c3c6d7] hover:text-white py-2">Features</a>
    <a href="#business-types" onclick="toggleMobileMenu()" class="block text-[#c3c6d7] hover:text-white py-2">Business Types</a>
    <a href="#pricing" onclick="toggleMobileMenu()" class="block text-[#c3c6d7] hover:text-white py-2">Pricing</a>
    <a href="#fbr" onclick="toggleMobileMenu()" class="block text-[#c3c6d7] hover:text-white py-2">FBR Pakistan</a>
    <a href="#contact" onclick="toggleMobileMenu()" class="block text-[#c3c6d7] hover:text-white py-2">Contact</a>
    <div class="pt-3 flex flex-col gap-2 border-t border-[rgba(255,255,255,0.07)]">
      <a href="/login" class="block text-center py-2 border border-[rgba(255,255,255,0.12)] rounded-lg text-[#7bd0ff]">Login</a>
      <button onclick="openDemoModal();toggleMobileMenu()" class="py-2 electric-gradient-bg text-white rounded-lg font-semibold">Book Demo</button>
      <a href="/register-tenant" class="block text-center py-2 bg-[rgba(255,255,255,0.07)] border border-[rgba(255,255,255,0.1)] rounded-lg text-[#dae2fd]">Start Free Trial</a>
    </div>
  </div>
</nav>

{{-- ======================================================
     HERO
     ====================================================== --}}
<main class="pt-20 overflow-x-hidden">
<section class="relative px-5 md:px-10 py-20 max-w-[1440px] mx-auto flex flex-col md:flex-row items-center gap-12 sr">
  <div class="flex-1 space-y-6">
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full glass-card text-xs font-semibold text-[#4edea3] uppercase tracking-widest mb-2">
      <span class="material-symbols-outlined text-base">verified</span> FBR Ready · Offline-First · Multi-Tenant
    </div>
    <h1 class="text-[40px] md:text-[56px] font-bold leading-tight tracking-tight text-gradient">
      The All-In-One POS + ERP<br>for Every Business Type
    </h1>
    <p class="text-[#94A3B8] text-lg max-w-xl leading-relaxed">
      Tailored for retail, restaurants, pharmacies, and more. Multi-branch, multi-tenant, offline-ready infrastructure designed for the modern enterprise.
    </p>
    <div class="flex flex-wrap gap-4 pt-2">
      <a href="/register-tenant" class="px-8 py-4 electric-gradient-bg text-white rounded-xl font-bold flex items-center gap-2 hover:shadow-[0_0_20px_rgba(37,99,235,0.35)] transition-all active:scale-95">
        Start 14-Day Free Trial
        <span class="material-symbols-outlined">arrow_forward</span>
      </a>
      <button onclick="openDemoModal()" class="px-8 py-4 glass-card rounded-xl font-bold flex items-center gap-2 hover:bg-white/10 transition-all active:scale-95">
        <span class="material-symbols-outlined">play_circle</span>
        Book a Demo
      </button>
    </div>
    <div class="flex flex-wrap items-center gap-6 pt-4 text-[#4edea3]">
      <div class="flex items-center gap-2 text-sm"><span class="material-symbols-outlined text-lg">check_circle</span> FBR Pakistan Ready</div>
      <div class="flex items-center gap-2 text-sm"><span class="material-symbols-outlined text-lg">cloud_off</span> Offline-First PWA</div>
      <div class="flex items-center gap-2 text-sm"><span class="material-symbols-outlined text-lg">shield</span> Enterprise Secure</div>
    </div>
  </div>

  <div class="flex-1 relative">
    <div class="relative z-10 rounded-2xl overflow-hidden border border-[rgba(255,255,255,0.1)] shadow-2xl">
      <img src="/stitch_noovapos_enterprise_saas/stitch_noovapos_enterprise_saas/high_fidelity_3d_mockup_of_a_modern_pos_dashboard_on_a_sleek_tablet_screen._the/screen.png"
           alt="NoovaPOS Dashboard on tablet — glassmorphism POS interface"
           class="w-full object-cover"
           onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
      <div class="hidden w-full aspect-[16/10] bg-[rgba(37,99,235,0.08)] items-center justify-center rounded-xl" style="display:none">
        <span class="material-symbols-outlined text-[80px] text-[#2563eb] opacity-30">point_of_sale</span>
      </div>
    </div>
    <div class="absolute -top-10 -right-10 w-64 h-64 bg-[#2563eb]/20 rounded-full blur-[120px] -z-10"></div>
    <div class="absolute -bottom-10 -left-10 w-64 h-64 bg-[#7bd0ff]/15 rounded-full blur-[120px] -z-10"></div>
  </div>
</section>

{{-- ======================================================
     BUSINESS TYPES
     ====================================================== --}}
<section class="px-5 md:px-10 py-24 max-w-[1440px] mx-auto sr" id="business-types">
  <div class="text-center mb-14 space-y-3">
    <h2 class="text-[32px] font-semibold text-gradient">Built for Every Type of Business</h2>
    <p class="text-[#94A3B8]">Specific modules optimized for your industry requirements.</p>
  </div>
  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-5">
    @foreach ([
      ['storefront','Retail','Inventory, barcoding, supplier management','primary'],
      ['restaurant','Restaurant','Table management, KDS, recipe costing','secondary'],
      ['medical_services','Pharmacy','Batch expiry tracking and formula sales','tertiary'],
      ['water_drop','Water Supply','Subscription billing and route optimization','primary'],
      ['bakery_dining','Bakery','Waste tracking and morning prep lists','secondary'],
      ['devices','Electronics','Serial number tracking and warranty logs','tertiary'],
      ['apparel','Fashion','Size/colour matrix and style inventory','primary'],
      ['local_shipping','Distribution','B2B credit management and load sheets','secondary'],
      ['history_edu','Monthly Svc','Recurring billing and automated invoicing','tertiary'],
      ['settings_suggest','Custom','Flexible ERP core for unique workflows','primary'],
    ] as [$icon, $name, $desc, $color])
    <div class="glass-card p-5 rounded-xl space-y-3 group transition-all cursor-default">
      <div class="w-11 h-11 rounded-lg bg-{{ $color }}/10 flex items-center justify-center text-{{ $color }} group-hover:scale-110 transition-transform">
        <span class="material-symbols-outlined text-[22px]">{{ $icon }}</span>
      </div>
      <h3 class="font-semibold text-base text-[#dae2fd]">{{ $name }}</h3>
      <p class="text-sm text-[#94A3B8]">{{ $desc }}</p>
    </div>
    @endforeach
  </div>
</section>

{{-- ======================================================
     HIERARCHY
     ====================================================== --}}
<section class="bg-[#131b2e] py-24 border-y border-[rgba(255,255,255,0.07)] sr">
  <div class="max-w-[1440px] mx-auto px-5 md:px-10 text-center">
    <h2 class="text-[32px] font-semibold mb-14 text-gradient">Scale from One Shop to Global Enterprise</h2>
    <div class="flex flex-col md:flex-row items-center justify-center gap-6 md:gap-10">
      @foreach ([
        ['hub','Platform','#2563eb',true],
        ['corporate_fare','Tenants','#7bd0ff',false],
        ['apartment','Stores','#4edea3',false],
        ['point_of_sale','Registers','#b4c5ff',false],
      ] as [$icon, $label, $color, $gradient])
      <div class="flex flex-col items-center gap-3">
        <div class="w-20 h-20 rounded-full flex items-center justify-center shadow-lg {{ $gradient ? 'electric-gradient-bg' : 'glass-card' }}">
          <span class="material-symbols-outlined text-white text-[30px]" style="color: {{ $gradient ? 'white' : $color }}">{{ $icon }}</span>
        </div>
        <span class="font-semibold text-[#dae2fd]">{{ $label }}</span>
      </div>
      @if(!$loop->last)
      <span class="material-symbols-outlined rotate-90 md:rotate-0 text-[#8d90a0] text-2xl">trending_flat</span>
      @endif
      @endforeach
    </div>
  </div>
</section>

{{-- ======================================================
     FEATURES
     ====================================================== --}}
<section class="px-5 md:px-10 py-24 max-w-[1440px] mx-auto sr" id="features">
  <h2 class="text-[32px] font-semibold text-center mb-14 text-gradient">Enterprise-Grade Features</h2>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    @foreach ([
      ['speed','Ultra-Fast POS','Process transactions in milliseconds with keyboard shortcuts + touch UI.','primary'],
      ['wifi_off','Offline-First','Keep selling without internet. Auto-sync when back online via PWA.','secondary'],
      ['groups','Multi-Tenant','Manage multiple businesses and branches from one super admin panel.','tertiary'],
      ['inventory_2','Smart Inventory','Low stock alerts, batch tracking, expiry management, multi-warehouse.','primary'],
      ['verified','FBR Ready','Official Pakistan FBR integration — real-time invoice sync & QR codes.','secondary'],
      ['payments','Multi-Payment','Cash, card, wallet, split payment, credit sales and full refunds.','tertiary'],
      ['insights','AI Reports','Predictive analytics for sales, stock reorder, and anomaly detection.','primary'],
      ['upload_file','Background Imports','Import 100k+ products in queue — survives logout and browser close.','secondary'],
      ['install_mobile','PWA Support','Install on any device like a native app. Works on tablet and mobile.','tertiary'],
      ['admin_panel_settings','Fine-Grained Roles','Dynamic permissions per shop, module, and user role with full audit logs.','primary'],
      ['restaurant_menu','Restaurant Module','Floor mapping, kitchen display system, KOT, waiter app, split bills.','secondary'],
      ['notifications_active','Omni-Notifications','SMS, WhatsApp, email, and push notifications for sales and stock alerts.','tertiary'],
    ] as [$icon, $title, $desc, $color])
    <div class="flex gap-4 p-6 glass-card rounded-xl hover:border-[#2563eb] transition-all">
      <span class="material-symbols-outlined text-{{ $color }} text-[28px] shrink-0 mt-0.5">{{ $icon }}</span>
      <div>
        <h4 class="font-semibold text-[#dae2fd] mb-1.5">{{ $title }}</h4>
        <p class="text-sm text-[#94A3B8] leading-relaxed">{{ $desc }}</p>
      </div>
    </div>
    @endforeach
  </div>
</section>

{{-- ======================================================
     PRICING  (loaded from /api/public/plans)
     ====================================================== --}}
<section class="bg-[#131b2e] py-24 border-y border-[rgba(255,255,255,0.07)] sr" id="pricing">
  <div class="max-w-[1440px] mx-auto px-5 md:px-10">
    <div class="text-center mb-12 space-y-4">
      <h2 class="text-[32px] font-semibold text-gradient">Plans for Every Growth Stage</h2>
      <div class="flex flex-wrap justify-center gap-4 pt-2">
        {{-- Billing toggle --}}
        <div class="flex items-center gap-1 bg-[#2d3449] p-1 rounded-full border border-[rgba(255,255,255,0.1)]">
          <button id="btn-monthly" onclick="setBilling('monthly')" class="px-5 py-1.5 rounded-full text-xs font-bold bg-[#2563eb] text-white transition-all">Monthly</button>
          <button id="btn-yearly" onclick="setBilling('yearly')" class="px-5 py-1.5 rounded-full text-xs font-bold text-[#94A3B8] hover:text-white transition-all">Yearly (−20%)</button>
        </div>
        {{-- Currency toggle --}}
        <div class="flex items-center gap-1 bg-[#2d3449] p-1 rounded-full border border-[rgba(255,255,255,0.1)]">
          <button id="btn-usd" onclick="setCurrency('usd')" class="px-5 py-1.5 rounded-full text-xs font-bold bg-[#2563eb] text-white transition-all">USD $</button>
          <button id="btn-pkr" onclick="setCurrency('pkr')" class="px-5 py-1.5 rounded-full text-xs font-bold text-[#94A3B8] hover:text-white transition-all">PKR ₨</button>
        </div>
      </div>
    </div>

    {{-- Plans grid (populated by JS) --}}
    <div id="pricing-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      {{-- Skeleton loaders --}}
      @for ($i = 0; $i < 4; $i++)
      <div class="price-skeleton h-80 rounded-2xl"></div>
      @endfor
    </div>
    <p id="pricing-error" class="hidden text-center text-[#94A3B8] mt-8">Unable to load plans — <a href="#contact" class="text-[#2563eb] underline">contact us</a> for pricing.</p>
  </div>
</section>

{{-- ======================================================
     FBR PAKISTAN
     ====================================================== --}}
<section class="relative overflow-hidden sr" id="fbr">
  <div class="absolute inset-0 bg-green-900/10 -z-10"></div>
  <div class="max-w-[1440px] mx-auto px-5 md:px-10 py-24 flex flex-col md:flex-row items-center gap-16">
    <div class="flex-1 space-y-8">
      <div class="flex items-center gap-4">
        <div class="w-12 h-8 bg-[#00401A] rounded flex items-center justify-center border border-white/20 relative overflow-hidden">
          <div class="absolute left-0 top-0 bottom-0 w-2 bg-white/80"></div>
        </div>
        <h2 class="text-[28px] md:text-[32px] font-semibold text-gradient">Officially FBR Compliant</h2>
      </div>
      <p class="text-[#94A3B8] text-lg leading-relaxed max-w-lg">
        NoovaPOS is fully integrated with the Federal Board of Revenue Pakistan, ensuring your business stays tax-compliant automatically with every sale.
      </p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach ([
          'Real-time Invoice Sync to FBR',
          'QR Code on Every Receipt',
          'Annexure C Auto-Generation',
          'Auto Tax Calculations',
          'POS ID & NTN Integration',
          'Multiple Province Tax Scales',
        ] as $item)
        <div class="flex items-center gap-3 text-[#dae2fd] text-sm">
          <span class="material-symbols-outlined text-[#4edea3] text-lg">check_circle</span>
          {{ $item }}
        </div>
        @endforeach
      </div>
    </div>
    <div class="flex-1 glass-card p-1.5 rounded-2xl">
      <div class="bg-[rgba(37,99,235,0.08)] rounded-xl p-12 flex flex-col items-center justify-center gap-4 min-h-[280px]">
        <span class="material-symbols-outlined text-[64px] text-[#4edea3]">receipt_long</span>
        <div class="text-center">
          <div class="text-lg font-bold text-[#dae2fd]">FBR-Integrated POS</div>
          <div class="text-sm text-[#94A3B8] mt-1">Every invoice synced. Every sale compliant.</div>
        </div>
        <div class="flex gap-3 flex-wrap justify-center mt-2">
          <span class="px-3 py-1 rounded-full text-xs font-bold bg-[rgba(78,222,163,0.15)] text-[#4edea3]">NTN Verified</span>
          <span class="px-3 py-1 rounded-full text-xs font-bold bg-[rgba(37,99,235,0.15)] text-[#7bd0ff]">QR Receipts</span>
          <span class="px-3 py-1 rounded-full text-xs font-bold bg-[rgba(255,255,255,0.08)] text-[#c3c6d7]">Sandbox + Live</span>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ======================================================
     SOCIAL PROOF
     ====================================================== --}}
<section class="px-5 md:px-10 py-24 max-w-[1440px] mx-auto sr">
  <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-20 text-center">
    @foreach([['5,000+','Stores Running'],['$200M+','Monthly GMV'],['99.9%','Uptime Record'],['24/7','Priority Support']] as [$stat,$label])
    <div>
      <div class="text-[40px] md:text-[48px] font-bold text-gradient leading-tight">{{ $stat }}</div>
      <div class="text-xs font-semibold text-[#94A3B8] uppercase tracking-wider mt-1">{{ $label }}</div>
    </div>
    @endforeach
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    @foreach ([
      ['"Switching to NoovaPOS allowed our retail chain to manage 15 branches across 3 cities from one hub. The offline feature is a lifesaver."','Ahmed Malik','CEO, Urban Retail'],
      ['"The FBR integration was flawless. We were up and running in a day. Highly recommend for any food and beverage business in Pakistan."','Sara Khan','Founder, Bloom Bakery'],
      ['"NoovaPOS scales perfectly. We started with one register and now manage our entire pharmaceutical distribution network with it."','Omar Farooq','Ops Manager, MedSource'],
    ] as [$quote, $name, $title])
    <div class="glass-card p-8 rounded-2xl flex flex-col gap-6">
      <p class="text-[#94A3B8] italic leading-relaxed text-sm">{{ $quote }}</p>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-[rgba(37,99,235,0.15)] flex items-center justify-center">
          <span class="material-symbols-outlined text-[#7bd0ff] text-lg">person</span>
        </div>
        <div>
          <div class="font-bold text-[#dae2fd] text-sm">{{ $name }}</div>
          <div class="text-xs text-[#94A3B8]">{{ $title }}</div>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</section>

{{-- ======================================================
     FINAL CTA
     ====================================================== --}}
<section class="px-5 md:px-10 py-24 sr" id="contact">
  <div class="max-w-[1440px] mx-auto electric-gradient-bg rounded-[2rem] p-12 md:p-16 text-center text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="relative z-10 space-y-6">
      <h2 class="text-[36px] md:text-[48px] font-bold leading-tight">Ready to Run Every Counter<br>From One Platform?</h2>
      <p class="text-lg opacity-90 max-w-2xl mx-auto leading-relaxed">Join 5,000+ businesses scaling their operations with NoovaPOS. No credit card required to start.</p>
      <div class="flex flex-wrap justify-center gap-4 pt-2">
        <a href="/register-tenant" class="px-10 py-4 bg-white text-[#002a78] rounded-xl font-bold hover:bg-[#dae2fd] transition-all active:scale-95">Get Started Free</a>
        <button onclick="openDemoModal()" class="px-10 py-4 border border-white/30 bg-white/10 backdrop-blur rounded-xl font-bold hover:bg-white/20 transition-all active:scale-95">Book a Personalised Demo</button>
      </div>
    </div>
  </div>
</section>
</main>

{{-- ======================================================
     FOOTER
     ====================================================== --}}
<footer class="bg-[#060e20] border-t border-[rgba(255,255,255,0.07)]">
  <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8 px-5 md:px-10 py-14 max-w-[1440px] mx-auto">

    {{-- Brand --}}
    <div class="col-span-2 space-y-5">
      <a href="/" class="flex items-center gap-3">
        <svg width="36" height="36" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="40" height="40" rx="8" fill="#2563EB"/>
          <path d="M10 28V12H14L20 20L26 12H30V28H26V18L21 25H19L14 18V28H10Z" fill="white"/>
          <path d="M12 14H28V16H12V14Z" fill="white" fill-opacity="0.3"/>
        </svg>
        <span class="text-lg font-bold text-[#b4c5ff]">NoovaPOS</span>
      </a>
      <p class="text-sm text-[#94A3B8] max-w-xs leading-relaxed">The ultimate enterprise ecosystem for modern retail, hospitality, and distribution businesses. Scale without limits.</p>
      <div class="flex gap-4">
        <a href="mailto:sales@noovapos.com" class="material-symbols-outlined text-[#8d90a0] hover:text-[#b4c5ff] transition-colors cursor-pointer">alternate_email</a>
        <a href="tel:+923001234567" class="material-symbols-outlined text-[#8d90a0] hover:text-[#b4c5ff] transition-colors cursor-pointer">phone</a>
      </div>
    </div>

    {{-- Product links --}}
    <div class="space-y-3">
      <h5 class="font-bold text-[#dae2fd] text-sm uppercase tracking-wider">Product</h5>
      <ul class="space-y-2">
        <li><a class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors" href="#features">Features</a></li>
        <li><a class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors" href="#pricing">Pricing</a></li>
        <li><a class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors" href="#fbr">FBR Integration</a></li>
        <li><a class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors" href="#business-types">Business Types</a></li>
      </ul>
    </div>

    {{-- Company links --}}
    <div class="space-y-3">
      <h5 class="font-bold text-[#dae2fd] text-sm uppercase tracking-wider">Company</h5>
      <ul class="space-y-2">
        <li><a class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors" href="#contact">About</a></li>
        <li><button onclick="openDemoModal()" class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors text-left">Book Demo</button></li>
        <li><a class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors" href="mailto:sales@noovapos.com">Contact Sales</a></li>
      </ul>
    </div>

    {{-- Legal --}}
    <div class="space-y-3">
      <h5 class="font-bold text-[#dae2fd] text-sm uppercase tracking-wider">Legal</h5>
      <ul class="space-y-2">
        <li><button onclick="openLegalModal('privacy')" class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors text-left">Privacy Policy</button></li>
        <li><button onclick="openLegalModal('terms')" class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors text-left">Terms of Service</button></li>
        <li><button onclick="openLegalModal('sla')" class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors text-left">SLA</button></li>
      </ul>
    </div>

    {{-- Support --}}
    <div class="space-y-3">
      <h5 class="font-bold text-[#dae2fd] text-sm uppercase tracking-wider">Support</h5>
      <ul class="space-y-2">
        <li><a class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors" href="mailto:support@noovapos.com">support@noovapos.com</a></li>
        <li><a class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors" href="tel:+923001234567">+92 300 1234567</a></li>
        <li><a class="text-sm text-[#94A3B8] hover:text-[#dae2fd] transition-colors" href="/login">Login to Portal</a></li>
      </ul>
    </div>
  </div>
  <div class="max-w-[1440px] mx-auto px-5 md:px-10 py-5 border-t border-[rgba(255,255,255,0.06)] flex flex-col md:flex-row justify-between items-center text-[#94A3B8] gap-2">
    <p class="text-xs">© {{ date('Y') }} NoovaPOS Enterprise. All rights reserved.</p>
    <p class="text-xs">Designed for Scale · Built for Performance · Made in Pakistan 🇵🇰</p>
  </div>
</footer>

{{-- ======================================================
     LEGAL MODALS (Privacy Policy · Terms of Service · SLA)
     ====================================================== --}}
<div id="legal-modal" class="fixed inset-0 z-[110] bg-black/70 backdrop-blur-sm hidden items-center justify-center" onclick="closeLegalModalOutside(event)">
  <div class="bg-[#0d1630] border border-[rgba(255,255,255,0.1)] rounded-2xl shadow-2xl w-full max-w-2xl mx-4 md:mx-auto relative flex flex-col" style="max-height:85vh;" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between px-8 py-5 border-b border-[rgba(255,255,255,0.08)] shrink-0">
      <h2 id="legal-modal-title" class="text-lg font-bold text-[#dae2fd]">Legal</h2>
      <button onclick="closeLegalModal()" class="text-[#64748b] hover:text-white transition-colors">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div id="legal-modal-body" class="overflow-y-auto px-8 py-6 text-sm text-[#94A3B8] leading-relaxed space-y-4"></div>
    <div class="px-8 py-4 border-t border-[rgba(255,255,255,0.08)] shrink-0 text-xs text-[#64748b]">
      Questions? Email <a href="mailto:legal@noovapos.com" class="text-[#7bd0ff] hover:underline">legal@noovapos.com</a>
    </div>
  </div>
</div>

{{-- ======================================================
     DEMO REQUEST MODAL
     ====================================================== --}}
<div id="demo-modal" class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-sm" onclick="closeDemoModalOutside(event)">
  <div class="bg-[#0d1630] border border-[rgba(255,255,255,0.1)] rounded-2xl shadow-2xl w-full max-w-lg mx-4 md:mx-auto p-8 relative" onclick="event.stopPropagation()">
    <button onclick="closeDemoModal()" class="absolute top-4 right-4 text-[#64748b] hover:text-white transition-colors">
      <span class="material-symbols-outlined">close</span>
    </button>

    <div class="mb-6">
      <div class="flex items-center gap-3 mb-3">
        <svg width="32" height="32" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="8" fill="#2563EB"/><path d="M10 28V12H14L20 20L26 12H30V28H26V18L21 25H19L14 18V28H10Z" fill="white"/></svg>
        <h2 class="text-xl font-bold text-[#dae2fd]">Book a Demo</h2>
      </div>
      <p class="text-sm text-[#94A3B8]">Our team will reach out within 24 hours to schedule your personalised demo.</p>
    </div>

    <form id="demo-form" onsubmit="submitDemoRequest(event)" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-[#94A3B8] mb-1.5 uppercase tracking-wide">Full Name *</label>
          <input type="text" name="name" required placeholder="Ahmed Malik"
            class="w-full px-4 py-3 rounded-lg bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.1)] text-[#dae2fd] placeholder-[#64748b] focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[rgba(37,99,235,0.4)] text-sm transition-all"/>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#94A3B8] mb-1.5 uppercase tracking-wide">Email *</label>
          <input type="email" name="email" required placeholder="ahmed@business.com"
            class="w-full px-4 py-3 rounded-lg bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.1)] text-[#dae2fd] placeholder-[#64748b] focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[rgba(37,99,235,0.4)] text-sm transition-all"/>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-[#94A3B8] mb-1.5 uppercase tracking-wide">Phone</label>
          <input type="tel" name="phone" placeholder="+92 300 0000000"
            class="w-full px-4 py-3 rounded-lg bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.1)] text-[#dae2fd] placeholder-[#64748b] focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[rgba(37,99,235,0.4)] text-sm transition-all"/>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#94A3B8] mb-1.5 uppercase tracking-wide">Business Name</label>
          <input type="text" name="business_name" placeholder="Your Company Ltd."
            class="w-full px-4 py-3 rounded-lg bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.1)] text-[#dae2fd] placeholder-[#64748b] focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[rgba(37,99,235,0.4)] text-sm transition-all"/>
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-[#94A3B8] mb-1.5 uppercase tracking-wide">Business Type</label>
        <select name="business_type"
          class="w-full px-4 py-3 rounded-lg bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.1)] text-[#dae2fd] focus:outline-none focus:border-[#2563eb] text-sm transition-all">
          <option value="" class="bg-[#0d1630]">Select your industry</option>
          <option value="Retail" class="bg-[#0d1630]">Retail</option>
          <option value="Restaurant" class="bg-[#0d1630]">Restaurant / Food &amp; Beverage</option>
          <option value="Pharmacy" class="bg-[#0d1630]">Pharmacy</option>
          <option value="Water Supply" class="bg-[#0d1630]">Water Supply</option>
          <option value="Bakery" class="bg-[#0d1630]">Bakery</option>
          <option value="Electronics" class="bg-[#0d1630]">Electronics</option>
          <option value="Fashion" class="bg-[#0d1630]">Fashion / Apparel</option>
          <option value="Distribution" class="bg-[#0d1630]">Distribution / Wholesale</option>
          <option value="Other" class="bg-[#0d1630]">Other</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-[#94A3B8] mb-1.5 uppercase tracking-wide">Message</label>
        <textarea name="message" rows="3" placeholder="Tell us about your business and what you're looking for..."
          class="w-full px-4 py-3 rounded-lg bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.1)] text-[#dae2fd] placeholder-[#64748b] focus:outline-none focus:border-[#2563eb] focus:ring-1 focus:ring-[rgba(37,99,235,0.4)] text-sm transition-all resize-none"></textarea>
      </div>

      <div id="demo-error" class="hidden px-4 py-3 rounded-lg bg-[rgba(239,68,68,0.12)] border border-[rgba(239,68,68,0.25)] text-[#f87171] text-sm"></div>
      <div id="demo-success" class="hidden px-4 py-3 rounded-lg bg-[rgba(78,222,163,0.12)] border border-[rgba(78,222,163,0.25)] text-[#4edea3] text-sm"></div>

      <button type="submit" id="demo-submit" class="w-full py-3.5 electric-gradient-bg text-white rounded-xl font-bold hover:shadow-[0_0_20px_rgba(37,99,235,0.4)] transition-all active:scale-95 flex items-center justify-center gap-2">
        <span id="demo-submit-text">Send Demo Request</span>
        <span id="demo-submit-spinner" class="hidden material-symbols-outlined animate-spin text-base">progress_activity</span>
      </button>
    </form>
  </div>
</div>

{{-- ======================================================
     JAVASCRIPT
     ====================================================== --}}
<script>
/* ── Mobile menu ── */
function toggleMobileMenu() {
  const menu = document.getElementById('mobile-menu');
  const icon = document.getElementById('ham-icon');
  const isOpen = menu.classList.toggle('open');
  icon.textContent = isOpen ? 'close' : 'menu';
}

/* ── Scroll reveal ── */
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.08 });
document.querySelectorAll('.sr').forEach(el => observer.observe(el));

/* Smooth scroll for anchor links */
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const id = a.getAttribute('href').slice(1);
    const el = document.getElementById(id);
    if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  });
});

/* ── Legal modal ── */
const legalContent = {
  privacy: {
    title: 'Privacy Policy',
    body: `
      <h3 class="text-[#dae2fd] font-semibold text-base mb-2">1. Information We Collect</h3>
      <p>We collect information you provide directly to us (account registration, demo requests, support enquiries) and usage data generated as you interact with the platform (log files, IP addresses, device identifiers, feature usage).</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">2. How We Use Your Information</h3>
      <p>We use the information to provide, operate, and improve our services; send transactional and product communications; respond to support requests; detect and prevent fraud; and comply with legal obligations.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">3. Data Sharing</h3>
      <p>We do not sell your personal data. We may share data with service providers (hosting, email, analytics) under strict data-processing agreements. We may disclose information if required by law.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">4. Data Retention</h3>
      <p>We retain personal data for as long as your account is active or as needed to provide services. Tenant data is deleted within 30 days of account termination upon written request.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">5. Security</h3>
      <p>We implement industry-standard security controls including encryption in transit (TLS), encryption at rest, role-based access, and regular security audits.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">6. Your Rights</h3>
      <p>You may request access to, correction of, or deletion of your personal data by emailing <a href="mailto:legal@noovapos.com" class="text-[#7bd0ff] hover:underline">legal@noovapos.com</a>.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">7. Changes</h3>
      <p>We may update this policy from time to time. Material changes will be communicated via email or in-app notification. Continued use after changes constitutes acceptance.</p>
      <p class="mt-4 text-xs text-[#64748b]">Effective date: January 1, 2025 · Last updated: May 2026</p>
    `
  },
  terms: {
    title: 'Terms of Service',
    body: `
      <h3 class="text-[#dae2fd] font-semibold text-base mb-2">1. Acceptance</h3>
      <p>By accessing or using NoovaPOS you agree to be bound by these Terms of Service. If you do not agree, do not use the platform.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">2. Licence</h3>
      <p>NoovaPOS grants you a limited, non-exclusive, non-transferable licence to access and use the platform in accordance with your subscription plan.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">3. Acceptable Use</h3>
      <p>You may not use the platform for unlawful purposes, to transmit harmful content, to attempt to gain unauthorised access to other tenants' data, or to resell access without prior written agreement.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">4. Subscriptions & Payment</h3>
      <p>Subscriptions are billed in advance on a monthly or annual basis. All fees are non-refundable except as required by law. Plans renew automatically unless cancelled before the renewal date.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">5. Termination</h3>
      <p>We reserve the right to suspend or terminate accounts that violate these terms, with or without notice. You may cancel your subscription at any time from the billing settings.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">6. Limitation of Liability</h3>
      <p>To the maximum extent permitted by law, NoovaPOS shall not be liable for indirect, incidental, or consequential damages. Our total liability shall not exceed the fees paid in the 12 months preceding the claim.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">7. Governing Law</h3>
      <p>These terms are governed by the laws of Pakistan. Any disputes shall be resolved in the courts of Lahore, Punjab.</p>
      <p class="mt-4 text-xs text-[#64748b]">Effective date: January 1, 2025 · Last updated: May 2026</p>
    `
  },
  sla: {
    title: 'Service Level Agreement (SLA)',
    body: `
      <h3 class="text-[#dae2fd] font-semibold text-base mb-2">1. Uptime Commitment</h3>
      <p>NoovaPOS targets <strong class="text-[#dae2fd]">99.9% monthly uptime</strong> for all paid plans. This excludes scheduled maintenance windows (communicated ≥24 h in advance) and force-majeure events.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">2. Downtime Definition</h3>
      <p>Downtime is defined as the platform being entirely inaccessible from the public internet for more than 5 consecutive minutes, as measured by our external monitoring. Degraded performance does not constitute downtime.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">3. Credits</h3>
      <div class="overflow-x-auto mt-2">
        <table class="w-full text-xs border-collapse">
          <thead><tr class="text-[#dae2fd]"><th class="text-left py-1 pr-4">Monthly Uptime</th><th class="text-left py-1">Credit</th></tr></thead>
          <tbody>
            <tr class="border-t border-[rgba(255,255,255,0.06)]"><td class="py-1 pr-4">99.0% – 99.9%</td><td>5% of monthly fee</td></tr>
            <tr class="border-t border-[rgba(255,255,255,0.06)]"><td class="py-1 pr-4">95.0% – 98.9%</td><td>15% of monthly fee</td></tr>
            <tr class="border-t border-[rgba(255,255,255,0.06)]"><td class="py-1 pr-4">&lt; 95.0%</td><td>30% of monthly fee</td></tr>
          </tbody>
        </table>
      </div>
      <p class="mt-3">Credits must be requested within 30 days of the incident by emailing <a href="mailto:support@noovapos.com" class="text-[#7bd0ff] hover:underline">support@noovapos.com</a>.</p>
      <h3 class="text-[#dae2fd] font-semibold text-base mt-4 mb-2">4. Support Response Times</h3>
      <div class="overflow-x-auto mt-2">
        <table class="w-full text-xs border-collapse">
          <thead><tr class="text-[#dae2fd]"><th class="text-left py-1 pr-4">Severity</th><th class="text-left py-1">First Response</th></tr></thead>
          <tbody>
            <tr class="border-t border-[rgba(255,255,255,0.06)]"><td class="py-1 pr-4">Critical (platform down)</td><td>1 hour</td></tr>
            <tr class="border-t border-[rgba(255,255,255,0.06)]"><td class="py-1 pr-4">High (key feature broken)</td><td>4 hours</td></tr>
            <tr class="border-t border-[rgba(255,255,255,0.06)]"><td class="py-1 pr-4">Medium</td><td>1 business day</td></tr>
            <tr class="border-t border-[rgba(255,255,255,0.06)]"><td class="py-1 pr-4">Low / General</td><td>2 business days</td></tr>
          </tbody>
        </table>
      </div>
      <p class="mt-4 text-xs text-[#64748b]">Effective date: January 1, 2025 · Last updated: May 2026</p>
    `
  }
};

function openLegalModal(type) {
  const content = legalContent[type];
  if (!content) return;
  document.getElementById('legal-modal-title').textContent = content.title;
  document.getElementById('legal-modal-body').innerHTML = content.body;
  const modal = document.getElementById('legal-modal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  document.body.style.overflow = 'hidden';
}
function closeLegalModal() {
  const modal = document.getElementById('legal-modal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.body.style.overflow = '';
}
function closeLegalModalOutside(e) {
  if (e.target === document.getElementById('legal-modal')) closeLegalModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeLegalModal(); closeDemoModal(); } });

/* ── Pricing state ── */
let billing = 'monthly';
let currency = 'usd';
let plansData = [];

function setBilling(b) {
  billing = b;
  document.getElementById('btn-monthly').className = b === 'monthly'
    ? 'px-5 py-1.5 rounded-full text-xs font-bold bg-[#2563eb] text-white transition-all'
    : 'px-5 py-1.5 rounded-full text-xs font-bold text-[#94A3B8] hover:text-white transition-all';
  document.getElementById('btn-yearly').className = b === 'yearly'
    ? 'px-5 py-1.5 rounded-full text-xs font-bold bg-[#2563eb] text-white transition-all'
    : 'px-5 py-1.5 rounded-full text-xs font-bold text-[#94A3B8] hover:text-white transition-all';
  renderPlans();
}

function setCurrency(c) {
  currency = c;
  document.getElementById('btn-usd').className = c === 'usd'
    ? 'px-5 py-1.5 rounded-full text-xs font-bold bg-[#2563eb] text-white transition-all'
    : 'px-5 py-1.5 rounded-full text-xs font-bold text-[#94A3B8] hover:text-white transition-all';
  document.getElementById('btn-pkr').className = c === 'pkr'
    ? 'px-5 py-1.5 rounded-full text-xs font-bold bg-[#2563eb] text-white transition-all'
    : 'px-5 py-1.5 rounded-full text-xs font-bold text-[#94A3B8] hover:text-white transition-all';
  renderPlans();
}

function getPrice(plan) {
  if (plan.is_contact_sales || plan.is_custom) return null;
  if (currency === 'pkr') {
    return billing === 'yearly'
      ? (plan.price_yearly_pkr ?? (plan.price_pkr ? plan.price_pkr * 10 : null))
      : plan.price_pkr;
  }
  return billing === 'yearly'
    ? (plan.price_yearly ?? (plan.price ? plan.price * 10 : null))
    : plan.price;
}

function formatPrice(plan) {
  if (plan.is_contact_sales || plan.is_custom) return '<span class="text-4xl font-bold text-[#dae2fd]">Custom</span>';
  const price = getPrice(plan);
  if (!price && price !== 0) return '<span class="text-4xl font-bold text-[#dae2fd]">—</span>';
  const sym = currency === 'pkr' ? '₨' : '$';
  const period = billing === 'yearly' ? '/yr' : '/mo';
  return `<span class="text-4xl font-bold text-[#dae2fd]">${sym}${Number(price).toLocaleString()}</span><span class="text-[#94A3B8] text-sm ml-1">${period}</span>`;
}

function renderPlans() {
  if (!plansData.length) return;
  const grid = document.getElementById('pricing-grid');
  const featured = plansData.find(p => p.is_featured);

  grid.innerHTML = plansData.map((plan, i) => {
    const isFeatured = plan.is_featured || plan.id === (featured && featured.id);
    const features = (() => {
      try { return typeof plan.features === 'string' ? JSON.parse(plan.features) : (plan.features ?? []); }
      catch { return []; }
    })();

    const btnClass = isFeatured
      ? 'w-full py-3 electric-gradient-bg text-white rounded-xl font-bold shadow-lg hover:shadow-[0_0_20px_rgba(37,99,235,0.4)] transition-all active:scale-95 mt-auto'
      : 'w-full py-3 glass-card border border-[rgba(255,255,255,0.1)] rounded-xl font-bold text-[#dae2fd] hover:bg-white/10 transition-all active:scale-95 mt-auto';

    const btnLabel = plan.is_contact_sales || plan.is_custom
      ? 'Contact Sales'
      : isFeatured ? `Go ${plan.name}` : `Choose ${plan.name}`;
    const btnAction = plan.is_contact_sales || plan.is_custom
      ? `onclick="openDemoModal()"`
      : `onclick="window.location='/register-tenant'"`;

    return `
      <div class="glass-card p-7 rounded-2xl flex flex-col gap-5 relative ${isFeatured ? 'ring-2 ring-[#2563eb] bg-[rgba(37,99,235,0.06)]' : ''}">
        ${isFeatured ? '<div class="absolute -top-4 left-1/2 -translate-x-1/2 electric-gradient-bg text-white px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider whitespace-nowrap">Most Popular</div>' : ''}
        <div>
          <div class="text-xs font-bold text-[#2563eb] uppercase tracking-widest mb-2">${plan.name}</div>
          <div class="flex items-baseline gap-1">${formatPrice(plan)}</div>
          ${plan.description ? `<p class="text-xs text-[#94A3B8] mt-2 leading-relaxed">${plan.description}</p>` : ''}
        </div>
        ${features.length ? `
        <ul class="space-y-2.5 flex-1">
          ${features.slice(0, 6).map(f => `
          <li class="flex items-start gap-2 text-sm text-[#dae2fd]">
            <span class="material-symbols-outlined text-[#4edea3] text-base shrink-0 mt-0.5">check</span>
            ${f}
          </li>`).join('')}
        </ul>` : '<div class="flex-1"></div>'}
        ${plan.trial_days ? `<div class="text-xs text-[#4edea3] font-semibold">${plan.trial_days}-day free trial included</div>` : ''}
        <button ${btnAction} class="${btnClass}">${btnLabel}</button>
      </div>`;
  }).join('');
}

async function loadPlans() {
  try {
    const res = await fetch('/api/public/plans');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    plansData = json.data || [];
    if (!plansData.length) throw new Error('empty');
    renderPlans();
  } catch (e) {
    document.getElementById('pricing-grid').innerHTML = '';
    document.getElementById('pricing-error').classList.remove('hidden');
  }
}

loadPlans();

/* ── Country-based currency detection ── */
// PKR tab is only shown for users whose IP resolves to Pakistan.
// USD is always available. All other countries see USD only.
(async function detectCountryCurrency() {
  try {
    // Use a free, no-key-required IP geolocation endpoint
    const res = await fetch('https://ipapi.co/json/', { signal: AbortSignal.timeout(4000) });
    if (!res.ok) return;
    const geo = await res.json();
    const countryCode = (geo.country_code || '').toUpperCase();

    // Countries that have PKR pricing supported
    const pkrCountries = ['PK'];
    const pkrBtn = document.getElementById('btn-pkr');

    if (pkrCountries.includes(countryCode)) {
      // Show PKR tab and auto-select it
      if (pkrBtn) pkrBtn.style.display = '';
      setCurrency('pkr');
    } else {
      // Hide PKR tab — user gets USD only
      if (pkrBtn) pkrBtn.style.display = 'none';
      setCurrency('usd');
    }
  } catch {
    // Network error / timeout — default to USD, keep PKR tab hidden
    const pkrBtn = document.getElementById('btn-pkr');
    if (pkrBtn) pkrBtn.style.display = 'none';
  }
})();

/* ── Demo modal ── */
function openDemoModal() {
  const modal = document.getElementById('demo-modal');
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDemoModal() {
  document.getElementById('demo-modal').classList.remove('open');
  document.body.style.overflow = '';
}
function closeDemoModalOutside(e) {
  if (e.target === document.getElementById('demo-modal')) closeDemoModal();
}
// Escape key handled in the legal modal JS block above (closes both modals)

async function submitDemoRequest(e) {
  e.preventDefault();
  const form = document.getElementById('demo-form');
  const submitBtn = document.getElementById('demo-submit');
  const submitText = document.getElementById('demo-submit-text');
  const spinner = document.getElementById('demo-submit-spinner');
  const errorEl = document.getElementById('demo-error');
  const successEl = document.getElementById('demo-success');

  errorEl.classList.add('hidden');
  successEl.classList.add('hidden');
  submitBtn.disabled = true;
  submitText.textContent = 'Sending...';
  spinner.classList.remove('hidden');

  const data = Object.fromEntries(new FormData(form));
  // Detect country from language
  try { data.country = navigator.language?.split('-')[1]?.toUpperCase() || ''; } catch {}

  try {
    const res = await fetch('/api/public/demo-request', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
      },
      body: JSON.stringify(data),
    });
    const json = await res.json();
    if (res.ok && json.success) {
      successEl.textContent = json.message || 'Thanks! Our team will reach out within 24 hours.';
      successEl.classList.remove('hidden');
      form.reset();
      setTimeout(closeDemoModal, 3500);
    } else {
      const msgs = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message || 'Something went wrong.');
      errorEl.textContent = msgs;
      errorEl.classList.remove('hidden');
    }
  } catch {
    errorEl.textContent = 'Network error — please try again or email sales@noovapos.com';
    errorEl.classList.remove('hidden');
  } finally {
    submitBtn.disabled = false;
    submitText.textContent = 'Send Demo Request';
    spinner.classList.add('hidden');
  }
}
</script>
</body>
</html>
