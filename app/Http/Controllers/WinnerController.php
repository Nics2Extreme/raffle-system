<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WinnerController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const SORTABLE = [
        'full_name' => 'participants.full_name',
        'employee_number' => 'participants.employee_number',
        'prize_label' => 'draws.prize_label',
        'selected_index' => 'draws.selected_index',
        'pool_count' => 'draws.pool_count',
        'pool_hash' => 'draws.pool_hash',
        'drawn_at' => 'draws.drawn_at',
        'drawn_by' => 'users.name',
    ];

    public function index(Request $request): Response
    {
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

        if (! array_key_exists($sort, self::SORTABLE)) {
            $sort = 'drawn_at';
            $direction = 'desc';
        }

        $winners = Draw::query()
            ->select('draws.*')
            ->join('participants', 'participants.id', '=', 'draws.participant_id')
            ->join('users', 'users.id', '=', 'draws.drawn_by')
            ->with(['participant', 'drawnBy'])
            ->orderBy(self::SORTABLE[$sort], $direction)
            ->orderByDesc('draws.id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Draw $draw): array => [
                'id' => $draw->id,
                'full_name' => $draw->participant->full_name,
                'employee_number' => $draw->participant->employee_number,
                'prize_label' => $draw->prize_label,
                'drawn_at' => $draw->drawn_at->toIso8601String(),
                'drawn_by' => $draw->drawnBy->name,
                'selected_index' => $draw->selected_index,
                'pool_count' => $draw->pool_count,
                'pool_hash' => $draw->pool_hash,
                'algorithm' => $draw->algorithm,
            ]);

        return Inertia::render('winners/index', [
            'winners' => $winners,
            'filters' => [
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }
}
