@extends('back.layouts.backend')

@section('content')
    @include('back.catalog.partials.directory-index', [
        'items' => $publishers,
        'title' => 'Izdavači',
        'description' => 'Održavajte uredan imenik izdavača za dosljedne podatke i jednostavnije uređivanje artikala.',
        'listTitle' => 'Imenik izdavača',
        'countLabel' => 'izdavača',
        'icon' => 'fa-building-columns',
        'createLabel' => 'Novi izdavač',
        'createRoute' => 'publishers.create',
        'indexRoute' => 'publishers',
        'editRoute' => 'publishers.edit',
        'destroyRoute' => 'publishers.destroy.api',
        'routeParameter' => 'publisher',
        'searchPlaceholder' => 'Pretraži izdavače',
        'emptyTitle' => 'Nema pronađenih izdavača',
        'emptyDescription' => 'Promijenite pojam pretraživanja ili dodajte novog izdavača.',
    ])
@endsection
