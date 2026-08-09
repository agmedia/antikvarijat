@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-solid fa-comments" aria-hidden="true"></i> Katalog</div>
                    <h1 class="admin-page-title">Recenzije artikala</h1>
                    <p class="admin-page-description">Odobrite stvarne komentare kupaca prije prikaza na artiklu i u strukturiranim podacima.</p>
                </div>
                @if(\App\Support\ProductReviewBackfillAccess::allows(auth()->user()))
                    <div class="admin-page-actions">
                        <a class="btn btn-primary" href="{{ route('product-review-backfills.index') }}">
                            <i class="fa-solid fa-paper-plane mr-1"></i> Pošalji stare pozive
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div>
                    <h2 class="block-title mb-1">Moderacija</h2>
                    <span class="admin-count">{{ number_format($reviews->total(), 0, ',', '.') }} recenzija</span>
                </div>
            </div>

            <div class="block-content bg-body-dark">
                <form method="GET" action="{{ route('product-reviews.index') }}">
                    <div class="form-row align-items-end">
                        <div class="col-md-7 mb-2">
                            <label class="admin-filter-label" for="review-search">Pretraživanje</label>
                            <input class="form-control" id="review-search" type="search" name="search" value="{{ $search }}" placeholder="Artikl, šifra, kupac, e-mail ili tekst...">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="admin-filter-label" for="review-status">Status</label>
                            <select class="form-control" id="review-status" name="status">
                                <option value="">Svi statusi</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @if($selectedStatus === $value) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button class="btn btn-primary btn-block" type="submit"><i class="fa-duotone fa-magnifying-glass mr-1"></i> Pretraži</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter admin-data-table">
                        <thead>
                        <tr>
                            <th>Artikl</th>
                            <th>Ocjena i komentar</th>
                            <th>Kupac</th>
                            <th>Datum</th>
                            <th style="width: 230px;">Moderacija</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($reviews as $review)
                            @php
                                $badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$review->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td data-label="Artikl">
                                    @if ($review->product)
                                        <a class="font-w600" href="{{ route('products.edit', $review->product) }}">{{ $review->product->name }}</a>
                                        <div class="small text-muted">Šifra {{ $review->product->sku }}</div>
                                    @else
                                        <span class="text-muted">Artikl #{{ $review->product_id }} nije dostupan</span>
                                    @endif
                                </td>
                                <td data-label="Komentar" style="min-width: 320px;">
                                    <div class="text-warning mb-1" aria-label="Ocjena {{ $review->rating }} od 5">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <i class="fa{{ $star <= $review->rating ? 's' : 'r' }} fa-star"></i>
                                        @endfor
                                    </div>
                                    @if ($review->title)<strong>{{ $review->title }}</strong>@endif
                                    <div style="white-space: pre-line;">{{ $review->body }}</div>
                                </td>
                                <td data-label="Kupac">
                                    <div class="font-w600">{{ $review->reviewer_name }}</div>
                                    @if ($review->reviewer_email)<a href="mailto:{{ $review->reviewer_email }}">{{ $review->reviewer_email }}</a>@endif
                                    @if ($review->is_verified_purchase)
                                        <div><span class="badge badge-success mt-1">Potvrđena kupnja</span></div>
                                    @endif
                                    @if ($review->order)<div class="small"><a href="{{ route('orders.show', $review->order) }}">Narudžba #{{ $review->order->id }}</a></div>@endif
                                </td>
                                <td class="text-nowrap" data-label="Datum">{{ $review->created_at->format('d.m.Y. H:i') }}</td>
                                <td data-label="Moderacija">
                                    <span class="badge badge-{{ $badge }} mb-2">{{ $statuses[$review->status] ?? $review->status }}</span>
                                    <form method="POST" action="{{ route('product-reviews.update', $review) }}" class="d-flex">
                                        @csrf
                                        @method('PATCH')
                                        <select class="form-control form-control-sm mr-2" name="status" aria-label="Status recenzije">
                                            @foreach ($statuses as $value => $label)
                                                <option value="{{ $value }}" @if($review->status === $value) selected @endif>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit">Spremi</button>
                                    </form>
                                    @if ($review->approver)
                                        <div class="small text-muted mt-2">{{ $review->approver->name }} · {{ optional($review->approved_at)->format('d.m.Y. H:i') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">Nema pronađenih recenzija.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $reviews->links() }}
            </div>
        </div>
    </div>
@endsection
