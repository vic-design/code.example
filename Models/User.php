<?php

namespace App\Models;

use App\Enums\ActivityTypes;
use App\Exports\UsersExport;
use App\Http\Resources\UserResource;
use App\Imports\UsersImport;
use App\Models\Traits\ActivityLogSettings;
use App\Models\Traits\HasRequiredScopesInterface;
use App\Models\Traits\HasTenancy;
use App\Models\Traits\ProtectedAppends;
use App\Models\Traits\HasRequiredScopes;
use App\Traits\SyncMedia;
use Bavix\Wallet\Interfaces\Customer;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\CanPay;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Passport\HasApiTokens;
use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia, Wallet, Customer, HasRequiredScopesInterface
{
    use HasRoles;
    use ProtectedAppends;
    use HasFactory;

    use CanPay;

    use HasApiTokens;
    use HasMediaTrait;
    use Notifiable;
    use SyncMedia;
    use Filterable;
    use HasTenancy;
    use HasRequiredScopes;
    use ActivityLogSettings;

    /**
     * Default phone for tests
     */
    const DEFAULT_PHONE = '79999999999';

    /**
     * Password for non production environment
     */
    const DEFAULT_PASSWORD = 'secret';

    const DEFAULT_SMS_PASSWORD = '00000';

    /**
     * Password live time in minutes
     */
    const PASSWORD_LIVE_TIME = 10; //

    public $resourceClass = UserResource::class;
    public $browseResourceClass = UserResource::class;

    protected $dates = ['password_sent_at'];

    protected $with = [];

    public $importClass = UsersImport::class;
    public $exportClass = UsersExport::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'phone',
        'is_password_active',
        'patronymic',
        'last_name',
        'country_id',
        'address',
        'distributor',
        'city',
        'trade_point_id',
        'device_token',
        'os',
        'version',
        'visits',
        'last_login',
        'active',
        'inn',
        'impersonate_id',
        'from_source'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * The attributes that appends to model.
     *
     * @var array
     */
    public $mediaFields = [
        'avatar',
    ];

    public array $requiredScopes = [];

    protected static string $logName = ActivityTypes::ChangingUserProfile;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $model->passport_data()->create([
                'user_id' => $model->id,
            ]);
        });
    }

    public function getRequiredScopes(): array
    {
        if (!\Auth::hasUser()) {
            return [];
        }

        if (isUser()) {
            return [
                'self'
            ];
        }

        return $this->requiredScopes;
    }

    /**
     * Specifies the user's FCM tokens
     *
     * @return string
     * @link https://github.com/laravel-notification-channels/fcm
     */
    public function routeNotificationForFcm(): ?string
    {
        return $this->device_token;
    }

    public function registerMediaConversions(Media $media = NULL)
    {
        $this->addMediaConversion('thumb')
            ->width(120)
            ->height(120);
    }

    public function registerMediaCollections()
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useDisk('avatar');
    }

    public function companies(): BelongsToMany //TODO: Abandoned
    {
        return $this->belongsToMany(Company::class);
    }

    public function price_monitoring(): BelongsToMany
    {
        return $this->belongsToMany(PriceMonitoring::class, 'pm_user');
    }

    public function pm_result(): HasMany
    {
        return $this->hasMany(PmResult::class);
    }

    public function passport_data(): HasOne
    {
        return $this->hasOne(PassportData::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function findForPassport($username)
    {
        return $this
            ->active()
            ->where(function (Builder $query) use ($username) {
                $query->where('email', $username)
                    ->orWhere('phone', $username);
            })
            ->first();
    }

    public function tradePoint(): BelongsTo
    {
        return $this->belongsTo(TradePoint::class);
    }

    public function trade_point(): BelongsTo
    {
        return $this->belongsTo(TradePoint::class);
    }

    public function trade_points(): BelongsTo
    {
        return $this->belongsTo(TradePoint::class);
    }

    /*public function inn(): BelongsTo
    {
        return $this->tradePoint();
    }*/

    public function code(): BelongsTo
    {
        return $this->tradePoint();
    }

    public function acts(): HasMany
    {
        return $this->hasMany(Act::class);
    }

    public function command(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_command', 'user_id', 'command_user_id');
    }

    /**
     * deprecated
     *
     * todo перенести в ActivityService
     *
     * Relation to login history
     *
     * @return HasMany
     */
    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }

    public function getFirstLoginAtAttribute()
    {
        $activity = $this->loginActivities()->orderBy('id', 'DESC')->first();
        return $activity ? $activity->created_at : NULL;
    }

    public function getLastLoginAtAttribute()
    {
        $activity = $this->loginActivities()->orderBy('id')->first();
        return $activity ? $activity->created_at : NULL;
    }

    /**
     * Override the mail body for reset password notification mail.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\MailResetPasswordNotification($token, $this));
    }

    /**
     * Проверяет, можно ли еще войти по данному паролю
     *
     * @return bool
     */
    public function getIsPasswordActiveAttribute()
    {
        if ($this->password_sent_at === NULL) return false;
        $time_diff = $this->password_sent_at->diffInSeconds(Carbon::now());
        return $time_diff <= self::PASSWORD_LIVE_TIME * 60;
    }

    /*public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }*/

    public function getNameAndPhoneAttribute()
    {
        return $this->name . ' ' . $this->phone;
    }

    public function academyTests()
    {
        return $this->belongsToMany(AcademyTest::class, 'academy_tests_users');
    }


    public function scopeActive(Builder $query): Builder
    {
        return $query->where('users.active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('users.active', false);
    }

    public function scopeOsIn(Builder $query, $osNames): Builder
    {
        return $query->whereIn('os', $osNames);
    }

    public function scopeSelf(Builder $query): Builder
    {
        return $query->where('users.id', auth()->id());
    }

    /**
     * @param Builder $query
     * @param int $role_id
     * @return Builder
     */
    public function scopeByRoleId(Builder $query, int $role_id): Builder
    {
        return $query->whereHas('roles',
            function (Builder $builder) use ($role_id) {
                $builder->where('role_id', $role_id);
            });
    }

    public function getRoleAttribute(): ?string
    {
        $this->makeHidden('roles');
        return $this->getRoleNames()->first();
    }

    /**
     * @param Builder $query
     * @param User|null $user
     * @return Builder
     */
    public function scopeImpersonated(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? \Auth::user();

        return $query
            ->withoutGlobalScopes(['requiredScopes', 'tenancy'])
            ->where('impersonate_id', $user->id);
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return __('Изменение профиля');
    }
}
