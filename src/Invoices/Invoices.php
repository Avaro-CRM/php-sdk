<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Invoices;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Model\Page;

/**
 * Invoices and credit notes of the signed-in customer, newest first.
 */
final class Invoices extends Resource
{
    /**
     * @return Page<Invoice>
     */
    public function list(?int $limit = null, ?string $cursor = null): Page
    {
        $data = $this->transport->send('GET', '/invoices/invoices', ['limit' => $limit, 'cursor' => $cursor], customerToken: $this->customer())->data();

        return new Page(array_map(Invoice::fromData(...), $data->list('data')), $data->optString('next'));
    }

    /**
     * @throws \Avaro\Sdk\Exception\NotFound
     */
    public function pdf(string $id): Pdf
    {
        $result = $this->transport->send('GET', '/invoices/invoices/' . self::segment($id) . '/pdf', headers: ['Accept' => 'application/pdf, application/problem+json'], customerToken: $this->customer());
        $name = null;

        if (1 === preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $result->header('Content-Disposition') ?? '', $match)) {
            $name = basename(rawurldecode($match[1]));
        }

        return new Pdf($name ?? 'invoice-' . $id . '.pdf', $result->body);
    }
}
