<!-- {"title": "Banneri", "description": "Widget za bannere"} -->
<section class=" pt-5 py-3 " style="background-image: url({{ asset('media/img/farmer.png')  }});background-repeat: repeat;padding-top:30px">

    <div class="container">

    <div class="row  mt-2 mt-lg-3 ">
        @foreach ($data as $widget)
            <div class="col-lg-12 col-xl-{{ $widget['width'] }} mb-grid-gutter">
                <div class="d-block d-sm-flex justify-content-between align-items-center  paper-white-bck  rounded-3">
                        <div class="pt-5 py-sm-4 px-4 ps-md-4 pe-md-0 text-center text-sm-start">
                                <h3 class=" font-title">{{ $widget['title'] }}</h3>
                                <p class=" pb-2">{{ $widget['subtitle'] }}</p>
                            <a class="btn btn-primary btn-sm btn-shadow mt-0" href="{{ url($widget['url']) }}">Opširnije <i class="ci-arrow-right ms-2 me-n1"></i></a>
                        </div>
                       <img class="d-block mx-auto mx-sm-0 rounded-end" src="{{ $widget['image'] }}" width="220" height="263" alt="{{ $widget['title'] }}">
                </div>
            </div>
        @endforeach
    </div>
    </div>
</section>
