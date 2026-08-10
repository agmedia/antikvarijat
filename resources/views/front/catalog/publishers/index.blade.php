@extends('front.layouts.app')
@section('title', __('front.publishers.meta_title'))
@section('description', __('front.publishers.meta_description'))
@section('schema_page_type', 'CollectionPage')

@push('meta_tags')
    @include('front.layouts.partials.collection-schema', [
        'collectionPaginator' => $publishers,
        'collectionName' => __('front.publishers.title') . ' - ' . $letter,
    ])
@endpush

@push('css_after')
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/category.css') }}">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/directory.css') }}">
@endpush

@section('content')
    @include('front.catalog.partials.directory-index', [
        'directoryItems' => $publishers,
        'directoryTitle' => __('front.publishers.title'),
        'directorySubtitle' => __('front.publishers.subtitle'),
        'directoryIcon' => 'fa-regular fa-building',
        'directoryRoute' => 'catalog.route.publisher',
        'directoryRouteParameter' => 'publisher',
        'directoryOpenLabel' => 'front.directories.open_publisher',
    ])
@endsection
