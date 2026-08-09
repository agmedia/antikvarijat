@extends('back.layouts.backend')

@section('content')
    @include('back.catalog.partials.directory-index', [
        'items' => $authors,
        'title' => 'Autori',
        'description' => 'Uredite imenik autora i brzo pronađite sve zapise povezane s knjigama u katalogu.',
        'listTitle' => 'Imenik autora',
        'countLabel' => 'autora',
        'icon' => 'fa-user-pen',
        'createLabel' => 'Novi autor',
        'createRoute' => 'authors.create',
        'indexRoute' => 'authors',
        'editRoute' => 'authors.edit',
        'destroyRoute' => 'authors.destroy.api',
        'routeParameter' => 'author',
        'searchPlaceholder' => 'Pretraži autore',
        'emptyTitle' => 'Nema pronađenih autora',
        'emptyDescription' => 'Promijenite pojam pretraživanja ili dodajte novog autora.',
    ])
@endsection
