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

    @if($page->type === 'shop_type' && $page->shop_type)
        <div class="mt-10">
            <a href="/login" class="px-6 py-3 bg-indigo-600 text-white rounded-lg">
                Get started with {{ ucfirst(str_replace('_',' ', $page->shop_type)) }} POS
            </a>
        </div>
    @endif
@endsection
