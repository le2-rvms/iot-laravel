# Laravel 13 后台骨架实施方案

## 摘要

- 以当前 Laravel 13 仓库为基础，手工接入 `Inertia + Vue 3(JS) + Tailwind 4 + shadcn-vue`，维持“同仓、单体、服务端主导”的后台架构。
- 后端认证逻辑采用 `Fortify` 作为无 UI 基底；前端认证页面由 `Inertia + Vue` 手工实现，不使用 starter kit 默认页面结构。`Breeze` 若参考，仅作为兼容性脚手架样例，不作为主路线。
- 首版邮箱验证为“硬拦路”模式：后台核心页面默认放在 `auth + verified` 后面，未验证用户统一进入 `/email/verify`。
- 首个业务模块选 `Users`，只覆盖现有 `users` 表的 `name/email/password`；管理员后台创建用户后立即发送验证邮件。
- 队列后端默认使用 `Redis`，并已接入 `Laravel Horizon` 作为官方队列监控面板。
- Laravel 默认框架语言切为简体中文，通过 `laravel-lang/lang` 维护验证、认证、密码重置、分页等中文文案。
- 模型统一补充 `PHPDoc @property` 中文字段说明，并通过 `attributeLabels()` 与 `FormRequest::attributes()` 收口验证字段显示名。

## 初始化步骤

1. 安装 Inertia 与 Vue 基础：
    - `composer require inertiajs/inertia-laravel`
    - `php artisan inertia:middleware`
    - `npm install vue @vitejs/plugin-vue @inertiajs/vue3`
2. 安装 Fortify：
    - `composer require laravel/fortify`
    - `php artisan fortify:install`
    - 在 `config/fortify.php` 中启用：
        - `Features::resetPasswords()`
        - `Features::emailVerification()`
    - 不启用 `Features::registration()`
3. 安装 Horizon：
    - `composer require laravel/horizon`
    - `php artisan horizon:install`
4. 安装前端支撑：
    - `npm install unplugin-vue-components`
    - `npx shadcn-vue@latest init`
5. 将 `components.json` 锁定为 JavaScript 模式：
    - `typescript: false`
    - `style: "new-york"`
    - `tailwind.css: "resources/css/app.css"`
    - `tailwind.baseColor: "neutral"`
    - `tailwind.cssVariables: true`
    - `aliases.components: "@/components"`
    - `aliases.ui: "@/components/ui"`
    - `aliases.lib: "@/lib"`
    - `aliases.utils: "@/lib/utils"`
6. 通过 `shadcn-vue` 添加首批基础组件：
    - `button,input,label,card,checkbox,alert,dropdown-menu,dialog,sheet,table,pagination,badge,avatar,breadcrumb,separator,sonner,skeleton`
7. 按 Inertia 官方方式完成服务端桥接：
    - 建立 `resources/views/app.blade.php`
    - 挂载 `HandleInertiaRequests` middleware
    - 在前端入口接入 `createInertiaApp`
8. 执行迁移与验证：
    - `php artisan migrate`
    - `php artisan test`

## 目录结构

- `resources/js/app.js`
- `resources/js/pages/Auth/Login.vue`
- `resources/js/pages/Auth/ForgotPassword.vue`
- `resources/js/pages/Auth/ResetPassword.vue`
- `resources/js/pages/Auth/VerifyEmail.vue`
- `resources/js/pages/Dashboard.vue`
- `resources/js/pages/Users/Index.vue`
- `resources/js/pages/Users/Create.vue`
- `resources/js/pages/Users/Edit.vue`
- `resources/js/pages/Roles/Index.vue`
- `resources/js/pages/Roles/Create.vue`
- `resources/js/pages/Roles/Edit.vue`
- `resources/js/pages/Settings/Index.vue`
- `resources/js/pages/Settings/FormLab.vue`
- `resources/js/layouts/AuthLayout.vue`
- `resources/js/layouts/AppLayout.vue`
- `resources/js/components/ui/*`
- `resources/js/components/app/Sidebar.vue`
- `resources/js/components/app/Header.vue`
- `resources/js/components/app/UserMenu.vue`
- `resources/js/components/app/PageToolbar.vue`
- `resources/js/components/app/DataTableShell.vue`
- `resources/js/components/app/PaginationBar.vue`
- `resources/js/components/app/ConfirmDialog.vue`
- `resources/js/components/app/EmptyState.vue`
- `resources/js/components/app/LoadingState.vue`
- `resources/js/components/app/FlashToaster.vue`
- `resources/js/components/auth/LoginForm.vue`
- `resources/js/components/auth/ForgotPasswordForm.vue`
- `resources/js/components/auth/ResetPasswordForm.vue`
- `resources/js/components/users/Form.vue`
- `resources/js/components/users/UserFilters.vue`
- `resources/js/components/users/Table.vue`
- `resources/js/components/roles/Form.vue`
- `resources/js/components/roles/Table.vue`
- `resources/js/components/roles/PermissionMatrix.vue`
- `resources/js/components/settings/NotificationRuleForm.vue`
- `resources/js/components/shared/forms/FieldError.vue`
- `resources/js/components/shared/forms/FormFieldShell.vue`
- `resources/js/components/shared/forms/FormSection.vue`
- `resources/js/components/shared/forms/RepeaterField.vue`
- `resources/js/composables/useInertiaFormBridge.js`
- `resources/js/lib/utils.js`
- `resources/js/lib/navigation.js`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Providers/FortifyServiceProvider.php`
- `app/Providers/HorizonServiceProvider.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/Users/UserController.php`
- `app/Http/Controllers/Roles/RoleController.php`
- `app/Http/Controllers/Settings/SettingsController.php`
- `app/Http/Controllers/Settings/FormLabController.php`
- `app/Http/Requests/Users/StoreUserRequest.php`
- `app/Http/Requests/Users/UpdateUserRequest.php`
- `app/Http/Requests/Roles/StoreRoleRequest.php`
- `app/Http/Requests/Roles/UpdateRoleRequest.php`
- `app/Http/Requests/Settings/StoreNotificationRuleRequest.php`
- `app/Support/PermissionRegistry.php`
- `database/seeders/RolePermissionSeeder.php`
- `routes/web.php`

## 关键配置与实现约定

### Laravel / Inertia

- 使用 `php artisan inertia:middleware` 生成 `HandleInertiaRequests`，并在 `bootstrap/app.php` 的 `web` 中间件组挂载。
- 新增 `resources/views/app.blade.php`，只负责 `@vite`、`@inertiaHead`、`@inertia`。
- `HandleInertiaRequests::share()` 只共享：
    - `auth.user: id,name,email,email_verified_at`
    - `flash.success`
    - `flash.error`
    - `app.name`
- `routes/web.php`：
    - `/` 重定向到 `/dashboard` 或 `/login`
    - `/dashboard` 使用 `auth + verified`
    - `users` 资源路由使用 `auth + verified`
    - `/email/verify` 使用 `auth`，作为 `verified` 中间件拦截后的落点
- 首版约定：后台主路由默认受 `verified` 保护；若未来出现无需验证即可访问的后台页面，再单独从 `verified` 组中拆出。

### Fortify 认证基底

- 使用 Fortify 提供登录、退出、忘记密码、重置密码、邮箱验证相关 routes、controllers 和 logic。
- 在 `FortifyServiceProvider` 中通过：
    - `Fortify::loginView()`
    - `Fortify::requestPasswordResetLinkView()`
    - `Fortify::resetPasswordView()`
    - `Fortify::verifyEmailView()`
      返回对应 Inertia 页面。
- 不开放注册：
    - `config/fortify.php` 不启用 `Features::registration()`
    - 前端不提供注册入口
- `App\Models\Auth\User` 实现 `MustVerifyEmail`。
- 后台创建用户后的规则：
    - `UserController@store` 创建成功后立即触发邮箱验证通知
    - 触发方式用 `sendEmailVerificationNotification()`，不依赖公开注册流
    - 若用户邮箱已验证或未来导入场景需要跳过，再作为后续策略扩展，不放进首版默认分支

### 前端入口与自动导入

- `resources/js/app.js` 使用 `createInertiaApp` + `resolvePageComponent`，页面根目录固定 `./pages/**/*.vue`。
- Vite 增加：
    - `vue()`
    - `Components({ dirs: ['resources/js/components', 'resources/js/layouts'], extensions: ['vue'], deep: true, dts: false, directoryAsNamespace: true, collapseSamePrefixes: true })`
    - `resolve.alias['@'] = /resources/js`
- 新增 `jsconfig.json`，配置 `@/* -> resources/js/*`。
- 自动导入覆盖 `resources/js/components` 与 `resources/js/layouts`。
- `components/ui` 继续使用 `Ui*` 命名空间，例如 `UiDialogContent`、`UiDropdownMenuItem`、`UiSonner`。
- 业务组件按目录命名空间自动导入，例如 `AppSidebar`、`AuthLoginForm`、`UsersForm`、`UsersTable`、`RolesForm`、`SettingsNotificationRuleForm`、`SharedFormsFormSection`。
- `FlashToaster.vue` 基于 `sonner` 封装，监听 `flash.success`、`flash.error`，作为应用壳层唯一全局提示出口。
- Horizon 使用官方面板 `/horizon`，不包装成 Inertia 页面；从 `Settings` 使用原生跳转进入。

## 页面与模块实施

### 布局

- `AuthLayout`
    - 居中卡片布局，统一品牌区、标题、副标题、表单容器。
- `AppLayout`
    - 固定 `Sidebar + Header + MainContent`
    - `Sidebar` 使用本地 `navigation.js`，不放 shared data
    - `Header` 预留面包屑、全局动作、用户菜单
    - 壳层挂 `FlashToaster` 与全局 `ConfirmDialog`
- `Horizon`
    - 继续使用官方 provider 与 `/horizon` 路径
    - 中间件为 `auth + verified + can:settings.read`
    - provider gate 与现有 `settings.read` 权限保持一致
- `spatie/laravel-permission` 首轮可暂缓，但目录、导航、控制器与布局必须为未来权限判断预留接口。

### 认证页面

- `Login.vue`
    - `email/password/remember`
    - `useForm` 提交到 Fortify 登录路由
- `ForgotPassword.vue`
    - `email`
    - 提交发送重置邮件
- `ResetPassword.vue`
    - `token/email/password/password_confirmation`
- `VerifyEmail.vue`
    - 显示验证提示、重发邮件、退出登录
    - 作为未验证用户访问 `verified` 路由时的承接页面

### Dashboard

- `DashboardController` 立即返回：
    - 当前用户基础信息
    - `stats.usersCount`
- Deferred props：
    - `recentUsers`
    - `systemCards`
- 页面结构：
    - 标题区
    - KPI 卡片区
    - 最近用户/快捷入口区

### Users 首个 CRUD

- 控制器：
    - `index/create/store/edit/update/destroy`
- 列表：
    - 搜索 `name/email`
    - 分页使用 Laravel paginator
    - 默认 `latest()`
- 页面结构：
    - `PageToolbar`
    - `DataTableShell`
    - `UsersTable`
    - `PaginationBar`
    - `EmptyState`
    - `LoadingState`
- `UsersForm`
    - create/edit 共用
    - 创建时密码必填，编辑时密码留空表示不修改
- 删除：
    - 行操作触发 `ConfirmDialog`
- Partial reload：
    - 搜索、筛选、分页默认优先使用 `router.reload({ only: ['users', 'filters'] })`
    - 仅在需要修改 URL 查询参数时配合 `router.get(...)`

### Roles / Settings / Complex Form

- `Roles` 模块已落地：
    - 列表、创建、编辑、删除
    - 权限模型统一为 `module.read` / `module.write`
    - 页面使用 `RolesForm`、`RolesTable`、`RolesPermissionMatrix`
- `Settings` 已落地为受权限保护的入口页，并包含：
    - Horizon 官方面板入口
    - `复杂表单实验室` 入口
    - `Settings/FormLab.vue` 示例页
- 复杂表单接入点已落地：
    - 技术选型为 `vee-validate + yup + Inertia`
    - 基础设施包含 `useInertiaFormBridge`、`SharedFormsFormSection`、`SharedFormsFormFieldShell`、`SharedFormsRepeaterField`
    - 示例业务表单为 `SettingsNotificationRuleForm`
    - 后端用 `StoreNotificationRuleRequest` 做 Laravel 最终校验

## 测试方案

- Feature 测试覆盖：
    - 访客访问 `/dashboard`、`/users` 会被重定向到登录页
    - 已登录但未验证邮箱的用户访问 `/dashboard`、`/users` 会被重定向到 `/email/verify`
    - 登录成功、失败、remember me
    - 忘记密码邮件发送
    - 重置密码成功与 token 无效
    - 验证邮件重发
    - 登录后可访问 Dashboard
    - Users 列表、创建、更新、删除、搜索、分页
    - 后台创建用户后发送验证通知
    - Users 列表页的 Inertia partial reload 返回 `users/filters` 关键 props
    - Roles 列表、创建、更新、删除与权限限制
    - Settings 页面访问控制
    - Horizon 页面访问控制
    - FormLab 页面访问控制、复杂 payload 校验与成功提交
- 验证方式以 Laravel Feature + Inertia 断言为主。
- 额外检查：
    - `npm run build`
    - `php artisan test`

## 假设与参考

- 默认中文后台文案；Laravel 验证消息本地化可后补。
- 简单表单坚持 `useForm`；复杂动态表单使用 `vee-validate`。
- 参考文档：
    - Inertia server-side setup: [inertiajs.com/server-side-setup](https://inertiajs.com/server-side-setup)
    - Inertia Partial Reloads: [inertiajs.com/partial-reloads](https://inertiajs.com/partial-reloads)
    - Inertia Deferred Props: [inertiajs.com/deferred-props](https://inertiajs.com/deferred-props)
    - Laravel Authentication: [laravel.com/docs/13.x/authentication](https://laravel.com/docs/13.x/authentication)
    - Laravel Fortify: [laravel.com/docs/13.x/fortify](https://laravel.com/docs/13.x/fortify)
    - Laravel Starter Kits: [laravel.com/docs/13.x/starter-kits](https://laravel.com/docs/13.x/starter-kits)
