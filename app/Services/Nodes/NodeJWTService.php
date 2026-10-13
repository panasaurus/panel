<?php

namespace Pterodactyl\Services\Nodes;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Enums\Daemon\JwtScope;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Pterodactyl\Extensions\Lcobucci\JWT\Encoding\TimestampDates;

class NodeJWTService
{
    private array $claims = [];

    private array $scopes;

    private ?User $user = null;

    private ?\DateTimeImmutable $expiresAt;

    private ?string $subject = null;

    public function setClaims(array $claims): self
    {
        $this->claims = $claims;

        return $this;
    }

    public function setScopes(JwtScope ...$scopes): self
    {
        $this->scopes = $scopes;

        return $this;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setExpiresAt(\DateTimeImmutable $date): self
    {
        $this->expiresAt = $date;

        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public static function identifier(?string $identifiedBy, string $algo = 'sha256'): string
    {
        return hash($algo, $identifiedBy ?? '');
    }

    public function handle(Node $node, ?string $identifiedBy, string $algo = 'sha256'): Plain
    {
        $identifier = self::identifier($identifiedBy, $algo);
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($node->getDecryptedKey()));

        $builder = $config->builder(new TimestampDates())
            ->issuedBy(config('app.url'))
            ->permittedFor($node->getConnectionAddress())
            ->identifiedBy($identifier)
            ->withHeader('jti', $identifier)
            ->issuedAt(CarbonImmutable::now())
            ->canOnlyBeUsedAfter(CarbonImmutable::now()->subMinutes(5));

        if ($this->expiresAt) {
            $builder = $builder->expiresAt($this->expiresAt);
        }

        if (!empty($this->subject)) {
            $builder = $builder->relatedTo($this->subject)->withHeader('sub', $this->subject);
        }

        foreach ($this->claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        $builder = $builder->withClaim('scope', implode(' ', array_map(fn ($scope) => $scope->value, $this->scopes)));

        if (!is_null($this->user)) {
            $builder = $builder
                ->withClaim('user_uuid', $this->user->uuid)

                // This claim will be removed in Panel@1.11 or later.
                ->withClaim('user_id', $this->user->id);
        }

        return $builder
            ->withClaim('unique_id', Str::random())
            ->getToken($config->signer(), $config->signingKey());
    }
}
