<?php

namespace App\Http\Middleware;

use App\Models\Entity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentEntity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // セッションから現在の事業体IDを取得（なければデフォルトで個人事業）
        $entityId = session('current_entity_id');

        if (!$entityId) {
            $entity = Entity::where('type', 'individual')->first();
            $entityId = $entity?->id ?? 1;
            session(['current_entity_id' => $entityId]);
        }

        // 現在の事業体をビューで使えるように共有
        $currentEntity = Entity::find($entityId);
        view()->share('currentEntity', $currentEntity);
        view()->share('entities', Entity::all());

        return $next($request);
    }
}
