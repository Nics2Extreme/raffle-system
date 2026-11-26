<?php

namespace App\Models;

use App\Enums\ParticipantSource;
use Carbon\CarbonInterface;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $full_name
 * @property string $employee_number
 * @property bool $privacy_agreed
 * @property ParticipantSource $source
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Draw|null $draw
 */
#[Fillable(['full_name', 'employee_number', 'privacy_agreed', 'source'])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    /**
     * @return HasOne<Draw, $this>
     */
    public function draw(): HasOne
    {
        return $this->hasOne(Draw::class);
    }

    /**
     * Form registrants who have not yet won.
     *
     * @param  Builder<Participant>  $query
     * @return Builder<Participant>
     */
    #[Scope]
    protected function eligible(Builder $query): Builder
    {
        return $query->whereDoesntHave('draw');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'privacy_agreed' => 'boolean',
            'source' => ParticipantSource::class,
        ];
    }
}
