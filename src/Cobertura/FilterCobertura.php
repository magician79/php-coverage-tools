<?php

declare(strict_types=1);

namespace CoverageTools\Php\Cobertura;

use DOMDocument;
use DOMElement;
use RuntimeException;

use function is_file;

final class FilterCobertura
{
    public static function run(string $inputFile, string $outputFile) : void
    {
        if (! is_file($inputFile)) {
            throw new RuntimeException('Input file does not exist: ' . $inputFile);
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if ($dom->load($inputFile) === false) {
            throw new RuntimeException('Failed to parse Cobertura XML');
        }

        $packages = $dom->getElementsByTagName('package');

        /** @var DOMElement[] $toRemove */
        $toRemove = [];

        foreach ($packages as $package) {
            if (! self::isNonExecutablePackage($package)) {
                continue;
            }

            $toRemove[] = $package;
        }

        foreach ($toRemove as $package) {
            $package->parentNode?->removeChild($package);
        }

        // Self-check: ensure no non-executable packages remain
        foreach ($dom->getElementsByTagName('package') as $package) {
            if (self::isNonExecutablePackage($package)) {
                throw new RuntimeException(
                    'Non-executable package leaked into filtered coverage: ' .
                    $package->getAttribute('name'),
                );
            }
        }

        if ($dom->save($outputFile) === false) {
            throw new RuntimeException('Failed to write filtered Cobertura file');
        }
    }

    private static function isNonExecutablePackage(DOMElement $package) : bool
    {
        $lineRate = (float) $package->getAttribute('line-rate');
        $complexity = (int) $package->getAttribute('complexity');

        // Fast path: executable by definition
        if ($lineRate > 0.0 || $complexity > 0) {
            return false;
        }

        // Future-proofing: if Cobertura emitted any executable lines, keep it
        if ($package->getElementsByTagName('line')->length > 0) {
            return false;
        }

        // Cobertura marks interfaces / pure contracts as empty <classes/>
        foreach ($package->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'classes') {
                return ! $child->hasChildNodes();
            }
        }

        // Defensive default: keep the package
        return false;
    }
}
