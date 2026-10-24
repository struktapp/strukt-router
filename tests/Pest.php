<?php

pest()->extend(Tests\TestCase::class)->in('Unit');

/**
 * Runs the routes binary and captures its streams.
 *
 * @param list<string> $arguments Arguments passed to the binary.
 * @return array{0: int, 1: string, 2: string} Exit status, stdout, stderr.
 */
function run_strukt_routes(array $arguments): array
{
    $command = [PHP_BINARY, dirname(__DIR__).'/bin/routes', ...$arguments];

    $pipes = [];
    $process = proc_open($command, [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, dirname(__DIR__));

    expect($process)->toBeResource();

    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $stdout, $stderr];
}
