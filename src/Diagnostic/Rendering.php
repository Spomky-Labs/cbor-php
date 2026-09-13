<?php

declare(strict_types=1);

namespace CBOR\Diagnostic;

use function implode;
use Stringable;

/**
 * A document in diagnostic notation, with the CDDL of the productions it instantiates.
 *
 * Cast to a string, it reads the way an RFC appendix does: the productions first, each as a ";" comment line, then
 * the notation.
 */
final class Rendering implements Stringable
{
    /**
     * @param list<string> $cddl the CDDL of each production the document instantiates, in order of first use
     */
    public function __construct(
        private string $notation,
        private array $cddl
    ) {
    }

    public function __toString(): string
    {
        $lines = [];
        foreach ($this->cddl as $production) {
            $lines[] = '; ' . $production;
        }
        $lines[] = $this->notation;

        return implode("\n", $lines);
    }

    public function getNotation(): string
    {
        return $this->notation;
    }

    /**
     * @return list<string>
     */
    public function getCddl(): array
    {
        return $this->cddl;
    }
}
