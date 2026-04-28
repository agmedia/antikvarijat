<?php echo '<?xml version="1.0" encoding="UTF-8" ?>'; ?>
<Books>
    @foreach ($feed['books'] as $book)
        <Book>
            <date_update>{{ $book['date_update'] }}</date_update>
            @if ($book['author'] !== '')
                <author>{{ $book['author'] }}</author>
            @endif
            <title>{{ $book['title'] }}</title>
            @if ($book['description'] !== '')
                <description>{{ $book['description'] }}</description>
            @endif
            <source_id>{{ $book['source_id'] }}</source_id>
            <sku_dealer_item_id>{{ $book['sku_dealer_item_id'] }}</sku_dealer_item_id>
            @if ($book['year'] !== '')
                <year>{{ $book['year'] }}</year>
            @endif
            @if ($book['edition'] !== '')
                <edition>{{ $book['edition'] }}</edition>
            @endif
            @if ($book['publisher'] !== '')
                <publisher>{{ $book['publisher'] }}</publisher>
            @endif
            <price>{{ $book['price'] }}</price>
            @if ($book['keywords'] !== '')
                <keywords>{{ $book['keywords'] }}</keywords>
            @endif
            @if ($book['isbn'] !== '')
                <isbn>{{ $book['isbn'] }}</isbn>
            @endif
            @if ($book['first_edition'] !== null)
                <first_edition>{{ $book['first_edition'] }}</first_edition>
            @endif
            @if ($book['signed'] !== null)
                <signed>{{ $book['signed'] }}</signed>
            @endif
            @if ($book['dust_jacket'] !== null)
                <dust_jacket>{{ $book['dust_jacket'] }}</dust_jacket>
            @endif
            <item_url>{{ $book['item_url'] }}</item_url>
            @if (! empty($book['image_urls']))
                <image_urls>
                    @foreach ($book['image_urls'] as $imageUrl)
                        <image_url>{{ $imageUrl }}</image_url>
                    @endforeach
                </image_urls>
            @endif
        </Book>
    @endforeach
</Books>
