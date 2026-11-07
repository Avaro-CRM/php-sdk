<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Auth;

use Avaro\Sdk\Model\Address;
use Avaro\Sdk\Model\Data;

/**
 * The signed-in contact and its customer.
 */
final class Customer
{
    /**
     * @param list<Address> $deliveryAddresses
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $customerNumber,
        public readonly ?string $company,
        public readonly ?string $vatId,
        public readonly ?Address $billingAddress,
        public readonly array $deliveryAddresses,
    ) {}

    public static function fromData(Data $data): self
    {
        $billing = $data->optData('billingAddress');

        return new self(
            $data->string('id'),
            $data->string('name'),
            $data->string('email'),
            $data->string('customerNumber'),
            $data->optString('company'),
            $data->optString('vatId'),
            null === $billing ? null : Address::fromData($billing),
            array_map(Address::fromData(...), $data->list('deliveryAddresses')),
        );
    }
}
