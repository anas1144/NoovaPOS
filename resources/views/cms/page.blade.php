@extends('cms.layout')

@section('seo_title', $page->seo_title ?: $page->title)
@section('seo_description', $page->seo_description)
@section('seo_keywords', $page->seo_keywords)

@section('content')
    <h1 class="text-3xl font-bold mb-6">{{ $page->title }}</h1>

    @forelse($sections as $section)
        <section class="mb-8">
            @php $content = $section->content; @endphp
            {{-- Render raw HTML content; JSON-typed sections fall back to a code block. --}}
            @if(is_string($content) && \Illuminate\Support\Str::startsWith(trim($content), ['{','[']))
                <pre class="bg-slate-100 p-4 rounded text-xs overflow-auto">{{ $content }}</pre>
            @else
                <div class="prose max-w-none">{!! $content !!}</div>
            @endif
        </section>
    @empty
        <p class="text-slate-500">This page has no content yet.</p>
    @endforelse

    @if(($plans ?? collect())->isNotEmpty())
        <section class="mt-12" id="plans">
            <h2 class="text-2xl font-bold text-slate-900 mb-1">Plans for {{ ucfirst(str_replace('_',' ', $page->shop_type)) }}</h2>
            <p class="text-slate-500 mb-4">Subscribe to this plan to run a {{ strtolower(str_replace('_',' ', $page->shop_type)) }} store. You can add more business types later.</p>

            {{-- Billing + currency toggles --}}
            <div class="flex flex-wrap gap-3 mb-6">
                <div class="inline-flex rounded-full bg-slate-100 p-1" data-toggle="billing">
                    <button type="button" data-val="monthly" class="px-4 py-1.5 rounded-full text-sm font-semibold bg-indigo-600 text-white">Monthly</button>
                    <button type="button" data-val="yearly"  class="px-4 py-1.5 rounded-full text-sm font-semibold text-slate-600">Yearly (−2 mo)</button>
                </div>
                <div class="inline-flex rounded-full bg-slate-100 p-1" data-toggle="currency">
                    <button type="button" data-val="usd" class="px-4 py-1.5 rounded-full text-sm font-semibold bg-indigo-600 text-white">Global $</button>
                    <button type="button" data-val="pkr" class="px-4 py-1.5 rounded-full text-sm font-semibold text-slate-600">Pakistan ₨</button>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($plans as $plan)
                    @php
                        $mYearlyUsd = $plan->price_yearly ?: ((float) $plan->price * 10);
                        $mYearlyPkr = $plan->price_yearly_pkr ?: ((float) $plan->price_pkr * 10);
                    @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 flex flex-col {{ $plan->is_featured ? 'ring-2 ring-indigo-500' : '' }}">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500 min-h-[40px]">{{ $plan->description }}</p>

                        <div class="mt-4">
                            <div class="text-3xl font-bold text-slate-900 plan-price"
                                 data-musd="{{ (float) $plan->price }}"
                                 data-mpkr="{{ (float) $plan->price_pkr }}"
                                 data-yusd="{{ (float) $mYearlyUsd }}"
                                 data-ypkr="{{ (float) $mYearlyPkr }}">
                                ${{ number_format((float) $plan->price, 0) }}<span class="text-sm font-normal text-slate-500 plan-period">/mo</span>
                            </div>
                        </div>

                        @if($plan->trial_days)
                            <div class="mt-2 text-xs text-emerald-600">{{ $plan->trial_days }}-day free trial</div>
                        @endif
                        <ul class="mt-4 space-y-1 text-sm text-slate-600 flex-1">
                            @foreach(($plan->features ?? []) as $f)
                                <li>✓ {{ ucwords(str_replace('_',' ', $f)) }}</li>
                            @endforeach
                        </ul>
                        <a href="/login" class="mt-6 block text-center px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Get started
                        </a>
                    </div>
                @endforeach
            </div>
        </section>

        <script>
        (function () {
            var billing = 'monthly', currency = 'usd';
            function fmt(n) { return Number(n || 0).toLocaleString(); }
            function refresh() {
                var sym = currency === 'pkr' ? '₨' : '$';
                var period = billing === 'yearly' ? '/yr' : '/mo';
                document.querySelectorAll('.plan-price').forEach(function (el) {
                    var key = (billing === 'yearly' ? 'y' : 'm') + currency; // musd/mpkr/yusd/ypkr
                    var val = el.getAttribute('data-' + key);
                    el.innerHTML = sym + fmt(val) + '<span class="text-sm font-normal text-slate-500">' + period + '</span>';
                });
            }
            document.querySelectorAll('[data-toggle]').forEach(function (group) {
                group.querySelectorAll('button').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (group.dataset.toggle === 'billing') billing = btn.dataset.val;
                        else currency = btn.dataset.val;
                        group.querySelectorAll('button').forEach(function (b) {
                            b.className = b === btn
                                ? 'px-4 py-1.5 rounded-full text-sm font-semibold bg-indigo-600 text-white'
                                : 'px-4 py-1.5 rounded-full text-sm font-semibold text-slate-600';
                        });
                        refresh();
                    });
                });
            });
        })();
        </script>
    @elseif($page->type === 'shop_type' && $page->shop_type)
        <div class="mt-10">
            <a href="/login" class="px-6 py-3 bg-indigo-600 text-white rounded-lg">
                Get started with {{ ucfirst(str_replace('_',' ', $page->shop_type)) }} POS
            </a>
        </div>
    @endif
@endsection
