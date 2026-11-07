<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Tests;

use Avaro\Sdk\Exception\InvalidSignature;
use Avaro\Sdk\Webhooks\Webhook;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase as Base;

final class WebhookTest extends Base
{
    private const SECRET = 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';

    public function testAValidWebhook(): void
    {
        $body = '{"type":"product.updated","timestamp":"2026-11-07T10:00:00Z","data":{"id":"123"}}';
        $headers = self::sign('msg_1', 1794045600, $body);

        $event = (new Webhook(self::SECRET))->verify($headers, $body, new DateTimeImmutable('@1794045660'));

        self::assertSame('msg_1', $event->id);
        self::assertSame('product.updated', $event->type);
        self::assertSame(['id' => '123'], $event->data);
    }

    public function testOneOfSeveralSignaturesIsEnough(): void
    {
        $body = '{"type":"stock.changed","timestamp":"2026-11-07T10:00:00Z","data":{"id":"1"}}';
        $headers = self::sign('msg_2', 1794045600, $body);
        $headers['Webhook-Signature'] = 'v1,d3Jvbmc= ' . $headers['Webhook-Signature'];

        self::assertTrue((new Webhook(self::SECRET))->isValid($headers, $body, new DateTimeImmutable('@1794045600')));
    }

    public function testWrongOrOldWebhooksAreRefused(): void
    {
        $body = '{"type":"product.updated","timestamp":"2026-11-07T10:00:00Z","data":{"id":"123"}}';
        $headers = self::sign('msg_1', 1794045600, $body);
        $webhook = new Webhook(self::SECRET);

        self::assertFalse($webhook->isValid($headers, $body . ' ', new DateTimeImmutable('@1794045600')));
        self::assertFalse($webhook->isValid($headers, $body, new DateTimeImmutable('@1794046000')));
        self::assertFalse($webhook->isValid(['webhook-id' => 'msg_1'], $body, new DateTimeImmutable('@1794045600')));
        self::assertFalse((new Webhook('whsec_b3RoZXI='))->isValid($headers, $body, new DateTimeImmutable('@1794045600')));

        $this->expectException(InvalidSignature::class);
        $webhook->verify(self::sign('msg_3', 1794045600, '[]'), '[]', new DateTimeImmutable('@1794045600'));
    }

    /**
     * @return array<string, string>
     */
    private static function sign(string $id, int $timestamp, string $body): array
    {
        $key = (string) base64_decode(substr(self::SECRET, 6), true);

        return [
            'Webhook-Id' => $id,
            'Webhook-Timestamp' => (string) $timestamp,
            'Webhook-Signature' => 'v1,' . base64_encode(hash_hmac('sha256', $id . '.' . $timestamp . '.' . $body, $key, true)),
        ];
    }
}
