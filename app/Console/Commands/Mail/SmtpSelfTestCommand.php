<?php

namespace App\Console\Commands\Mail;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
class SmtpSelfTestCommand extends Command
{
    protected $signature = 'mail:smtp-test
        {--to= : Override the recipient email address}
        {--subject= : Override the email subject}';

    protected $description = 'Send a self-check email to verify SMTP delivery';

    protected $aliases = ['_sys:smtp:self-test'];

    public function handle(): int
    {
        $recipient = $this->option('to') ?: config('mail.from.address');

        if (! $recipient) {
            $this->error('请在 .env 设置 MAIL_FROM_ADDRESS，或通过 --to 传参。');

            return self::FAILURE;
        }

        $subjectPrefix = app()->isProduction() ? '' : '['.app()->environment().']';
        $subject = $this->option('subject') ?: $subjectPrefix.'SMTP Self Test';

        $body = 'SMTP 自检邮件发送于 '.Carbon::now()->toDateTimeString().'。如果你收到这封邮件，说明当前 SMTP 正常工作。';

        Mail::raw($body, function ($message) use ($recipient, $subject): void {
            $message->to($recipient)->subject($subject);
        });

        $this->info('已派发 SMTP 自检邮件到：'.$recipient);

        return self::SUCCESS;
    }
}
