@extends('front.layouts.app')
@section('title', __('front.authors.meta_title'))
@section('description', __('front.authors.meta_description'))
@section('schema_page_type', 'CollectionPage')

@push('meta_tags')
    @include('front.layouts.partials.collection-schema', [
        'collectionPaginator' => $authors,
        'collectionName' => __('front.authors.title') . ' - ' . $letter,
    ])
@endpush

@push('css_after')
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/category.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/directory.css') }}">
@endpush

@section('content')
    @include('front.catalog.partials.directory-index', [
        'directoryItems' => $authors,
        'directoryTitle' => __('front.authors.title'),
        'directorySubtitle' => __('front.authors.subtitle'),
        'directoryIcon' => 'fa-regular fa-user-pen',
        'directoryRoute' => 'catalog.route.author',
        'directoryRouteParameter' => 'author',
        'directoryOpenLabel' => 'front.directories.open_author',
    ])
@endsection
