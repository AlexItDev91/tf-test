<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string $total_cents
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Collection<int, SaleItem> $items
 */
class Sale extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'total_cents',
    ];

    protected $casts = [
        'total_cents' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
