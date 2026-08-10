<section class="directory-hero">
    <div class="container">
        <div class="directory-hero__content">
            <div>
                <h1 class="directory-hero__title">{{ $directoryTitle }}</h1>
                <p class="directory-hero__subtitle">{{ $directorySubtitle }}</p>
            </div>
        </div>
    </div>
</section>

<main class="container directory-page">
    <nav class="directory-alphabet" aria-label="{{ __('front.directories.select_letter') }}">
        <p class="directory-alphabet__label">{{ __('front.directories.select_letter') }}</p>

        <div class="directory-alphabet__grid">
            @foreach ($letters as $item)
                @php
                    $isCurrent = $item['value'] === $letter;
                    $isAvailable = (bool) $item['active'];
                @endphp

                @if ($isCurrent)
                    <span class="directory-letter is-current" aria-current="page">{{ $item['value'] }}</span>
                @elseif ($isAvailable)
                    <a
                        class="directory-letter"
                        href="{{ \App\Helpers\LocaleHelper::route($directoryRoute, [$directoryRouteParameter => null, 'letter' => $item['value']]) }}"
                        aria-label="{{ __('front.directories.show_letter', ['letter' => $item['value']]) }}"
                    >{{ $item['value'] }}</a>
                @else
                    <span class="directory-letter is-disabled" aria-disabled="true">{{ $item['value'] }}</span>
                @endif
            @endforeach
        </div>
    </nav>

    <section class="directory-results" aria-labelledby="directory-results-title">
        <header class="directory-results__header">
            <h2 class="directory-results__title" id="directory-results-title">{{ __('front.directories.results_for_letter', ['letter' => $letter]) }}</h2>
            <span class="directory-results__count">
                {{ trans_choice('front.directories.results_count', $directoryItems->total(), ['count' => number_format($directoryItems->total(), 0, ',', '.')]) }}
            </span>
        </header>

        @if ($directoryItems->count())
            <ul class="directory-grid">
                @foreach ($directoryItems as $directoryItem)
                    <li class="directory-grid__item">
                        <a
                            class="directory-card"
                            href="{{ url($directoryItem['url']) }}"
                            aria-label="{{ __($directoryOpenLabel, ['name' => $directoryItem['title']]) }}"
                        >
                            <span class="directory-card__content">
                                <span class="directory-card__title">{{ $directoryItem['title'] }}</span>
                                <span class="directory-card__meta">
                                    {{ __('front.directories.books_label') }}
                                    <strong>{{ number_format($directoryItem['products_count'], 0, ',', '.') }}</strong>
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="directory-empty">
                <i class="fa-regular fa-books" aria-hidden="true"></i>
                <p>{{ __('front.directories.empty') }}</p>
            </div>
        @endif

        @include('front.catalog.partials.directory-pagination', ['paginator' => $directoryItems])
    </section>
</main>
