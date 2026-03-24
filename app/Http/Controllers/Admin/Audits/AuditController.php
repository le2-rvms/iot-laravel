<?php

namespace App\Http\Controllers\Admin\Audits;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Support\CsvExporter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[PermissionGroup]
class AuditController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = Audit::indexQuery($request->query());
        $filters = collect($request->except('page'))
            ->map(fn (mixed $value): mixed => $value ?? '')
            ->all();

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

    #[PermissionAction('read')]
    public function export(Request $request): StreamedResponse
    {
        $query = Audit::indexQuery($request->query());

        return CsvExporter::download(
            query: $query,
            columns: [
                'models.audit.id' => static fn (Audit $audit): int => $audit->id,
                'models.audit.created_at' => static fn (Audit $audit): string => $audit->created_at?->format('Y-m-d H:i:s') ?? '',
                'models.audit.event_label' => static fn (Audit $audit): string => $audit->event_label,
                'models.audit.resource_type_label' => static fn (Audit $audit): string => $audit->resource_type_label,
                'models.audit.auditable_id' => static fn (Audit $audit): string => (string) $audit->auditable_id,
                'models.audit.actor_name' => static fn (Audit $audit): string => $audit->actor?->name ?? '系统',
                'models.audit.actor_email' => static fn (Audit $audit): string => $audit->actor?->email ?? '',
                'models.audit.route' => static fn (Audit $audit): string => $audit->route ?? '',
                'models.audit.method' => static fn (Audit $audit): string => $audit->method ?? '',
                'models.audit.ip' => static fn (Audit $audit): string => $audit->ip ?? '',
                'models.audit.change_summary' => static fn (Audit $audit): string => $audit->change_summary,
            ],
            fileName: 'audits-'.now()->format('Ymd-His').'.csv',
        );
    }
}
