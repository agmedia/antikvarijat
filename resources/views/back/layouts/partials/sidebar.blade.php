<!-- Sidebar -->
<!--
    Sidebar Mini Mode - Display Helper classes

    Adding 'smini-hide' class to an element will make it invisible (opacity: 0) when the sidebar is in mini mode
    Adding 'smini-show' class to an element will make it visible (opacity: 1) when the sidebar is in mini mode
        If you would like to disable the transition animation, make sure to also add the 'no-transition' class to your element

    Adding 'smini-hidden' to an element will hide it when the sidebar is in mini mode
    Adding 'smini-visible' to an element will show it (display: inline-block) only when the sidebar is in mini mode
    Adding 'smini-visible-block' to an element will show it (display: block) only when the sidebar is in mini mode
-->
<nav id="sidebar" aria-label="Main Navigation">
    <!-- Side Header -->
    <div class="bg-header-dark">
        <div class="content-header bg-white-10">
            <!-- Logo -->
            <a class="admin-brand" href="/" aria-label="Antikvarijat Biblos">
                <img class="admin-brand-logo" src="{{ asset('media/img/logobijeli.svg') }}" alt="Antikvarijat Biblos">
            </a>
            <!-- END Logo -->

            <!-- Options -->
            <div>
                <!-- Toggle Sidebar Style -->
                <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
                <!-- Class Toggle, functionality initialized in Helpers.coreToggleClass() -->
            <!--    <a class="js-class-toggle text-white-75" data-target="#sidebar-style-toggler" data-class="fa-toggle-off fa-toggle-on" onclick="Dashmix.layout('sidebar_style_toggle');Dashmix.layout('header_style_toggle');" href="javascript:void(0)">
                    <i class="fa fa-toggle-off" id="sidebar-style-toggler"></i>
                </a>-->
                <!-- END Toggle Sidebar Style -->

                <!-- Close Sidebar, Visible only on mobile screens -->
                <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
                <a class="d-lg-none text-white ml-2" data-toggle="layout" data-action="sidebar_close" href="javascript:void(0)">
                    <i class="fa-duotone fa-circle-xmark"></i>
                </a>
                <!-- END Close Sidebar -->
            </div>
            <!-- END Options -->
        </div>
    </div>
    <!-- END Side Header -->

    <!-- Sidebar Scrolling -->
    <div class="js-sidebar-scroll">
        <!-- Side Navigation -->
        <div class="content-side content-side-full">
            <ul class="nav-main">
                {{--<li class="nav-main-heading">Katalog</li>--}}

                <li class="nav-main-item">
                    <a class="nav-main-link{{ request()->routeIs(['dashboard', 'statistics', 'statistics.*']) ? ' active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="nav-main-link-icon fa-duotone fa-grid-2"></i>
                        <span class="nav-main-link-name">Dashboard</span>
                        {{--<span class="nav-main-link-badge badge badge-pill badge-success">5</span>--}}
                    </a>
                </li>
                {{--<li class="nav-main-heading">Various</li>--}}
                <li class="nav-main-item{{ request()->is(['admin/catalog/*']) ? ' open' : '' }}">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="true" href="#">
                        <i class="nav-main-link-icon fa-duotone fa-books"></i>
                        <span class="nav-main-link-name">Katalog</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['categories', 'category.*']) ? ' active' : '' }}" href="{{ route('categories') }}">
                                <span class="nav-main-link-name">Kategorije</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['products', 'products.*']) ? ' active' : '' }}" href="{{ route('products') }}">
                                <span class="nav-main-link-name">Artikli</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['publishers', 'publishers.*']) ? ' active' : '' }}" href="{{ route('publishers') }}">
                                <span class="nav-main-link-name">Izdavači</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['authors', 'authors.*']) ? ' active' : '' }}" href="{{ route('authors') }}">
                                <span class="nav-main-link-name">Autori</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-main-item">
                    <a class="nav-main-link{{ request()->routeIs(['orders', 'orders.*']) ? ' active' : '' }}" href="{{ route('orders') }}">
                        <i class="nav-main-link-icon fa-duotone fa-cart-shopping"></i>
                        <span class="nav-main-link-name">Narudžbe</span>
                    </a>
                </li>

                @if(auth()->check() && ! auth()->user()->isAn('editor'))
                    <li class="nav-main-item">
                        <a class="nav-main-link{{ request()->routeIs(['gift-vouchers.*']) ? ' active' : '' }}" href="{{ route('gift-vouchers.index') }}">
                            <i class="nav-main-link-icon fa-duotone fa-gift-card"></i>
                            <span class="nav-main-link-name">Poklon bonovi</span>
                        </a>
                    </li>
                @endif

                <li class="nav-main-item">
                    <a class="nav-main-link{{ request()->routeIs(['contract-withdrawals.*']) ? ' active' : '' }}" href="{{ route('contract-withdrawals.index') }}">
                        <i class="nav-main-link-icon fa-duotone fa-file-signature"></i>
                        <span class="nav-main-link-name">Raskidi ugovora</span>
                    </a>
                </li>

                <li class="nav-main-item">
                    <a class="nav-main-link{{ request()->routeIs(['product-reviews.*']) ? ' active' : '' }}" href="{{ route('product-reviews.index') }}">
                        <i class="nav-main-link-icon fa-solid fa-comments"></i>
                        <span class="nav-main-link-name">Recenzije artikala</span>
                    </a>
                </li>

                @if(\App\Support\ProductReviewBackfillAccess::allows(auth()->user()))
                    <li class="nav-main-item">
                        <a class="nav-main-link{{ request()->routeIs(['product-review-backfills.*']) ? ' active' : '' }}" href="{{ route('product-review-backfills.index') }}">
                            <i class="nav-main-link-icon fa-solid fa-paper-plane"></i>
                            <span class="nav-main-link-name">Pozivi za recenzije</span>
                        </a>
                    </li>
                @endif

                <li class="nav-main-item{{ (request()->is(['admin/marketing/*']) || request()->routeIs(['wishlists', 'wishlists.*'])) ? ' open' : '' }}">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="{{ (request()->is(['admin/marketing/*']) || request()->routeIs(['wishlists', 'wishlists.*'])) ? 'true' : 'false' }}" href="#">
                        <i class="nav-main-link-icon fa-duotone fa-megaphone"></i>
                        <span class="nav-main-link-name">Marketing</span>
                    </a>
                    <ul class="nav-main-submenu">
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['actions', 'actions.*']) ? ' active' : '' }}" href="{{ route('actions') }}">
                                <span class="nav-main-link-name">Akcije</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['blogs', 'blogs.*']) ? ' active' : '' }}" href="{{ route('blogs') }}">
                                <span class="nav-main-link-name">Blog</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['wishlists', 'wishlists.*']) ? ' active' : '' }}" href="{{ route('wishlists') }}">
                                <span class="nav-main-link-name">Wishlist</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['book.purchases', 'book.purchases.*']) ? ' active' : '' }}" href="{{ route('book.purchases') }}">
                                <span class="nav-main-link-name">Otkup knjiga</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['newsletter.subscribers']) ? ' active' : '' }}" href="{{ route('newsletter.subscribers') }}">
                                <span class="nav-main-link-name">Newsletter</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['vialibri.index', 'vialibri.*']) ? ' active' : '' }}" href="{{ route('vialibri.index') }}">
                                <span class="nav-main-link-name">ViaLibri</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-main-item">
                    <a class="nav-main-link{{ request()->routeIs(['users', 'users.*']) ? ' active' : '' }}" href="{{ route('users') }}">
                        <i class="nav-main-link-icon fa-duotone fa-user-group"></i>
                        <span class="nav-main-link-name">Korisnici</span>
                    </a>
                </li>

                <li class="nav-main-heading">Aplikacija</li>

                <li class="nav-main-item">
                    <a class="nav-main-link{{ request()->routeIs(['profile', 'profile.*']) ? ' active' : '' }}" href="{{ route('profile.show') }}">
                        <i class="nav-main-link-icon fa-duotone fa-user"></i>
                        <span class="nav-main-link-name">Moj Profil</span>
                    </a>
                </li>

                <li class="nav-main-item">
                    <a class="nav-main-link{{ request()->routeIs(['widgets', 'widgets.*']) ? ' active' : '' }}" href="{{ route('widgets') }}">
                        <i class="nav-main-link-icon fa-duotone fa-puzzle-piece"></i>
                        <span class="nav-main-link-name">Widgets</span>
                    </a>
                </li>

                <li class="nav-main-item{{ request()->is(['admin/settings/*']) ? ' open' : '' }}">
                    <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="true" href="#">
                        <i class="nav-main-link-icon fa-duotone fa-gear"></i>
                        <span class="nav-main-link-name">Postavke</span>
                    </a>
                    <ul class="nav-main-submenu">
                       <li class="nav-main-item">
                           <a class="nav-main-link{{ request()->routeIs(['api', 'api.*']) ? ' active' : '' }}" href="{{ route('api.index') }}">
                               <span class="nav-main-link-name">API</span>
                           </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['google.api.*']) ? ' active' : '' }}" href="{{ route('google.api.index') }}">
                                <span class="nav-main-link-name">Google API</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['google-login.*']) ? ' active' : '' }}" href="{{ route('google-login.edit') }}">
                                <span class="nav-main-link-name">Google prijava</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['pages', 'pages.*']) ? ' active' : '' }}" href="{{ route('pages') }}">
                                <span class="nav-main-link-name">Info Stranice</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['faqs', 'faqs.*']) ? ' active' : '' }}" href="{{ route('faqs') }}">
                                <span class="nav-main-link-name">FAQ</span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['contract-withdrawal-settings.*']) ? ' active' : '' }}" href="{{ route('contract-withdrawal-settings.edit') }}">
                                <span class="nav-main-link-name">Jednostrani raskid</span>
                            </a>
                        </li>
                        <li class="nav-main-item{{ request()->is(['admin/settings/application/*']) ? ' open' : '' }}">
                            <a class="nav-main-link nav-main-link-submenu" data-toggle="submenu" aria-haspopup="true" aria-expanded="false" href="#">
                                <span class="nav-main-link-name">Postavke Aplikacije</span>
                            </a>

                            <ul class="nav-main-submenu">
                                <li class="nav-main-item">
                                    <a class="nav-main-link{{ request()->routeIs(['geozones', 'geozones.*']) ? ' active' : '' }}" href="{{ route('geozones') }}">
                                        <span class="nav-main-link-name">Geo Zone</span>
                                    </a>
                                </li>
                                <li class="nav-main-item">
                                    <a class="nav-main-link{{ request()->routeIs(['order.statuses']) ? ' active' : '' }}" href="{{ route('order.statuses') }}">
                                        <span class="nav-main-link-name">Statusi Narudžbi</span>
                                    </a>
                                </li>
                                <li class="nav-main-item">
                                    <a class="nav-main-link{{ request()->routeIs(['taxes']) ? ' active' : '' }}" href="{{ route('taxes') }}">
                                        <span class="nav-main-link-name">Porezi</span>
                                    </a>
                                </li>
                                <li class="nav-main-item">
                                    <a class="nav-main-link{{ request()->routeIs(['currencies']) ? ' active' : '' }}" href="{{ route('currencies') }}">
                                        <span class="nav-main-link-name">Valute</span>
                                    </a>
                                </li>
                                <li class="nav-main-item">
                                    <a class="nav-main-link{{ request()->routeIs(['shippings']) ? ' active' : '' }}" href="{{ route('shippings') }}">
                                        <span class="nav-main-link-name">Načini dostave</span>
                                    </a>
                                </li>
                                <li class="nav-main-item">
                                    <a class="nav-main-link{{ request()->routeIs(['payments']) ? ' active' : '' }}" href="{{ route('payments') }}">
                                        <span class="nav-main-link-name">Načini plaćanja</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link{{ request()->routeIs(['history', 'history.*']) ? ' active' : '' }}" href="{{ route('history') }}">
                                <span class="nav-main-link-name">History log</span>
                            </a>
                        </li>

                    </ul>
                </li>

            </ul>
        </div>
        <!-- END Side Navigation -->
    </div>
    <!-- END Sidebar Scrolling -->
</nav>
