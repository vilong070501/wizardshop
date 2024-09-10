<?php

namespace App\Data;

class SearchData
{
    public int $page = 1;
    public ?string $q = null;
    public array $categories = [];
    public ?float $max = null;
    public ?float $min = null;
}