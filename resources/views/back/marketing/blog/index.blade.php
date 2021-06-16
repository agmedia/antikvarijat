@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Blog</h1>
                <a class="btn btn-hero-success my-2" href="{{ route('blogs.create') }}">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> Novi post</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="content content-full">
    @include('back.layouts.partials.session')


        <!-- Posts -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Objave (150)</h3>
            </div>
            <div class="block-content">
                <!-- Search Posts -->
                <form class="push" action="be_pages_blog_post_manage.html" method="POST">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Pretraži..">
                        <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="fa fa-fw fa-search"></i>
                                        </span>
                        </div>
                    </div>
                </form>
                <!-- END Search Posts -->

                <!-- Posts Table -->
                <table class="table table-striped table-borderless table-vcenter">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 60px;">Slika</th>
                        <th style="width: 33%;">Naziv</th>
                        <th >Autor</th>
                        <th >Kreirano</th>
                        <th >Objavljeno</th>
                        <th style="width: 100px;" class="text-center">Uredi</th>
                    </tr>
                    </thead>
                    <tbody>

                    <tr>
                        <td>
                            <a href="{{ route('blogs.create') }}">
                            <img src="{{ asset('media/img/knjiga.jpg') }}" height="80px"/>
                            </a>
                        </td>
                        <td>
                            <i class="fa fa-eye text-success mr-1"></i>
                            <a href="{{ route('blogs.create') }}">
                                Can you travel &amp; work efficiently?
                            </a>
                        </td>
                        <td>
                            <a href="be_pages_generic_profile.html">Andrea Gardner</a>
                        </td>
                        <td>
                            10.05.2021. u 20:12
                        </td>
                        <td>
                            10.05.2021. u 20:12
                        </td>
                        <td class="text-right font-size-sm">
                            <a class="btn btn-sm btn-alt-secondary" href="">
                                <i class="fa fa-fw fa-eye"></i>
                            </a>
                            <a class="btn btn-sm btn-alt-secondary" href="{{ route('blogs.create') }}">
                                <i class="fa fa-fw fa-pencil-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <a href="{{ route('blogs.create') }}">
                            <img src="{{ asset('media/img/knjiga.jpg') }}" height="80px"/>
                            </a>
                        </td>
                        <td>
                            <i class="fa fa-eye text-danger mr-1"></i>
                            <a href="{{ route('blogs.create') }}">
                                The best places to work from
                            </a>
                        </td>
                        <td>
                            <a href="be_pages_generic_profile.html">Susan Day</a>
                        </td>
                        <td>
                            10.05.2021. u 20:12
                        </td>
                        <td>
                            10.05.2021. u 20:12
                        </td>
                        <td class="text-right font-size-sm">
                            <a class="btn btn-sm btn-alt-secondary" href="">
                                <i class="fa fa-fw fa-eye"></i>
                            </a>
                            <a class="btn btn-sm btn-alt-secondary" href="{{ route('blogs.create') }}">
                                <i class="fa fa-fw fa-pencil-alt"></i>
                            </a>
                        </td>
                    </tr>


                    </tbody>
                </table>
                <!-- END Posts Table -->

                <!-- Posts Pagincation -->
                <nav aria-label="Posts Navigation">
                    <ul class="pagination justify-content-end">
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0)" tabindex="-1" aria-label="Previous">
                                            <span aria-hidden="true">
                                                <i class="fa fa-angle-double-left"></i>
                                            </span>
                                <span class="sr-only">Previous</span>
                            </a>
                        </li>
                        <li class="page-item active">
                            <a class="page-link" href="javascript:void(0)">1</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0)">2</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0)">3</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0)">4</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0)" aria-label="Next">
                                            <span aria-hidden="true">
                                                <i class="fa fa-angle-double-right"></i>
                                            </span>
                                <span class="sr-only">Next</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- END Posts Pagincation -->
            </div>
        </div>
        <!-- END Posts -->
    </div>
    <!-- END Page Content -->

@endsection

@push('js_after')

@endpush