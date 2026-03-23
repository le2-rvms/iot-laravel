<?php

namespace App\Console\Commands;

use App\Models\Auth\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'admin:create-super-user',
    description: 'Create or upgrade a super admin user',
    help: 'Create or update an admin user, mark the email as verified, and assign the Super Admin role.',
    usages: [
        '--name="Admin" --email="admin@example.com" --password="password"',
    ],
)]
class CreateSuperUserCommand extends Command
{
    protected $signature = 'admin:create-super-user {--name=Admin} {--email=admin@example.com} {--password=password}';

    public function handle(): int
    {
        // 命令输入保持扁平结构，便于运维直接从 shell 历史里重复执行。
        $input = [
            'name' => $this->option('name'),
            'email' => $this->option('email'),
            'password' => $this->option('password'),
        ];

        $validator = Validator::make($input, [
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

        $adminUser = AdminUser::query()->firstOrNew([
            // 邮箱是这条命令的稳定标识，重复执行时会更新同一个运维账号。
            'email' => $input['email'],
        ]);

        // 成功提示区分首次创建和重复执行后的刷新，便于操作人确认本次行为。
        $created = ! $adminUser->exists;

        // 这是运维快捷命令，因此创建后账号应立即可用。
        $adminUser = $adminUser->saveAsSuperAdmin($input);

        $this->info($created ? '超级用户已创建。' : '超级用户已更新。');
        $this->line("邮箱: {$adminUser->email}");
        $this->line('角色: Super Admin');
        $this->line('邮箱验证: 已完成');

        return self::SUCCESS;
    }
}
