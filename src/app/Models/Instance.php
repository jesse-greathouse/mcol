<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Database\Eloquent\Model,
    Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Instance
 *
 * Represents an instance associated with a client.
 *
 * @property string $status The current status of the instance, either 'UP' or 'DOWN'.
 * @property int $client_id The foreign key linking this instance to a client.
 */
class Instance extends Model
{
    use HasFactory;

    /** @var string Constant for the 'UP' status. */
    const STATUS_UP = 'UP';

    /** @var string Constant for the 'DOWN' status. */
    const STATUS_DOWN = 'DOWN';

    /**
     * Mass-assignable attributes. Empty array to allow all attributes to be mass assigned.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the client that owns the instance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
