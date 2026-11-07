<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Webhooks;

use Avaro\Sdk\Exception\InvalidSignature;
use Avaro\Sdk\Model\Data;
use DateTimeImmutable;
use JsonException;

/**
 * Checks webhooks of Avaro after Standard Webhooks: HMAC-SHA256 over
 * "<webhook-id>.<webhook-timestamp>.<body>" with the webhook secret of
 * the client (whsec_<base64>), at most five minutes old.
 */
final class Webhook
{
    private readonly string $key;

    public function __construct(#[\SensitiveParameter] string $secret, private readonly int $tolerance = 300)
    {
        $decoded = str_starts_with($secret, 'whsec_') ? base64_decode(substr($secret, 6), true) : $secret;
        $this->key = false === $decoded ? '' : $decoded;
    }

    /**
     * The event of a valid webhook.
     *
     * @param array<string, string|list<string>> $headers names in any case
     *
     * @throws InvalidSignature
     */
    public function verify(array $headers, string $body, ?DateTimeImmutable $now = null): WebhookEvent
    {
        $headers = self::headers($headers);
        $id = $headers['webhook-id'] ?? '';
        $timestamp = $headers['webhook-timestamp'] ?? '';
        $signatures = $headers['webhook-signature'] ?? '';

        if ('' === $this->key || '' === $id || !ctype_digit($timestamp) || '' === $signatures) {
            throw new InvalidSignature('The webhook has no valid signature headers.');
        }

        $now ??= new DateTimeImmutable();

        if (abs($now->getTimestamp() - (int) $timestamp) > $this->tolerance) {
            throw new InvalidSignature('The webhook is too old or from the future.');
        }

        $expected = base64_encode(hash_hmac('sha256', $id . '.' . $timestamp . '.' . $body, $this->key, true));
        $valid = false;

        foreach (preg_split('/\s+/', trim($signatures)) ?: [] as $signature) {
            [$version, $value] = array_pad(explode(',', $signature, 2), 2, '');

            if ('v1' === $version && hash_equals($expected, $value)) {
                $valid = true;
            }
        }

        if (!$valid) {
            throw new InvalidSignature('The signature of the webhook is wrong.');
        }

        try {
            $decoded = json_decode($body, true, 32, \JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidSignature('The webhook carries no JSON.');
        }

        if (!\is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidSignature('The webhook carries no JSON object.');
        }

        /** @var array<string, mixed> $decoded */
        $data = new Data($decoded);

        try {
            return new WebhookEvent($id, $data->string('type'), $data->dateTime('timestamp'), $data->data('data')->values);
        } catch (\Avaro\Sdk\Exception\UnexpectedResponse $error) {
            throw new InvalidSignature('The webhook is not an event: ' . $error->getMessage());
        }
    }

    /**
     * Whether the webhook is valid, without the event.
     *
     * @param array<string, string|list<string>> $headers
     */
    public function isValid(array $headers, string $body, ?DateTimeImmutable $now = null): bool
    {
        try {
            $this->verify($headers, $body, $now);

            return true;
        } catch (InvalidSignature) {
            return false;
        }
    }

    /**
     * @param array<string, string|list<string>> $headers
     *
     * @return array<string, string>
     */
    private static function headers(array $headers): array
    {
        $normal = [];

        foreach ($headers as $name => $value) {
            $normal[strtolower($name)] = \is_array($value) ? implode(' ', $value) : $value;
        }

        return $normal;
    }
}
