<?php

declare(strict_types=1);

namespace CoverageTools\Php\Cobertura;

use Exception;
use RuntimeException;
use SimpleXMLElement;

use function file_get_contents;
use function file_put_contents;
use function fwrite;
use function is_file;
use function sprintf;

use const PHP_EOL;
use const STDERR;

final class FilterCobertura
{
    /** @throws Exception */
    public static function run(string $input, string $output) : void
    {
        if (! is_file($input)) {
            throw new RuntimeException(sprintf('Input file not found: %s', $input));
        }

        $xml = new SimpleXMLElement(file_get_contents($input));

        $removed = 0;

        foreach ($xml->packages->package as $package) {
            foreach ($package->classes->class as $i => $class) {
                if ((int) $class['lines-valid'] !== 0) {
                    continue;
                }

                unset($package->classes->class[$i]);
                $removed++;
            }
        }

        // Self-check: ensure no non-executable files remain
        foreach ($xml->packages->package as $package) {
            foreach ($package->classes->class as $class) {
                if ((int) $class['lines-valid'] === 0) {
                    throw new RuntimeException(
                        sprintf('Non-executable file leaked into filtered coverage: %s', $class['filename']),
                    );
                }
            }
        }

        file_put_contents($output, $xml->asXML());

        fwrite(
            STDERR,
            sprintf('Filtered Cobertura: removed %s non-executable files%s', $removed, PHP_EOL),
        );
    }
}
