<?php

namespace Tests\Unit;

use App\Services\UserImpersonationService;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class UserImpersonationServiceTest extends TestCase
{
    public function testHasStateReturnsFalseWhenRequestHasNoSession(): void
    {
        $request = Request::create('/sitemap.xml');

        $this->assertFalse((new UserImpersonationService())->hasState($request));
    }
}
