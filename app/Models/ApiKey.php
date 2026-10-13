<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Str;
use Webmozart\Assert\Assert;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Pterodactyl\Models\ApiKey.
 *
 * @property int $id
 * @property int $user_id
 * @property int $key_type
 * @property string $identifier
 * @property string $token
 * @property array|null $allowed_ips
 * @property string|null $memo
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $r_servers
 * @property int $r_nodes
 * @property int $r_allocations
 * @property int $r_users
 * @property int $r_locations
 * @property int $r_nests
 * @property int $r_eggs
 * @property int $r_database_hosts
 * @property int $r_server_databases
 * @property User $tokenable
 * @property User $user
 *
 * @method static \Database\Factories\ApiKeyFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey query()
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereAllowedIps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereKeyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRAllocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRDatabaseHosts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereREggs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRNests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRNodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRServerDatabases($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRServers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereRUsers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiKey whereUserId($value)
 *
 * @mixin \Eloquent
 */
class ApiKey extends Model
{
  /** @use HasFactory<\Database\Factories\ApiKeyFactory> */
  use HasFactory;

  public const RESOURCE_NAME = 'api_key';

  public const TYPE_NONE = 0;
  public const TYPE_ACCOUNT = 1;
  /* @deprecated */
  public const TYPE_APPLICATION = 2;
  /* @deprecated */
  public const TYPE_DAEMON_USER = 3;
  /* @deprecated */
  public const TYPE_DAEMON_APPLICATION = 4;

  public const IDENTIFIER_LENGTH = 16;

  public const KEY_LENGTH = 32;

  protected $table = 'api_keys';

  protected $casts = [
    'allowed_ips' => 'array',
    'user_id' => 'int',
    'last_used_at' => 'datetime',
    'expires_at' => 'datetime',
    self::CREATED_AT => 'datetime',
    self::UPDATED_AT => 'datetime',
    'r_' . AdminAcl::RESOURCE_USERS => 'int',
    'r_' . AdminAcl::RESOURCE_ALLOCATIONS => 'int',
    'r_' . AdminAcl::RESOURCE_DATABASE_HOSTS => 'int',
    'r_' . AdminAcl::RESOURCE_SERVER_DATABASES => 'int',
    'r_' . AdminAcl::RESOURCE_EGGS => 'int',
    'r_' . AdminAcl::RESOURCE_LOCATIONS => 'int',
    'r_' . AdminAcl::RESOURCE_NESTS => 'int',
    'r_' . AdminAcl::RESOURCE_NODES => 'int',
    'r_' . AdminAcl::RESOURCE_SERVERS => 'int',
  ];

  protected $fillable = [
    'identifier',
    'token',
    'allowed_ips',
    'memo',
    'last_used_at',
    'expires_at',
  ];

  protected $hidden = ['token'];

  public static array $validationRules = [
    'user_id' => 'required|exists:users,id',
    'key_type' => 'present|integer|min:0|max:4',
    'identifier' => 'required|string|size:16|unique:api_keys,identifier',
    'token' => 'required|string',
    'memo' => 'required|nullable|string|max:500',
    'allowed_ips' => 'nullable|array',
    'allowed_ips.*' => 'string',
    'last_used_at' => 'nullable|date',
    'expires_at' => 'nullable|date',
    'r_' . AdminAcl::RESOURCE_USERS => 'integer|min:0|max:3',
    'r_' . AdminAcl::RESOURCE_ALLOCATIONS => 'integer|min:0|max:3',
    'r_' . AdminAcl::RESOURCE_DATABASE_HOSTS => 'integer|min:0|max:3',
    'r_' . AdminAcl::RESOURCE_SERVER_DATABASES => 'integer|min:0|max:3',
    'r_' . AdminAcl::RESOURCE_EGGS => 'integer|min:0|max:3',
    'r_' . AdminAcl::RESOURCE_LOCATIONS => 'integer|min:0|max:3',
    'r_' . AdminAcl::RESOURCE_NESTS => 'integer|min:0|max:3',
    'r_' . AdminAcl::RESOURCE_NODES => 'integer|min:0|max:3',
    'r_' . AdminAcl::RESOURCE_SERVERS => 'integer|min:0|max:3',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Required for support with Laravel Sanctum.
   *
   * @see \Laravel\Sanctum\Guard::supportsTokens()
   */
  public function tokenable(): BelongsTo
  {
    return $this->user();
  }

  public static function findToken(string $token): ?self
  {
    $identifier = substr($token, 0, self::IDENTIFIER_LENGTH);

    $model = static::where('identifier', $identifier)->first();
    if (!is_null($model) && decrypt($model->token) === substr($token, strlen($identifier))) {
      return $model;
    }

    return null;
  }

  public static function getPrefixForType(int $type): string
  {
    Assert::oneOf($type, [self::TYPE_ACCOUNT, self::TYPE_APPLICATION]);

    return 'panas_';
  }

  public static function generateTokenIdentifier(int $type): string
  {
    $prefix = self::getPrefixForType($type);

    return $prefix . Str::random(self::IDENTIFIER_LENGTH - strlen($prefix));
  }
}
