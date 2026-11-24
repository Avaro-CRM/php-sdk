<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Todos;

use Avaro\Sdk\Http\Resource;
use Avaro\Sdk\Model\Page;

/**
 * The module todos. Every call acts for one user: use $avaro->asUser($id)
 * for a user of the client's external system (token exchange) or an
 * employee token as BearerToken.
 */
final class Todos extends Resource
{
    public function todos(): TodoList
    {
        return new TodoList($this->transport);
    }

    /**
     * Active members of the tenant a todo can be assigned to, by name.
     *
     * @return Page<UserRef>
     */
    public function assignees(?int $limit = null, ?string $cursor = null): Page
    {
        $data = $this->transport->send('GET', '/todos/assignees', ['limit' => $limit, 'cursor' => $cursor])->data();

        return new Page(array_map(UserRef::fromData(...), $data->list('data')), $data->optString('next'));
    }
}
