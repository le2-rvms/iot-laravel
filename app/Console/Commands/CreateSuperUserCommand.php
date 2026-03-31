<?php

namespace App\Console\Commands;

use App\Models\Admin\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'admin:create-super-user',
    description: 'Create or upgrade a super admin user',
    help: 'Create or update an admin user, mark the email as verified, and assign the Super Admin role.',
    usages: [
        '--name="Admin" --email="admin@example.com" --password="your-password"',
        '--name="Admin" --email="admin@example.com"',
    ],
)]
class CreateSuperUserCommand extends Command
{
    protected $signature = 'admin:create-super-user {--name=Admin} {--email=admin@example.com} {--password=}';

    public function handle(): int
    {
        $validator = Validator::make($this->option(), [
            'name' => ['string'],
            'email' => ['email'],
            'password' => ['string'],
        ], [
            'email.email' => 'The --email option must be a valid email address.',
        ]);

        if ($validator->fails()) {
            // 命令失败时只输出普通校验错误并返回非零状态码，便于脚本侧处理。
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $validated = $validator->validated();

        $passwordWasGenerated = blank($validated['password']);

        if ($passwordWasGenerated) {
            // 先走命令参数校验，校验通过后再为缺省密码补一个随机值。
            $validated['password'] = Str::password(length: 16);
        }

        $adminUser = AdminUser::query()->firstOrNew([
            // 邮箱是这条命令的稳定标识，重复执行时会更新同一个运维账号。
            'email' => $validated['email'],
        ]);

        // 成功提示区分首次创建和重复执行后的刷新，便于操作人确认本次行为。
        $created = ! $adminUser->exists;

        // 这是运维快捷命令，因此创建后账号应立即可用。
        $adminUser = $adminUser->saveAsSuperAdmin($validated);

        $this->info($created ? '超级用户已创建。' : '超级用户已更新。');
        $this->line("邮箱: {$adminUser->email}");
        $this->line('角色: Super Admin');
        $this->line('邮箱验证: 已完成');
        $this->line('密码来源: '.($passwordWasGenerated ? '系统随机生成' : '命令参数传入'));
        $this->line("密码: {$validated['password']}");

        return self::SUCCESS;
    }
}
