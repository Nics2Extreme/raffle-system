<?php

namespace App\Models;

use App\Support\FairDrawSelector;
use Carbon\CarbonInterface;
use Database\Factories\DrawFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $participant_id
 * @property int $drawn_by
 * @property int|null $prize_id
 * @property string|null $prize_label
 * @property string $algorithm
 * @property int $pool_count
 * @property string|null $pool_hash
 * @property int|null $selected_index
 * @property CarbonInterface $drawn_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Participant $participant
 * @property-read User $drawnBy
 * @property-read Prize|null $prize
 */
#[Fillable([
    'participant_id',
    'drawn_by',
    'prize_id',
    'prize_label',
    'algorithm',
    'pool_count',
    'pool_hash',
    'selected_index',
    'drawn_at',
])]
class Draw extends Model
{
    /** @use HasFactory<DrawFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'algorithm' => FairDrawSelector::ALGORITHM,
        'pool_count' => 0,
    ];

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function drawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }

    /**
     * @return BelongsTo<Prize, $this>
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'drawn_at' => 'datetime',
            'pool_count' => 'integer',
            'selected_index' => 'integer',
        ];
    }
}
