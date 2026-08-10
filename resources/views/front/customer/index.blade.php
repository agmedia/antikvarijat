@extends('front.layouts.app')

@section('content')
    @include('front.customer.layouts.header')

    <section class="account-page container pb-5 mb-2 mb-md-4">
        <div class="row g-4">
            @include('front.customer.layouts.sidebar')

            <section class="col-lg-8 col-xl-9">
                <div class="account-card account-content-card">
                    <div class="account-content-header">
                        <div class="account-content-heading">
                            <span class="account-content-icon"><i class="fa-duotone fa-user" aria-hidden="true"></i></span>
                            <div>
                                <h2 class="account-content-title">{{ __('front.account.my_data') }}</h2>
                                <p class="account-content-subtitle">{{ __('front.account.edit_hint') }}</p>
                            </div>
                        </div>
                    </div>

                    @include('front.layouts.partials.session')

                    <form action="{{ \App\Helpers\LocaleHelper::route('moj-racun.snimi', ['user' => $user]) }}" method="POST">
                        @csrf
                        {{ method_field('PATCH') }}

                        <h3 class="account-section-title"><i class="fa-duotone fa-circle-user" aria-hidden="true"></i>{{ __('front.account.basic_data') }}</h3>
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-fname">{{ __('front.checkout.first_name') }}</label>
                                <input class="form-control @error('fname') is-invalid @enderror" id="account-fname" type="text" name="fname" value="{{ old('fname', $user->details->fname) }}">
                                @error('fname') <div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('front.checkout.first_name')]) }}</div> @enderror
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-lname">{{ __('front.checkout.last_name') }}</label>
                                <input class="form-control @error('lname') is-invalid @enderror" id="account-lname" type="text" name="lname" value="{{ old('lname', $user->details->lname) }}">
                                @error('lname') <div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('front.checkout.last_name')]) }}</div> @enderror
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-email">{{ __('front.checkout.email_address') }}</label>
                                <input class="form-control" id="account-email" type="email" readonly name="email" value="{{ $user->email }}">
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-phone">{{ __('front.checkout.phone') }}</label>
                                <input class="form-control" id="account-phone" type="text" name="phone" value="{{ old('phone', $user->details->phone) }}">
                            </div>
                        </div>

                        <h3 class="account-section-title mt-3"><i class="fa-duotone fa-location-dot" aria-hidden="true"></i>{{ __('front.account.shipping_address') }}</h3>
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-address">{{ __('front.checkout.address') }}</label>
                                <input class="form-control @error('address') is-invalid @enderror" id="account-address" type="text" name="address" value="{{ old('address', $user->details->address) }}">
                                @error('address') <div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('front.checkout.address')]) }}</div> @enderror
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-city">{{ __('front.checkout.city') }}</label>
                                <input class="form-control @error('city') is-invalid @enderror" id="account-city" type="text" name="city" value="{{ old('city', $user->details->city) }}">
                                @error('city') <div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('front.checkout.city')]) }}</div> @enderror
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-zip">{{ __('front.checkout.zip') }}</label>
                                <input class="form-control @error('zip') is-invalid @enderror" id="account-zip" type="text" name="zip" value="{{ old('zip', $user->details->zip) }}">
                                @error('zip') <div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('front.checkout.zip')]) }}</div> @enderror
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-country">{{ __('front.checkout.country') }}</label>
                                <select class="form-select @error('state') is-invalid @enderror" id="account-country" name="state">
                                    <option value=""></option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country['name'] }}" @if($country['name'] === old('state', $user->details->state)) selected @endif>{{ $country['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('state') <div class="invalid-feedback">{{ __('validation.required', ['attribute' => __('front.checkout.country')]) }}</div> @enderror
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-company">{{ __('front.checkout.company') }}</label>
                                <input class="form-control" id="account-company" type="text" name="company" value="{{ old('company', $user->details->company) }}">
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="form-label" for="account-oib">{{ __('front.checkout.oib') }}</label>
                                <input class="form-control" id="account-oib" type="text" name="oib" value="{{ old('oib', $user->details->oib) }}">
                            </div>
                        </div>

                        <div class="account-form-actions">
                            <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>{{ __('front.general.save') }}</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </section>
@endsection
