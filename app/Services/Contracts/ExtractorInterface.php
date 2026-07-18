<?php

namespace App\Services\Contracts;

interface ExtractorInterface
{
    public function extract(string $input): array;
}