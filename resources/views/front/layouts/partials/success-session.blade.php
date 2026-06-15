@if(session('success'))
    <!-- Success alert -->
    <div class="alert alert-success d-flex" role="alert">
        <div class="alert-icon">
            <i class="ci-check-circle"></i>
        </div>
        <div>{{ __('front.messages.success') }} {{ session('success') }}</div>
    </div>
@endif
