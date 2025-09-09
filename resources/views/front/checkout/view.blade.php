
@extends('front.layouts.app')

@section('content')

<div class="page-title-overlap bg-accent pt-4"  style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat">
    <div class="container d-lg-flex justify-content-between py-2 py-lg-3">
        <div class="order-lg-2 mb-3 mb-lg-0 pt-lg-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-dark flex-lg-nowrap justify-content-center justify-content-lg-start">
                    <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>Naslovnica</a></li>

                    <li class="breadcrumb-item text-nowrap active" aria-current="page">Potvrdite narudžbu</li>
                </ol>
            </nav>
        </div>
        <div class="order-lg-1 pe-lg-4 text-center text-lg-start">
            <h1 class="h3 text-dark mb-0">Način plaćanja</h1>
        </div>
    </div>
</div>

<div class="container pb-5 mb-2 mb-md-4">
    <div class="row">
        <section class="col-lg-8">

            <div class="steps steps-dark pt-2 pb-3 mb-5">
                <a class="step-item active" href="{{ route('kosarica') }}">
                    <div class="step-progress"><span class="step-count">1</span></div>
                    <div class="step-label"><i class="ci-cart"></i>Košarica</div>
                </a>
                <a class="step-item active" href="{{ route('naplata', ['step' => 'podaci']) }}">
                    <div class="step-progress"><span class="step-count">2</span></div>
                    <div class="step-label"><i class="ci-user-circle"></i>Podaci</div>
                </a>
                <a class="step-item active" href="{{ route('naplata', ['step' => 'dostava']) }}">
                    <div class="step-progress"><span class="step-count">3</span></div>
                    <div class="step-label"><i class="ci-package"></i>Dostava</div>
                </a>
                <a class="step-item active" href="{{ route('naplata', ['step' => 'placanje']) }}">
                    <div class="step-progress"><span class="step-count">4</span></div>
                    <div class="step-label"><i class="ci-card"></i>Plaćanje</div>
                </a>
                <a class="step-item current active" href="{{ route('pregled') }}">
                    <div class="step-progress"><span class="step-count">5</span></div>
                    <div class="step-label"><i class="ci-check-circle"></i>Pregledaj</div>
                </a>
            </div>
            <div class="bg-white rounded-3 shadow-lg p-4">
            <h2 class="h6 pt-1 pb-3 mb-3">Pregled košarice</h2>
            <cart-view continueurl="{{ route('index') }}" checkouturl="{{ route('naplata') }}" buttons="false"></cart-view>

            <div class="bg-secondary rounded-3 px-4 pt-4 pb-2">
                <div class="row">
                    <div class="col-sm-6">
                        <h4 class="h6">Platitelj:</h4>
                        <ul class="list-unstyled fs-sm">
                            @if (auth()->guest())
                                <li><span class="">Korisnik:&nbsp;</span>{{ $data['address']['fname'] }} {{ $data['address']['lname'] }}</li>
                                <li><span class="">Adresa:&nbsp;</span>{{ $data['address']['address'] }}, {{ $data['address']['zip'] }} {{ $data['address']['city'] }}, {{ $data['address']['state'] }}</li>
                                <li><span class="">Email:&nbsp;</span>{{ $data['address']['email'] }}</li>
                            @else
                                <li><span class="">Korisnik:&nbsp;</span>{{ auth()->user()->details->fname }} {{ auth()->user()->details->lname }}</li>
                                <li><span class="">Adresa:&nbsp;</span>{{ auth()->user()->details->address }}, {{ auth()->user()->details->zip }} {{ auth()->user()->details->city }}, {{ $data['address']['state'] }}</li>
                                <li><span class="">Email:&nbsp;</span>{{ auth()->user()->email }}</li>
                            @endif
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <h4 class="h6">Dostaviti na:</h4>
                        <ul class="list-unstyled fs-sm">
                            <li><span class="">Korisnik:&nbsp;</span>{{ $data['address']['fname'] }} {{ $data['address']['lname'] }}</li>
                            <li><span class="">Adresa:&nbsp;</span>{{ $data['address']['address'] }}, {{ $data['address']['zip'] }} {{ $data['address']['city'] }}, {{ $data['address']['state'] }}</li>
                            <li><span class="">Email:&nbsp;</span>{{ $data['address']['email'] }}</li>
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <h4 class="h6">Način dostave:</h4>
                        <ul class="list-unstyled fs-sm">
                            <li>
                                <span class="text-muted">{{ $data['shipping']->title }} </span><br>
                                {{ $data['shipping']->data->description ?: $data['shipping']->data->short_description }}
                            </li>
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <h4 class="h6">Način plaćanja:</h4>
                        <ul class="list-unstyled fs-sm">
                            <li>
                                <span class="text-muted">{{ $data['payment']->title }} </span><br>
                                {{ $data['payment']->data->description ?: $data['payment']->data->short_description }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="d-none d-lg-flex pt-0 mt-3">
                {!! $data['payment_form'] !!}
            </div>
            </div>
        </section>

        <aside class="col-lg-4 pt-4 pt-lg-0 mb-3 ps-xl-5 d-block">
            <cart-view-aside route="pregled" continueurl="{{ route('index') }}" checkouturl="/"></cart-view-aside>
        </aside>
    </div>

    <div class="row d-lg-none">
        <div class="col-lg-8">
            {!! $data['payment_form'] !!}
        </div>
    </div>
</div>
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Opći uvjeti korištenja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @foreach ($uvjeti_kupnje as $uvjet)
                    {!! $uvjet->description !!}

                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>

            </div>
        </div>
    </div>
</div>

@endsection

@push('js_after')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const submitButton = document.querySelector('form[name="pay"] button[type="submit"]');
            const form = document.querySelector('form[name="pay"]');

            submitButton.addEventListener('click', function(event) {
                event.preventDefault(); // Zaustavi automatski submit forme

                // PUTANJA NA BACKEND KOJA PROVJERAVA STANJE ARTIKALA
                fetch('api/v2/cart/provjeri-stanje-artikala', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        order_id: '{{ $data['id'] }}'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        // Sve je u redu, šaljemo formu
                        form.submit();
                    } else {
                        // Prikaz greške korisniku
                        alert('Nažalost, neki proizvodi više nisu dostupni ili je količina manja od naručene.');
                    }
                })
                .catch(error => {
                    console.error('Greška:', error);
                    alert('Došlo je do greške prilikom provjere zaliha. Pokušajte ponovo.');
                });
            });
        });
    </script>

@endpush