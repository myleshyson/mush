<?php

use Myleshyson\Fusion\Support\McpConfigReader;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../artifacts/mcp-reader';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

describe('McpConfigReader', function () {
    it('returns empty array when no mcp.json exists', function () {
        $result = McpConfigReader::read($this->artifactPath);
        expect($result)->toBe([]);
    });

    it('reads servers from mcp.json', function () {
        file_put_contents("{$this->artifactPath}/mcp.json", json_encode([
            'servers' => [
                'github' => ['command' => ['npx', '-y', '@mcp/server-github']],
                'postgres' => ['command' => ['npx', '-y', '@mcp/server-postgres']],
            ],
        ]));

        $result = McpConfigReader::read($this->artifactPath);

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('github');
        expect($result)->toHaveKey('postgres');
    });

    it('returns empty array when mcp.json has invalid JSON', function () {
        file_put_contents("{$this->artifactPath}/mcp.json", 'not valid json');

        $result = McpConfigReader::read($this->artifactPath);

        expect($result)->toBe([]);
    });

    it('returns empty array when mcp.json has no servers key', function () {
        file_put_contents("{$this->artifactPath}/mcp.json", json_encode([
            'other' => 'data',
        ]));

        $result = McpConfigReader::read($this->artifactPath);

        expect($result)->toBe([]);
    });

    it('reads servers from mcp.override.json when it exists', function () {
        file_put_contents("{$this->artifactPath}/mcp.override.json", json_encode([
            'servers' => [
                'local-db' => ['command' => ['npx', '-y', '@mcp/server-postgres']],
            ],
        ]));

        $result = McpConfigReader::read($this->artifactPath);

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('local-db');
    });

    it('merges mcp.json and mcp.override.json', function () {
        file_put_contents("{$this->artifactPath}/mcp.json", json_encode([
            'servers' => [
                'github' => ['command' => ['npx', '-y', '@mcp/server-github']],
                'postgres' => ['command' => ['npx', '-y', '@mcp/server-postgres']],
            ],
        ]));

        file_put_contents("{$this->artifactPath}/mcp.override.json", json_encode([
            'servers' => [
                'local-db' => ['command' => ['npx', '-y', '@mcp/server-postgres'], 'env' => ['DATABASE_URL' => 'local']],
            ],
        ]));

        $result = McpConfigReader::read($this->artifactPath);

        expect($result)->toHaveCount(3);
        expect($result)->toHaveKey('github');
        expect($result)->toHaveKey('postgres');
        expect($result)->toHaveKey('local-db');
    });

    it('override completely replaces matching servers', function () {
        file_put_contents("{$this->artifactPath}/mcp.json", json_encode([
            'servers' => [
                'github' => [
                    'command' => ['npx', '-y', '@mcp/server-github'],
                    'env' => ['GITHUB_TOKEN' => 'shared-token'],
                ],
            ],
        ]));

        file_put_contents("{$this->artifactPath}/mcp.override.json", json_encode([
            'servers' => [
                'github' => [
                    'command' => ['npx', '-y', '@mcp/server-github'],
                    'env' => ['GITHUB_TOKEN' => 'my-personal-token'],
                ],
            ],
        ]));

        $result = McpConfigReader::read($this->artifactPath);

        expect($result)->toHaveCount(1);
        expect($result['github']['env']['GITHUB_TOKEN'])->toBe('my-personal-token');
    });

    it('override can remove env from base server', function () {
        file_put_contents("{$this->artifactPath}/mcp.json", json_encode([
            'servers' => [
                'github' => [
                    'command' => ['npx', '-y', '@mcp/server-github'],
                    'env' => ['GITHUB_TOKEN' => 'shared-token'],
                ],
            ],
        ]));

        file_put_contents("{$this->artifactPath}/mcp.override.json", json_encode([
            'servers' => [
                'github' => [
                    'command' => ['npx', '-y', '@mcp/server-github'],
                ],
            ],
        ]));

        $result = McpConfigReader::read($this->artifactPath);

        expect($result['github'])->not->toHaveKey('env');
    });

    it('ignores invalid mcp.override.json', function () {
        file_put_contents("{$this->artifactPath}/mcp.json", json_encode([
            'servers' => [
                'github' => ['command' => ['npx', '-y', '@mcp/server-github']],
            ],
        ]));

        file_put_contents("{$this->artifactPath}/mcp.override.json", 'invalid json');

        $result = McpConfigReader::read($this->artifactPath);

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('github');
    });
});
