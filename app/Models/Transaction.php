<?php

namespace App\Models;

use App\Models\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @property TransactionStatus $status
 * @property float $amount
 * @property User $user
 * @property int $id
 */
class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'amount',
        'status',
    ];

    protected $casts = [
        'status' => TransactionStatus::class,
        'amount' => 'float',
    ];

    protected $appends = [
        'user_name',
    ];

    protected $hidden = [
        'user',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUserNameAttribute(): ?string
    {
        if (! empty($this->user)) {
            return $this->user->name;
        }

        return null;
    }

    public static function getPaymentsByUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return self::query()->whereUserId($user->id)
            ->select('id', 'amount', 'status', 'created_at', 'updated_at')
            ->when(! empty($filters['sort']), function (Builder $builder) use ($filters): Builder {
                $column = str_replace('-', '', $filters['sort']);
                if (! Schema::hasColumn((new self)->getTable(), $column)) {
                    Log::warning('Trying to access non-existence column.', [
                        'column' => $column,
                        'method' => 'getPaymentsByUser',
                    ]);

                    return $builder;
                }

                if (str_starts_with($filters['sort'], '-')) {
                    return $builder->orderBy('created_at');
                }

                return $builder->orderByDesc('created_at');
            })
            ->when(empty($filters['sort']), fn (Builder $builder): Builder => $builder->orderBy('created_at'))
            ->when(! empty($filters['status']), function (Builder $builder) use ($filters): Builder {
                return $builder->where('status', TransactionStatus::from($filters['status']));
            })
            ->paginate($filters['limit'] ?? 10);
    }

    public static function getPaymentSummary(): Collection
    {
        return self::query()
            ->select('id', 'user_id', 'amount', 'status', 'created_at', 'updated_at')
            ->with('user')
            ->get();
    }
}
