@if(session('success'))
    <!-- Success alert -->
    <div class="alert alert-success d-flex" role="alert">
        <div class="alert-icon">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        </div>
        <div>Uspjeh..! {{ session('success') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger d-flex" role="alert">
        <div class="alert-icon">
            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
        </div>
        <div>Greška..! {{ session('error') }}</div>
    </div>

@endif
@if(session('warning'))
    <div class="alert alert-warning d-flex" role="alert">
        <div class="alert-icon">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        </div>
        <div>Upozorenje..! {{ session('warning') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
