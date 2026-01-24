<?php

use Myleshyson\Mush\Agents\Codex\Codex;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../../artifacts/agents/codex';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

describe('Codex', function () {
    it('returns correct name', function () {
        $agent = new Codex($this->artifactPath);
        expect($agent->name())->toBe('OpenAI Codex');
    });

    it('returns correct paths', function () {
        $agent = new Codex($this->artifactPath);
        expect($agent->guidelines()->path())->toBe('AGENTS.md');
        expect($agent->skills()->path())->toBe('.codex/skills/');
        // Codex does not support MCP
        expect($agent->mcp())->toBeNull();
    });

    it('returns correct detection paths', function () {
        $agent = new Codex($this->artifactPath);
        expect($agent->detectionPaths())->toBe([
            'AGENTS.md',
            '.codex/',
        ]);
    });

    it('detects when AGENTS.md exists', function () {
        file_put_contents("{$this->artifactPath}/AGENTS.md", '# Test');
        $agent = new Codex($this->artifactPath);
        expect($agent->detect())->toBeTrue();
    });

    it('detects when .codex directory exists', function () {
        mkdir("{$this->artifactPath}/.codex", 0777, true);
        $agent = new Codex($this->artifactPath);
        expect($agent->detect())->toBeTrue();
    });

    it('does not support MCP', function () {
        $agent = new Codex($this->artifactPath);
        expect($agent->mcp())->toBeNull();

        // No file should be created since MCP is not supported
        expect(file_exists("{$this->artifactPath}/.codex/mcp.json"))->toBeFalse();
    });

    it('does not support agents', function () {
        $agent = new Codex($this->artifactPath);
        expect($agent->agents())->toBeNull();
    });

    it('does not support commands', function () {
        $agent = new Codex($this->artifactPath);
        expect($agent->commands())->toBeNull();
    });
});
