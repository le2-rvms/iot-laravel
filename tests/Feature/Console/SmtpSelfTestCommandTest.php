<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class SmtpSelfTestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_a_self_test_email_to_the_explicit_recipient(): void
    {
        $this->app['env'] = 'dev';
        config()->set('mail.from.address', 'fallback@example.com');

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $body, \Closure $callback): bool {
                $this->assertStringContainsString('SMTP 自检邮件发送于', $body);
                $this->assertStringContainsString('当前 SMTP 正常工作', $body);

                $message = Mockery::mock();
                $message->shouldReceive('to')->once()->with('target@example.com')->andReturnSelf();
                $message->shouldReceive('subject')->once()->with('[dev]SMTP Self Test')->andReturnSelf();

                $callback($message);

                return true;
            });

        $this->artisan('mail:smtp-test', [
            '--to' => 'target@example.com',
        ])
            ->expectsOutputToContain('已派发 SMTP 自检邮件到：target@example.com')
            ->assertSuccessful();
    }

    public function test_it_falls_back_to_mail_from_address_when_to_option_is_missing(): void
    {
        $this->app['env'] = 'production';
        config()->set('mail.from.address', 'fallback@example.com');

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $body, \Closure $callback): bool {
                $this->assertStringContainsString('SMTP 自检邮件发送于', $body);

                $message = Mockery::mock();
                $message->shouldReceive('to')->once()->with('fallback@example.com')->andReturnSelf();
                $message->shouldReceive('subject')->once()->with('SMTP Self Test')->andReturnSelf();

                $callback($message);

                return true;
            });

        $this->artisan('mail:smtp-test')
            ->expectsOutputToContain('已派发 SMTP 自检邮件到：fallback@example.com')
            ->assertSuccessful();
    }

    public function test_it_uses_the_custom_subject_when_provided(): void
    {
        config()->set('mail.from.address', 'fallback@example.com');

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $body, \Closure $callback): bool {
                $this->assertStringContainsString('SMTP 自检邮件发送于', $body);

                $message = Mockery::mock();
                $message->shouldReceive('to')->once()->with('target@example.com')->andReturnSelf();
                $message->shouldReceive('subject')->once()->with('Custom Subject')->andReturnSelf();

                $callback($message);

                return true;
            });

        $this->artisan('mail:smtp-test', [
            '--to' => 'target@example.com',
            '--subject' => 'Custom Subject',
        ])
            ->expectsOutputToContain('已派发 SMTP 自检邮件到：target@example.com')
            ->assertSuccessful();
    }

    public function test_the_legacy_alias_behaves_the_same_as_the_primary_command(): void
    {
        $this->app['env'] = 'dev';

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $body, \Closure $callback): bool {
                $this->assertStringContainsString('SMTP 自检邮件发送于', $body);

                $message = Mockery::mock();
                $message->shouldReceive('to')->once()->with('target@example.com')->andReturnSelf();
                $message->shouldReceive('subject')->once()->with('[dev]SMTP Self Test')->andReturnSelf();

                $callback($message);

                return true;
            });

        $this->artisan('_sys:smtp:self-test', [
            '--to' => 'target@example.com',
        ])
            ->expectsOutputToContain('已派发 SMTP 自检邮件到：target@example.com')
            ->assertSuccessful();
    }

    public function test_it_fails_when_no_recipient_is_available(): void
    {
        config()->set('mail.from.address', null);
        Mail::fake();

        $this->artisan('mail:smtp-test')
            ->expectsOutputToContain('请在 .env 设置 MAIL_FROM_ADDRESS，或通过 --to 传参。')
            ->assertFailed();

        Mail::assertNothingSent();
    }
}
