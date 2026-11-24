<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Crm;

use Avaro\Sdk\Model\Data;
use Avaro\Sdk\Todos\ContactRef;

/**
 * Someone taking part: role From, To, Cc, Author or Attendee; contact when
 * Avaro knows the person as a contact of an address.
 */
final class Participant
{
    public function __construct(
        public readonly string $role,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?ContactRef $contact,
    ) {}

    public static function fromData(Data $data): self
    {
        $contact = $data->optData('contact');

        return new self($data->string('role'), $data->optString('name'), $data->optString('email'), null === $contact ? null : ContactRef::fromData($contact));
    }
}
