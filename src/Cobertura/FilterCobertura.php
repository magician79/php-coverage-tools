<?php

declare(strict_types=1);

namespace CoverageTools\Php\Cobertura;

use DOMDocument;
use DOMElement;
use RuntimeException;

use function is_file;
use function sprintf;

final class FilterCobertura
{
    public static function run(
        string $inputFile,
        string $outputFile,
    ) : void {
        if (is_file($inputFile) === false) {
            throw new RuntimeException(sprintf('Input file does not exist: %s', $inputFile));
        }

        $dom = self::loadDocument($inputFile);

        self::removeNonExecutablePackages($dom);

        self::assertNoNonExecutablePackagesRemain($dom);

        if ($dom->save($outputFile) === false) {
            throw new RuntimeException('Failed to write filtered Cobertura file');
        }
    }

    private static function loadDocument(
        string $inputFile,
    ) : DOMDocument {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if ($dom->load($inputFile) === false) {
            throw new RuntimeException('Failed to parse Cobertura XML');
        }

        return $dom;
    }

    private static function removeNonExecutablePackages(
        DOMDocument $dom,
    ) : void {
        $packages = $dom->getElementsByTagName('package');
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
    }

    private static function isNonExecutablePackage(
        DOMElement $package,
    ) : bool {
        // Fast path: executable by definition
        if (self::hasExecutableMetrics($package)) {
            return false;
        }

        // Future-proofing: if Cobertura emitted any executable lines, keep it
        if (self::hasExecutableLines($package)) {
            return false;
        }

        // Cobertura marks interfaces / pure contracts as empty <classes/>
        return self::hasEmptyClassesNode($package);
    }

    private static function hasExecutableMetrics(
        DOMElement $package,
    ) : bool {
        return (float) $package->getAttribute('line-rate') > 0.0 ||
            (int) $package->getAttribute('complexity') > 0;
    }

    private static function hasExecutableLines(
        DOMElement $package,
    ) : bool {
        return $package->getElementsByTagName('line')->length > 0;
    }

    private static function hasEmptyClassesNode(
        DOMElement $package,
    ) : bool {
        foreach ($package->childNodes as $child) {
            if (
                $child instanceof DOMElement &&
                $child->tagName === 'classes'
            ) {
                return $child->hasChildNodes() === false;
            }
        }

        // Defensive default: keep the package
        return false;
    }

    private static function assertNoNonExecutablePackagesRemain(
        DOMDocument $dom,
    ) : void {
        foreach ($dom->getElementsByTagName('package') as $package) {
            if (self::isNonExecutablePackage($package)) {
                throw new RuntimeException(
                    sprintf(
                        'Invariant violated: non-executable package present after filtering: %s',
                        $package->getAttribute('name'),
                    ),
                );
            }
        }
    }
}
