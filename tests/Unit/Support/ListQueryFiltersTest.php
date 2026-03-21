<?php

namespace Tests\Unit\Support;

use App\Models\Auth\User;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Tests\TestCase;

class ListQueryFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_eq_gt_like_and_in_filters(): void
    {
        User::factory()->create([
            'name' => 'Alice Cooper',
            'email' => 'alice@example.com',
        ]);
        User::factory()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);
        User::factory()->create([
            'name' => 'Boris Stone',
            'email' => 'boris@example.com',
        ]);

        $query = User::query()->orderBy('id');

        $filters = (new ListQueryFilters(
            Request::create('/users', 'GET', [
                'id__gt' => '1',
                'name__like' => 'o',
                'email__in' => 'bob@example.com,boris@example.com',
            ]),
            [
                'id' => ['integer'],
                'name',
                'email',
            ],
        ))->apply($query);

        $this->assertSame([
            'id__gt' => '1',
            'name__like' => 'o',
            'email__in' => 'bob@example.com,boris@example.com',
        ], $filters);
        $this->assertSame(['bob@example.com', 'boris@example.com'], $query->pluck('email')->all());
    }

    public function test_it_applies_registered_func_filters(): void
    {
        User::factory()->create([
            'name' => 'Alpha',
            'email' => 'alpha@example.com',
        ]);
        User::factory()->create([
            'name' => 'Beta',
            'email' => 'beta@example.com',
        ]);

        $query = User::query()->orderBy('id');

        (new ListQueryFilters(
            Request::create('/users', 'GET', [
                'search__func' => 'alpha',
            ]),
            ['name', 'email'],
            [
                'search' => function (Builder $query, mixed $value): void {
                    $query->where('email', 'like', '%'.trim((string) $value).'%');
                },
            ],
        ))->apply($query);

        $this->assertSame(['alpha@example.com'], $query->pluck('email')->all());
    }

    public function test_page_is_treated_as_passthrough_and_empty_values_are_skipped(): void
    {
        User::factory()->create([
            'name' => 'Alpha',
            'email' => 'alpha@example.com',
        ]);
        User::factory()->create([
            'name' => 'Beta',
            'email' => 'beta@example.com',
        ]);

        $query = User::query()->orderBy('id');

        $filters = (new ListQueryFilters(
            Request::create('/users', 'GET', [
                'page' => '2',
                'name__eq' => '',
            ]),
            ['name'],
        ))->apply($query);

        $this->assertSame([], $filters);
        $this->assertCount(2, $query->get());
    }

    public function test_it_rejects_unknown_fields_unknown_operators_and_unregistered_callbacks(): void
    {
        $this->expectException(HttpResponseException::class);

        try {
            (new ListQueryFilters(
                Request::create('/users', 'GET', [
                    'unknown__eq' => 'alpha',
                    'name__foo' => 'beta',
                    'search__func' => 'gamma',
                ]),
                ['name'],
            ))->apply(User::query());
        } catch (HttpResponseException $exception) {
            $response = $exception->getResponse();

            $this->assertSame(422, $response->getStatusCode());
            $this->assertSame('该字段不允许作为筛选条件。', $response->getData(true)['errors']['unknown__eq'][0]);
            $this->assertSame('该字段不支持当前筛选操作符。', $response->getData(true)['errors']['name__foo'][0]);
            $this->assertSame('未注册该筛选回调。', $response->getData(true)['errors']['search__func'][0]);

            throw $exception;
        }
    }

    public function test_boolean_rules_only_allow_eq_operator(): void
    {
        $this->expectException(HttpResponseException::class);

        try {
            (new ListQueryFilters(
                Request::create('/configs', 'GET', [
                    'is_masked__gt' => '1',
                ]),
                [
                    'is_masked' => ['boolean'],
                ],
            ))->apply(User::query());
        } catch (HttpResponseException $exception) {
            $response = $exception->getResponse();

            $this->assertSame(422, $response->getStatusCode());
            $this->assertSame('该字段不支持当前筛选操作符。', $response->getData(true)['errors']['is_masked__gt'][0]);

            throw $exception;
        }
    }
}
