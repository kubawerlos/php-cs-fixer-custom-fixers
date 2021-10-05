<?php

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpCsFixerCustomFixers\Analyzer\Analysis;

/**
 * @internal
 */
final class CaseAnalysis
{
    /** @var int */
    private $colonIndex;

    public function __construct(int $colonIndex)
    {
        $this->colonIndex = $colonIndex;
    }

    public function getColonIndex(): int
    {
        return $this->colonIndex;
    }
}
