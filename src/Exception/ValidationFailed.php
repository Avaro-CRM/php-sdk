<?php

/*
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Avaro\Sdk\Exception;

/**
 * 422 with the errors of each field.
 */
final class ValidationFailed extends ApiException
{
    /**
     * @param list<FieldError> $errors
     */
    public function __construct(Problem $problem, public readonly array $errors = [])
    {
        parent::__construct($problem);
    }

    /**
     * The errors by pointer.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->errors as $error) {
            $messages[$error->pointer] ??= $error->detail;
        }

        return $messages;
    }

    public function has(string $pointer, ?string $code = null): bool
    {
        foreach ($this->errors as $error) {
            if ($error->pointer === $pointer && (null === $code || $error->code === $code)) {
                return true;
            }
        }

        return false;
    }
}
