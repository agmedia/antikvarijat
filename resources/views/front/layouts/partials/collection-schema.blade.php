@if ($collectionPaginator && \Illuminate\Support\Str::startsWith(\App\Helpers\Metatags::robots(request()), 'index'))
    @php
        $collectionSchema = \App\Helpers\StructuredData::itemList(
            \App\Helpers\Metatags::canonical(request()),
            $collectionName,
            $collectionPaginator
        );
    @endphp
    <script type="application/ld+json">{!! \App\Helpers\StructuredData::toJson($collectionSchema) !!}</script>
@endif
