<?php

namespace App\Http\Controllers\Admin\Audits;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup]
class AuditController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = Audit::query()
            ->with('actor:id,name,email')
            ->latest('created_at')
            ->latest('id');

        $filters = (new ListQueryFilters(
            request: $request,
            fieldDefinitions: [
                'event',
                'auditable_type',
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);

                    $query->where(function (Builder $nestedQuery) use ($search): void {
                        $nestedQuery->whereHas('actor', function (Builder $actorQuery) use ($search): void {
                            $actorQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })->orWhere('meta->route', 'like', "%{$search}%");

                        if (ctype_digit($search)) {
                            $nestedQuery->orWhere('auditable_id', (int) $search);
                        }
                    });
                },
            ],
        ))->apply($query);

        $filters = array_merge([
            'search__func' => '',
            'event__eq' => '',
            'auditable_type__eq' => '',
        ], $filters);

        $audits = $query
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Audits/Index', [
            'filters' => $filters,
            'audits' => $audits,
            'eventOptions' => Audit::eventOptions(),
            'resourceTypeOptions' => Audit::resourceTypeOptions(),
        ]);
    }
}
