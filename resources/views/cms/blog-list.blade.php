@extends('cms.layout')

@section('seo_title', 'Blog — NoovaPOS')
@section('seo_description', 'Latest news, guides and updates from NoovaPOS.')

@section('content')
    <h1 class="text-3xl font-bold mb-8">Blog</h1>

    @if($posts->isEmpty())
        <p class="text-slate-500">No posts yet.</p>
    @else
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <a href="/blog/{{ $post->slug }}" class="block bg-white rounded-xl border overflow-hidden hover:shadow">
                    @if($post->cover_image)
                        <img src="{{ $post->cover_image }}" alt="{{ $post->title }}" class="w-full h-40 object-cover"/>
                    @endif
                    <div class="p-4">
                        <h2 class="font-semibold text-lg mb-1">{{ $post->title }}</h2>
                        <p class="text-sm text-slate-500 mb-2">
                            {{ optional($post->published_at)->format('M d, Y') }}
                            @if($post->author) · {{ $post->author }} @endif
                        </p>
                        <p class="text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
