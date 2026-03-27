@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Newsletter prijave</h1>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Pretplatnici</h3>
                <div class="block-options">
                    <form method="post" action="{{ route('newsletter.subscribers.sync') }}" class="d-inline-block mr-2">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            Import novih u Mailchimp ({{ $pendingSyncCount ?? 0 }})
                        </button>
                    </form>
                    <form method="post" action="{{ route('newsletter.products.sync') }}" class="d-inline-block mr-2">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            Sync artikala u Mailchimp
                        </button>
                    </form>
                    <form method="post" action="{{ route('newsletter.orders.sync') }}" class="d-inline-block mr-2">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            Sync ordera u Mailchimp
                        </button>
                    </form>
                    <form method="post" action="{{ route('newsletter.caches.clear') }}" class="d-inline-block mr-2">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            Očisti app cache
                        </button>
                    </form>
                    <a class="btn btn-primary" href="{{ route('newsletter.subscribers') }}">
                        Očisti filter
                    </a>
                </div>
            </div>

            <div class="block-content">
                @if (session('status'))
                    <div class="alert alert-info">
                        <pre class="mb-0" style="white-space: pre-wrap;">{{ session('status') }}</pre>
                    </div>
                @endif

                <div class="bg-body-dark p-3 mb-3">
                    <form method="get" action="{{ route('newsletter.subscribers') }}">
                        <div class="form-group row">
                            <div class="col-md-9">
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control"
                                           name="search"
                                           value="{{ request()->input('search') }}"
                                           placeholder="Pretraži email, korisnika ili order ID">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>User ID</th>
                            <th>Ime i prezime</th>
                            <th>Order ID</th>
                            <th>Izvor</th>
                            <th>GDPR</th>
                            <th>Mailchimp</th>
                            <th>Prijavljen</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($subscribers as $subscriber)
                            @php
                                $fullName = trim((optional(optional($subscriber->user)->details)->fname ?? '') . ' ' . (optional(optional($subscriber->user)->details)->lname ?? ''));
                            @endphp
                            <tr>
                                <td>{{ $subscriber->id }}</td>
                                <td>{{ $subscriber->email }}</td>
                                <td>{{ $subscriber->user_id ?: '-' }}</td>
                                <td>
                                    @if ($subscriber->user_id)
                                        {{ $fullName !== '' ? $fullName : (optional($subscriber->user)->name ?? '-') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $subscriber->order_id ?: '-' }}</td>
                                <td>{{ $subscriber->source }}</td>
                                <td>{{ $subscriber->gdpr ? 'DA' : 'NE' }}</td>
                                <td>
                                    @if ($subscriber->mailchimp_synced_at)
                                        <span class="badge badge-success">Syncano {{ $subscriber->mailchimp_synced_at->format('d.m.Y H:i') }}</span>
                                    @elseif ($subscriber->mailchimp_last_error)
                                        <span class="badge badge-danger" title="{{ $subscriber->mailchimp_last_error }}">Greška</span>
                                    @else
                                        <span class="badge badge-secondary">Novo</span>
                                    @endif
                                </td>
                                <td>{{ optional($subscriber->subscribed_at)->format('d.m.Y H:i') ?: optional($subscriber->created_at)->format('d.m.Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">Nema newsletter prijava.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $subscribers->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
@endsection
