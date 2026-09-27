<?php

namespace App\Http\Middleware;

use App\Models\Group;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');

        abort_unless(
            $group instanceof Group && $group->verifyAdminToken((string) $request->route('token')),
            404,
        );

        // Scoped bindings would scope to {token} (the parameter before the child), not {group},
        // so check here that any participant or exclusion in the URL belongs to this group.
        foreach (['participant', 'exclusion'] as $child) {
            $model = $request->route($child);

            abort_if($model && $model->group_id !== $group->id, 404);
        }

        return $next($request);
    }
}
