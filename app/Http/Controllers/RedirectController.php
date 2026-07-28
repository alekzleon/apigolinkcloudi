<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LinkStatus;
use App\Models\Link;
use App\Services\ClickTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RedirectController extends Controller
{
    public function __construct(
        private readonly ClickTrackingService $clickTrackingService
    ) {}

    public function __invoke(Request $request, string $shortCode): RedirectResponse|Response
    {
        $link = Link::query()
            ->where('short_code', $shortCode)
            ->first();

        if (! $link) {
            abort(404);
        }

        if ($link->status === LinkStatus::Inactive) {
            return response('Este enlace no esta disponible.', 403);
        }

        if ($link->isExpired()) {
            return response('Este enlace ha expirado.', 410);
        }

        $this->clickTrackingService->track($link, $request);

        return redirect()->away($link->original_url, 302);
    }
}
