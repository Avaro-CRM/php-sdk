<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use Avaro\Sdk\Exception\Unauthorized;
use Avaro\Sdk\Http\Transport;
use DateTimeImmutable;
use Psr\Http\Message\RequestInterface;

/**
 * OAuth client credentials: a bearer token from POST /auth/token, kept
 * until shortly before it expires (15 minutes).
 */
final class ClientCredentials implements Credentials
{
    /** Seconds before the end at which a token counts as expired. */
    private const MARGIN = 60;

    private ?ClientToken $token = null;

    public function __construct(
        private readonly string $clientId,
        #[\SensitiveParameter] private readonly string $secret,
        private readonly ?string $scope = null,
        private readonly ?TokenStore $store = null,
    ) {}

    public function authenticate(RequestInterface $request, Transport $transport): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->token($transport)->accessToken);
    }

    /**
     * Forgets the token, e.g. after it was revoked.
     */
    public function forget(): void
    {
        $this->token = null;
    }

    public function token(Transport $transport, ?DateTimeImmutable $now = null): ClientToken
    {
        $now ??= new DateTimeImmutable();
        $key = hash('sha256', $transport->baseUrl() . "\n" . $this->clientId . "\n" . ($this->scope ?? ''));
        $this->token ??= $this->store?->get($key);

        if (null !== $this->token && $this->token->expiresAt->getTimestamp() - self::MARGIN > $now->getTimestamp()) {
            return $this->token;
        }

        $form = ['grant_type' => 'client_credentials'];

        if (null !== $this->scope) {
            $form['scope'] = $this->scope;
        }

        $data = $transport->sendForm('/auth/token', $form, ['Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->secret)])->data();

        if ('bearer' !== strtolower($data->string('token_type'))) {
            throw new Unauthorized(new \Avaro\Sdk\Exception\Problem(401, 'about:blank', 'Avaro returned no bearer token.'));
        }

        $this->token = new ClientToken($data->string('access_token'), $now->modify('+' . $data->int('expires_in') . ' seconds'), $data->string('scope'));
        $this->store?->put($key, $this->token);

        return $this->token;
    }
}
