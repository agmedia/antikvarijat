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
                    <a class="btn btn-primary" href="{{ route('newsletter.subscribers') }}">
                        Očisti filter
                    </a>
                </div>
            </div>

            <div class="block-content">
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
                                <td>{{ optional($subscriber->subscribed_at)->format('d.m.Y H:i') ?: optional($subscriber->created_at)->format('d.m.Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Nema newsletter prijava.</td>
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
