<?php

namespace App\Models;

use App\Enums\PrizeType;
use Carbon\CarbonInterface;
use Database\Factories\PrizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $quantity
 * @property int $remaining
 * @property PrizeType $type
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable(['name', 'quantity', 'remaining', 'type'])]
class Prize extends Model
{
    /** @use HasFactory<PrizeFactory> */
    use HasFactory;

    /**
     * @return HasMany<Draw, $this>
     */
    public function draws(): HasMany
    {
        return $this->hasMany(Draw::class);
    }

    /**
     * @param  Builder<Prize>  $query
     * @return Builder<Prize>
     */
    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('remaining', '>', 0);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'remaining' => 'integer',
            'type' => PrizeType::class,
        ];
    }
}
