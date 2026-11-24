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
 * A user token (avu_…) for one user of the client's external system, by
 * token exchange at POST /auth/token (RFC 8693). The client needs the
 * scope auth.users:exchange and an external system set in Avaro; the user
 * must have been provisioned by the client or linked in Avaro. The token
 * is kept until shortly before it expires (60 minutes).
 *
 *     $credentials = (new ClientCredentials($id, $secret))->forUser($externalUserId);
 */
final class UserCredentials implements Credentials
{
    public const GRANT = 'urn:ietf:params:oauth:grant-type:token-exchange';
    public const SUBJECT_TYPE = 'urn:avaro:params:oauth:token-type:external-user-id';

    private const MARGIN = 60;

    private ?ClientToken $token = null;

    public function __construct(
        private readonly string $clientId,
        #[\SensitiveParameter] private readonly string $secret,
        public readonly string $externalUserId,
        private readonly ?string $scope = null,
        private readonly ?TokenStore $store = null,
    ) {}

    public function authenticate(RequestInterface $request, Transport $transport): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->token($transport)->accessToken);
    }

    public function forget(): void
    {
        $this->token = null;
    }

    /**
     * @throws \Avaro\Sdk\Exception\BadRequest with slug oauth:invalid_grant when the user is unknown or inactive
     */
    public function token(Transport $transport, ?DateTimeImmutable $now = null): ClientToken
    {
        $now ??= new DateTimeImmutable();
        $key = hash('sha256', $transport->baseUrl() . "\n" . $this->clientId . "\nuser\n" . $this->externalUserId . "\n" . ($this->scope ?? ''));
        $this->token ??= $this->store?->get($key);

        if (null !== $this->token && $this->token->expiresAt->getTimestamp() - self::MARGIN > $now->getTimestamp()) {
            return $this->token;
        }

        $form = ['grant_type' => self::GRANT, 'subject_token' => $this->externalUserId, 'subject_token_type' => self::SUBJECT_TYPE];

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
