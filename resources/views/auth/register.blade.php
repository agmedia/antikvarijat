@extends('front.layouts.app')

@section('title', __('front.auth.register_title'))

@section('content')
    <section class="auth-gateway" aria-hidden="true">
        <div class="container text-center">
            <img class="auth-gateway__mark" src="{{ asset('media/img/faviconbiblos.png') }}" alt="">
            <h1 class="auth-gateway__title">Antikvarijat Biblos</h1>
            <p class="auth-gateway__text">{{ __('front.auth.register_title') }}</p>
        </div>
    </section>
@endsection
