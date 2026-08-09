@extends('back.layouts.backend')

@section('content')
    <div class="admin-page-hero">
        <div class="content content-full">
            <div class="admin-page-heading">
                <div>
                    <div class="admin-page-kicker"><i class="fa-duotone fa-circle-question"></i> Sadržaj</div>
                    <h1 class="admin-page-title">Česta pitanja</h1>
                    <p class="admin-page-description">Uredite odgovore koji kupcima pomažu prije kupnje i smanjuju broj upita podršci.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn btn-primary" href="{{ route('faqs.create') }}">
                        <i class="fa-duotone fa-plus mr-1"></i> Novo pitanje
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="d-flex align-items-center min-width-0">
                    <span class="admin-section-icon mr-3"><i class="fa-duotone fa-messages-question"></i></span>
                    <div>
                        <h2 class="block-title mb-1">Objavljena pitanja</h2>
                        <span class="admin-count">{{ number_format($faqs->total(), 0, ',', '.') }} pitanja</span>
                    </div>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-striped table-borderless table-vcenter admin-data-table">
                        <thead><tr><th>Pitanje</th><th class="text-right">Radnje</th></tr></thead>
                        <tbody>
                        @forelse ($faqs as $faq)
                            <tr>
                                <td data-label="Pitanje"><a class="font-w600" href="{{ route('faqs.edit', ['faq' => $faq]) }}">{{ $faq->title }}</a></td>
                                <td class="text-right" data-label="Radnje">
                                    <a class="btn btn-sm btn-alt-secondary" href="{{ route('faqs.edit', ['faq' => $faq]) }}" title="Uredi" aria-label="Uredi pitanje {{ $faq->title }}"><i class="fa-duotone fa-pen-to-square"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-5" colspan="2">Nema čestih pitanja.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $faqs->links() }}
            </div>
        </div>
    </div>
@endsection
