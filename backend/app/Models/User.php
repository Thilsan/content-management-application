<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Resolved once per request, since authorization checks it repeatedly.
     *
     * @var list<string>|null
     */
    protected ?array $privilegeNames = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Flattened privilege names granted by every role the user holds.
     *
     * @return list<string>
     */
    public function privilegeNames(): array
    {
        if ($this->privilegeNames === null) {
            $this->loadMissing('roles.privileges');

            $this->privilegeNames = $this->roles
                ->pluck('privileges')
                ->flatten()
                ->pluck('name')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        return $this->privilegeNames;
    }

    public function hasPrivilege(string $privilege): bool
    {
        return in_array($privilege, $this->privilegeNames(), true);
    }
}
