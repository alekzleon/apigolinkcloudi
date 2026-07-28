<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Links\LinkAnalyticsRequest;
use App\Models\Link;
use App\Models\LinkClick;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LinkAnalyticsController extends Controller
{
    use ApiResponse;

    public function __invoke(LinkAnalyticsRequest $request, Link $link): JsonResponse
    {
        $this->authorize('view', $link);

        [$from, $to] = $this->dateRange($request);

        return $this->success([
            'summary' => [
                'total_clicks' => $this->baseQuery($link, $from, $to)->count(),
                'today_clicks' => $this->countBetween($link, now()->startOfDay(), now()->endOfDay()),
                'last_7_days_clicks' => $this->countBetween($link, now()->subDays(6)->startOfDay(), now()->endOfDay()),
                'last_30_days_clicks' => $this->countBetween($link, now()->subDays(29)->startOfDay(), now()->endOfDay()),
            ],
            'clicks_by_day' => $this->clicksByDay($link, $from, $to),
            'top_referrers' => $this->topGrouped($link, 'referrer', 'referrer', $from, $to),
            'top_devices' => $this->topGrouped($link, 'device_type', 'device', $from, $to),
            'top_browsers' => $this->topGrouped($link, 'browser', 'browser', $from, $to),
            'top_operating_systems' => $this->topGrouped($link, 'operating_system', 'operating_system', $from, $to),
        ]);
    }

    /**
     * @return array{0: CarbonImmutable|null, 1: CarbonImmutable|null}
     */
    private function dateRange(LinkAnalyticsRequest $request): array
    {
        $timezone = config('app.timezone');
        $from = $request->filled('from')
            ? CarbonImmutable::parse((string) $request->validated('from'), $timezone)->startOfDay()
            : null;
        $to = $request->filled('to')
            ? CarbonImmutable::parse((string) $request->validated('to'), $timezone)->endOfDay()
            : null;

        return [$from, $to];
    }

    /**
     * @return Builder<LinkClick>
     */
    private function baseQuery(Link $link, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        return LinkClick::query()
            ->where('link_id', $link->id)
            ->when($from !== null, fn (Builder $query) => $query->where('clicked_at', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->where('clicked_at', '<=', $to));
    }

    private function countBetween(Link $link, mixed $from, mixed $to): int
    {
        return $this->baseQuery(
            $link,
            CarbonImmutable::parse($from),
            CarbonImmutable::parse($to)
        )->count();
    }

    /**
     * @return list<array{date: string, clicks: int}>
     */
    private function clicksByDay(Link $link, ?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        return $this->baseQuery($link, $from, $to)
            ->selectRaw('DATE(clicked_at) as click_date, COUNT(*) as clicks')
            ->groupBy('click_date')
            ->orderBy('click_date')
            ->get()
            ->map(fn (LinkClick $row): array => [
                'date' => (string) $row->click_date,
                'clicks' => (int) $row->clicks,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function topGrouped(
        Link $link,
        string $column,
        string $responseKey,
        ?CarbonImmutable $from,
        ?CarbonImmutable $to
    ): array {
        return $this->baseQuery($link, $from, $to)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->select($column, DB::raw('COUNT(*) as clicks'))
            ->groupBy($column)
            ->orderByDesc('clicks')
            ->limit(10)
            ->get()
            ->map(fn (LinkClick $row): array => [
                $responseKey => (string) $row->{$column},
                'clicks' => (int) $row->clicks,
            ])
            ->values()
            ->all();
    }
}
