<?php

declare(strict_types=1);

use CoverageTools\Php\Cobertura\FilterCobertura;

it('removes non-executable packages from Cobertura coverage', function () : void {
    // Arrange
    $input = __DIR__ . '/../Fixtures/Cobertura/non-executable-package.xml';

    $output = tempnam(sys_get_temp_dir(), 'cobertura_');
    expect($output)->not->toBeFalse();

    // Act
    FilterCobertura::run($input, $output);

    $result = file_get_contents($output);

    // Assert
    expect($result)->not->toContain('SchemaProvider.php');
});

it('keeps executable packages from Cobertura coverage', function () : void {
    // Arrange
    $input = __DIR__ . '/../Fixtures/Cobertura/executable-package.xml';

    $output = tempnam(sys_get_temp_dir(), 'cobertura_');
    expect($output)->not->toBeFalse();

    // Act
    FilterCobertura::run($input, $output);

    $result = file_get_contents($output);

    // Assert
    expect($result)->toContain('ErrorsHandler.php');
});

it('keeps only executable packages in mixed packages from Cobertura coverage', function () : void {
    // Arrange
    $input = __DIR__ . '/../Fixtures/Cobertura/mixed-packages.xml';

    $output = tempnam(sys_get_temp_dir(), 'cobertura_');
    expect($output)->not->toBeFalse();

    // Act
    FilterCobertura::run($input, $output);

    $result = file_get_contents($output);

    // Assert
    expect($result)->not->toContain('SchemaProvider.php')
        ->and($result)->toContain('ErrorsHandler.php');
});
