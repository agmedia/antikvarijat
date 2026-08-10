<?php

namespace Tests\Unit;

use App\Helpers\Asset;
use Tests\TestCase;

class AssetTest extends TestCase
{
    public function test_local_assets_receive_their_file_timestamp_as_version(): void
    {
        $path = 'css/theme.min.css';

        $this->assertSame(
            asset($path) . '?v=' . filemtime(public_path($path)),
            Asset::url($path)
        );
    }

    public function test_existing_query_string_is_replaced_by_automatic_version(): void
    {
        $this->assertSame(
            asset('js/theme.min.js') . '?v=' . filemtime(public_path('js/theme.min.js')),
            Asset::url('js/theme.min.js?v=old')
        );
    }
}
