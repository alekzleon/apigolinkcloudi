<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\LinkStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Links\StoreLinkRequest;
use App\Http\Requests\Links\UpdateLinkRequest;
use App\Http\Requests\Links\UpdateLinkStatusRequest;
use App\Http\Resources\LinkResource;
use App\Models\Link;
use App\Services\LinkService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LinkService $linkService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sort = $this->allowedSort((string) $request->query('sort', 'created_at'));
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $links = Link::query()
            ->whereBelongsTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = (string) $request->query('search');

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('original_url', 'like', "%{$search}%")
                        ->orWhere('short_code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $status = LinkStatus::tryFrom((string) $request->query('status'));

                if ($status !== null) {
                    $query->where('status', $status);
                }
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return $this->success([
            'links' => LinkResource::collection($links->items()),
            'meta' => [
                'current_page' => $links->currentPage(),
                'from' => $links->firstItem(),
                'last_page' => $links->lastPage(),
                'per_page' => $links->perPage(),
                'to' => $links->lastItem(),
                'total' => $links->total(),
            ],
        ]);
    }

    public function store(StoreLinkRequest $request): JsonResponse
    {
        $link = $this->linkService->create($request->user(), $request->validated());

        return $this->success(
            new LinkResource($link),
            'Enlace creado correctamente.',
            201
        );
    }

    public function show(Link $link): JsonResponse
    {
        $this->authorize('view', $link);

        return $this->success(new LinkResource($link));
    }

    public function update(UpdateLinkRequest $request, Link $link): JsonResponse
    {
        $this->authorize('update', $link);

        $link = $this->linkService->update($link, $request->validated());

        return $this->success(new LinkResource($link), 'Enlace actualizado correctamente.');
    }

    public function updateStatus(UpdateLinkStatusRequest $request, Link $link): JsonResponse
    {
        $this->authorize('update', $link);

        $link = $this->linkService->updateStatus(
            $link,
            LinkStatus::from((string) $request->validated('status'))
        );

        return $this->success(new LinkResource($link), 'Estado del enlace actualizado correctamente.');
    }

    public function destroy(Link $link): JsonResponse
    {
        $this->authorize('delete', $link);

        $link->delete();

        return $this->success(null, 'Enlace eliminado correctamente.');
    }

    private function allowedSort(string $sort): string
    {
        return in_array($sort, ['created_at', 'updated_at', 'name', 'clicks_count', 'last_clicked_at'], true)
            ? $sort
            : 'created_at';
    }
}
