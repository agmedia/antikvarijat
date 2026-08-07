@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Otkup knjiga</h1>
                <a class="btn btn-alt-primary mt-3 mt-sm-0" href="{{ route('book.purchases.content.edit') }}">
                    <i class="fa fa-edit mr-1"></i>Uredi tekstove HR / EN
                </a>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Prijave za otkup knjiga</h3>
                <div class="block-options">
                    <span class="badge badge-pill badge-primary font-size-sm">
                        Ukupno: {{ $purchases->total() }} prijava
                    </span>
                </div>
            </div>
            <div class="block-content">
                @if (session('status'))
                    <div class="alert alert-info">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="bg-body-dark p-3 mb-3">
                    <form method="get" action="{{ route('book.purchases') }}">
                        <div class="form-row">
                            <div class="col-md-3 mb-2">
                                <label class="mb-1">Ime i prezime</label>
                                <input type="text" class="form-control" name="name" value="{{ request('name') }}" placeholder="npr. Ivan Horvat">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="mb-1">Email</label>
                                <input type="text" class="form-control" name="email" value="{{ request('email') }}" placeholder="npr. korisnik@mail.com">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1">Datum od</label>
                                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1">Datum do</label>
                                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-search mr-1"></i> Filtriraj</button>
                                <a href="{{ route('book.purchases') }}" class="btn btn-alt-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead>
                        <tr>
                            <th style="width: 170px;">Vrijeme</th>
                            <th>Podaci</th>
                            <th style="width: 180px;">Fotografije</th>
                            <th style="width: 110px;">Detalji</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($purchases as $item)
                            <tr>
                                <td>
                                    <div class="font-w600">{{ optional($item->submitted_at)->format('d.m.Y H:i') ?: '-' }}</div>
                                    <div class="font-size-sm text-muted">{{ $item->submission_id }}</div>
                                </td>
                                <td>
                                    <div><strong>{{ $item->full_name }}</strong></div>
                                    <div class="font-size-sm">Poštanski broj: {{ $item->postal_code }}</div>
                                    <div class="font-size-sm">Email: <a href="mailto:{{ $item->email }}">{{ $item->email }}</a></div>
                                    <div class="font-size-sm">Mobitel: {{ $item->phone }}</div>
                                </td>
                                <td>
                                    @php($photos = collect($item->photos ?? []))
                                    @if($photos->isNotEmpty())
                                        @php($firstPhoto = $photos->first())
                                        <div>
                                            <img src="{{ $firstPhoto['url'] ?? '' }}" alt="{{ $firstPhoto['name'] ?? '' }}" style="width:56px; height:56px; object-fit:cover; border-radius:6px; border:1px solid #e2e2e2;">
                                        </div>
                                        <div class="font-size-sm text-muted mt-2">
                                            Ukupno fotografija: <strong>{{ $photos->count() }}</strong>
                                        </div>
                                    @else
                                        <span class="text-muted">Nema fotografija</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('book.purchases.show', ['purchase' => $item->id]) }}" class="btn btn-sm btn-alt-primary">
                                        Pregled
                                    </a>
                                    <form method="post"
                                          action="{{ route('book.purchases.destroy', ['purchase' => $item->id]) }}"
                                          class="mt-2"
                                          onsubmit="return confirm('Jeste li sigurni da želite obrisati prijavu i sve fotografije?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                                        <button type="submit" class="btn btn-sm btn-alt-danger">Obriši</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Nema prijava za otkup knjiga.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $purchases->links() }}
            </div>
        </div>
    </div>
@endsection
