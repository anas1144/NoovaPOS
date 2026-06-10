@extends('cms.layout')

@section('seo_title', $post->seo_title ?: $post->title)
@section('seo_description', $post->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 160))
@section('seo_keywords', $post->seo_keywords)

@section('content')
    <article class="max-w-3xl mx-auto">
        <a href="/blog" class="text-sm text-indigo-600">&larr; Back to blog</a>
        <h1 class="text-3xl font-bold mt-3 mb-2">{{ $post->title }}</h1>
        <p class="text-sm text-slate-500 mb-6">
            {{ optional($post->published_at)->format('M d, Y') }}
            @if($post->author) · {{ $post->author }} @endif
        </p>
        @if($post->cover_image)
            <img src="{{ $post->cover_image }}" alt="{{ $post->title }}" class="w-full rounded-xl mb-6"/>
        @endif
        <div class="prose max-w-none">{!! $post->content !!}</div>
    </article>
@endsection
