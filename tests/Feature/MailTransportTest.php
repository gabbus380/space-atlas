<?php

namespace Tests\Feature;

use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ResendTransport;
use Tests\TestCase;

class MailTransportTest extends TestCase
{
    public function test_resend_transport_is_installed(): void
    {
        config(['services.resend.key' => 're_test']);

        $transport = app(MailManager::class)->mailer('resend')->getSymfonyTransport();

        $this->assertInstanceOf(ResendTransport::class, $transport);
    }

    public function test_default_sender_is_the_atlas_address(): void
    {
        $this->assertSame('atlas@emmaconsulting.space', config('mail.from.address'));
        $this->assertSame('Sovereign Space ATLAS', config('mail.from.name'));
    }
}
