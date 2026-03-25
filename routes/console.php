<?php

use App\Console\Commands\Mail\SmtpSelfTestCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SmtpSelfTestCommand::class)
    ->dailyAt('09:00')
    ->description('SMTP 每日自检邮件');
