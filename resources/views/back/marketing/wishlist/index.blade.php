@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Liste želja</h1>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Sve liste želja {{ $wishlists->total() }}</h3>
                <div class="block-options">
                    <button class="btn btn-outline-primary" data-toggle="collapse" data-target="#filterBox">
                        <i class="fa fa-filter"></i> Filter
                    </button>
                    <a class="btn btn-primary" href="{{ route('wishlists') }}">
                        <i class="ci-trash"></i> Očisti filtere
                    </a>
                </div>
            </div>
            <div class="collapse show" id="filterBox">
                <div class="block-content bg-body-dark">
                    <form method="get" action="{{ route('wishlists') }}">
                        <div class="form-group row">
                            <div class="col-md-9">
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control"
                                           name="search"
                                           value="{{ request()->input('search') }}"
                                           placeholder="Pretraži po nazivu ili šifri artikla">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                                <div class="form-text small">
                                    Pretraži po nazivu ili šifri artikla.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter">
                        <thead>
                        <tr>
                            <th style="width: 80px;">Slika</th>
                            <th>Naziv</th>
                            <th>Šifra</th>
                            <th>E-mail (korisnik)</th>
                            <th>Dodano</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($wishlists as $w)
                            <tr>
                                <td>
                                    @if($w->product && $w->product->image)
                                        <img src="{{ asset($w->product->image) }}" height="60" alt="">
                                    @endif
                                </td>
                                <td>{{ $w->product->name ?? '---' }}</td>
                                <td>{{ $w->product->sku ?? '---' }}</td>
                                <td>{{ $w->email }}</td>
                                <td>{{ $w->created_at->format('d.m.Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">Nema zapisa u listi želja.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $wishlists->appends(request()->query())->links() }}
            </div>
        </div>

        <!-- Prikaz najtraženijih artikala -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Najviše predbilježenih artikala</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped">
                        <thead>
                        <tr>
                            <th>Naziv artikla</th>
                            <th>Šifra</th>
                            <th class="text-right">Broj prijava</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($topProducts as $item)
                            <tr>
                                <td>{{ $item->product->name }}</td>
                                <td>{{ $item->product->sku }}</td>
                                <td class="text-right">{{ $item->total }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    {{ $topProducts->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
