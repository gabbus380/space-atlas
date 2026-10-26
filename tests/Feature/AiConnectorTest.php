<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Connectors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiConnectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_server_anthropic_key_is_saved_and_applied(): void
    {
        Config::set('services.anthropic.key', 'test-anthropic-key-1234');
        Connectors::apply();

        $this->assertSame('test-anthropic-key-1234', Setting::get('ai.api_key'));
        $this->assertSame('anthropic', Setting::get('ai.provider'));
        $this->assertSame('test-anthropic-key-1234', config('services.anthropic.key'));
        $this->assertSame('1234', substr((string) Setting::hint('ai.api_key'), -4));
    }
}
