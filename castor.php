<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;
use function Castor\io;
use function Castor\run;

#[AsTask(description: 'Run mutation testing')]
function infect(int $minMsi = 0, int $minCoveredMsi = 0, bool $ci = false): void
{
    io()->title('Running infection');
    $nproc = run('nproc', quiet: true);
    if (! $nproc->isSuccessful()) {
        io()->error('Cannot determine the number of processors');
        return;
    }
    $threads = (int) $nproc->getOutput();
    $command = [
        'php',
        'vendor/bin/infection',
        sprintf('--min-msi=%s', $minMsi),
        sprintf('--min-covered-msi=%s', $minCoveredMsi),
        sprintf('--threads=%s', $threads),
    ];
    if ($ci) {
        $command[] = '--logger-github';
        $command[] = '-s';
    }
    $environment = [
        'XDEBUG_MODE' => 'coverage',
    ];
    run($command, environment: $environment);
}

#[AsTask(description: 'Run tests')]
function test(bool $coverageHtml = false, bool $coverageText = false, null|string $group = null): void
{
    io()->title('Running tests');
    $command = ['php', 'vendor/bin/phpunit', '--color'];
    $environment = [
        'XDEBUG_MODE' => 'off',
    ];
    if ($coverageHtml) {
        $command[] = '--coverage-html=build/coverage';
        $environment['XDEBUG_MODE'] = 'coverage';
    }
    if ($coverageText) {
        $command[] = '--coverage-text';
        $environment['XDEBUG_MODE'] = 'coverage';
    }
    if ($group !== null) {
        $command[] = sprintf('--group=%s', $group);
    }
    run($command, environment: $environment);
}

#[AsTask(description: 'Coding standards check')]
function cs(bool $fix = false): void
{
    io()->title('Running coding standards check');
    $command = ['php', 'vendor/bin/ecs', 'check'];
    $environment = [
        'XDEBUG_MODE' => 'off',
    ];
    if ($fix) {
        $command[] = '--fix';
    }
    run($command, environment: $environment);
}

#[AsTask(description: 'Running PHPStan')]
function stan(): void
{
    io()->title('Running PHPStan');
    $command = ['php', 'vendor/bin/phpstan', 'analyse'];
    $environment = [
        'XDEBUG_MODE' => 'off',
    ];
    run($command, environment: $environment);
}

#[AsTask(description: 'Validate Composer configuration')]
function validate(): void
{
    io()->title('Validating Composer configuration');
    $command = ['composer', 'validate'];
    $environment = [
        'XDEBUG_MODE' => 'off',
    ];
    run($command, environment: $environment);
}

#[AsTask(description: 'Run Rector')]
function rector(bool $fix = false): void
{
    io()->title('Running Rector');
    $command = ['php', 'vendor/bin/rector', 'process', '--ansi'];
    if (! $fix) {
        $command[] = '--dry-run';
    }
    $environment = [
        'XDEBUG_MODE' => 'off',
    ];
    run($command, environment: $environment);
}

#[AsTask(description: 'Run Rector')]
function deptrac(): void
{
    io()->title('Running Rector');
    $command = ['php', 'vendor/bin/deptrac', 'analyse', '--fail-on-uncovered', '--no-cache'];
    $environment = [
        'XDEBUG_MODE' => 'off',
    ];
    run($command, environment: $environment);
}
