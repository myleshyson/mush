<?php

use Myleshyson\Mush\Agents\Copilot\Copilot;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../../artifacts/agents/copilot';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

describe('Copilot', function () {
    it('returns correct name', function () {
        $agent = new Copilot($this->artifactPath);
        expect($agent->name())->toBe('GitHub Copilot');
    });

    it('returns correct paths', function () {
        $agent = new Copilot($this->artifactPath);
        expect($agent->guidelines()->path())->toBe('.github/copilot-instructions.md');
        expect($agent->skills()->path())->toBe('.github/skills/');
        expect($agent->mcp()->path())->toBe('.vscode/mcp.json');
    });

    it('returns correct detection paths', function () {
        $agent = new Copilot($this->artifactPath);
        expect($agent->detectionPaths())->toBe([
            '.github/copilot-instructions.md',
            '.vscode/mcp.json',
            '.github/',
        ]);
    });

    it('transforms MCP config with http type for remote', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->mcp()->write([
            'remote' => [
                'url' => 'https://example.com/mcp',
                'headers' => ['Key' => 'val'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.vscode/mcp.json"), true);
        expect($config['servers']['remote']['type'])->toBe('http');
        expect($config['servers']['remote']['url'])->toBe('https://example.com/mcp');
        expect($config['servers']['remote']['headers'])->toBe(['Key' => 'val']);
    });

    it('transforms MCP config with local server', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->mcp()->write([
            'local' => [
                'command' => ['npx', 'server'],
                'env' => ['ENV' => 'val'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.vscode/mcp.json"), true);
        expect($config['servers']['local']['command'])->toBe('npx');
        expect($config['servers']['local']['args'])->toBe(['server']);
        expect($config['servers']['local']['env'])->toBe(['ENV' => 'val']);
    });

    it('merges with existing servers config', function () {
        mkdir("{$this->artifactPath}/.vscode", 0777, true);
        file_put_contents("{$this->artifactPath}/.vscode/mcp.json", json_encode([
            'servers' => ['existing' => ['command' => 'cmd']],
        ]));

        $agent = new Copilot($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.vscode/mcp.json"), true);
        expect($config['servers']['existing'])->toBe(['command' => 'cmd']);
        expect($config['servers']['new']['command'])->toBe('new');
    });

    it('skips non-array configs and continues processing remaining servers', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->mcp()->write([
            'first' => ['command' => ['cmd1']],
            'invalid' => 'not-an-array',
            'third' => ['command' => ['cmd3']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.vscode/mcp.json"), true);
        expect($config['servers'])->toHaveKey('first');
        expect($config['servers'])->toHaveKey('third');
        expect($config['servers'])->not->toHaveKey('invalid');
    });

    it('handles command with single element (no args)', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->mcp()->write([
            'simple' => ['command' => ['just-command']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.vscode/mcp.json"), true);
        expect($config['servers']['simple']['command'])->toBe('just-command');
        expect($config['servers']['simple'])->not->toHaveKey('args');
    });

    it('merges when existing file has non-array servers', function () {
        mkdir("{$this->artifactPath}/.vscode", 0777, true);
        file_put_contents("{$this->artifactPath}/.vscode/mcp.json", json_encode([
            'servers' => 'invalid-string',
            'otherKey' => 'preserved',
        ]));

        $agent = new Copilot($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.vscode/mcp.json"), true);
        expect($config['servers']['new']['command'])->toBe('new-cmd');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('correctly merges when both existing and new have servers', function () {
        mkdir("{$this->artifactPath}/.vscode", 0777, true);
        file_put_contents("{$this->artifactPath}/.vscode/mcp.json", json_encode([
            'servers' => [
                'existing' => ['command' => 'old', 'env' => ['OLD' => 'val']],
            ],
        ]));

        $agent = new Copilot($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd', 'arg']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.vscode/mcp.json"), true);
        // Both servers should exist
        expect($config['servers'])->toHaveKey('existing');
        expect($config['servers'])->toHaveKey('new');
        expect($config['servers']['existing']['command'])->toBe('old');
        expect($config['servers']['new']['command'])->toBe('new-cmd');
        expect($config['servers']['new']['args'])->toBe(['arg']);
    });

    it('includes env when present in local server config', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->mcp()->write([
            'with-env' => [
                'command' => ['cmd'],
                'env' => ['VAR' => 'value'],
            ],
            'without-env' => [
                'command' => ['cmd2'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.vscode/mcp.json"), true);
        expect($config['servers']['with-env']['env'])->toBe(['VAR' => 'value']);
        expect($config['servers']['without-env'])->not->toHaveKey('env');
    });

    it('writes empty MCP config as object not array', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->mcp()->write([]);

        $rawContent = file_get_contents("{$this->artifactPath}/.vscode/mcp.json");
        // Ensure the JSON contains {} not [] for servers
        expect($rawContent)->toContain('"servers": {}');

        $config = json_decode($rawContent, true);
        expect($config['servers'])->toBe([]);
    });

    it('returns correct agents path', function () {
        $agent = new Copilot($this->artifactPath);
        expect($agent->agents()->path())->toBe('.github/agents/');
    });

    it('returns correct commands path', function () {
        $agent = new Copilot($this->artifactPath);
        expect($agent->commands()->path())->toBe('.github/prompts/');
    });

    it('writes agents correctly', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->agents()->write([
            'test-agent' => [
                'name' => 'Test Agent',
                'description' => 'A test agent',
                'content' => '# Agent Instructions',
            ],
        ]);

        expect("{$this->artifactPath}/.github/agents/test-agent.md")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.github/agents/test-agent.md");
        expect($content)->toContain('name: Test Agent');
        expect($content)->toContain('description: A test agent');
        expect($content)->toContain('# Agent Instructions');
    });

    it('writes multiple agents', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->agents()->write([
            'agent1' => [
                'name' => 'Agent One',
                'description' => 'First agent',
                'content' => 'Content 1',
            ],
            'agent2' => [
                'name' => 'Agent Two',
                'description' => 'Second agent',
                'content' => 'Content 2',
            ],
        ]);

        expect("{$this->artifactPath}/.github/agents/agent1.md")->toBeFile();
        expect("{$this->artifactPath}/.github/agents/agent2.md")->toBeFile();
    });

    it('writes agents without name', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->agents()->write([
            'minimal' => [
                'name' => '',
                'description' => 'Required description',
                'content' => 'Just content',
            ],
        ]);

        $content = file_get_contents("{$this->artifactPath}/.github/agents/minimal.md");
        expect($content)->not->toContain('name:');
        expect($content)->toContain('description: Required description');
        expect($content)->toContain('Just content');
    });

    it('writes commands correctly with prompt.md extension', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->commands()->write([
            'test-command' => [
                'name' => 'test-command',
                'description' => 'A test command',
                'content' => '# Command Instructions',
            ],
        ]);

        // Copilot uses .prompt.md extension
        expect("{$this->artifactPath}/.github/prompts/test-command.prompt.md")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.github/prompts/test-command.prompt.md");
        expect($content)->toContain("agent: 'agent'");
        expect($content)->toContain("description: 'A test command'");
        expect($content)->toContain('# Command Instructions');
    });

    it('writes multiple commands', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->commands()->write([
            'cmd1' => [
                'name' => 'cmd1',
                'description' => 'First command',
                'content' => 'Content 1',
            ],
            'cmd2' => [
                'name' => 'cmd2',
                'description' => 'Second command',
                'content' => 'Content 2',
            ],
        ]);

        expect("{$this->artifactPath}/.github/prompts/cmd1.prompt.md")->toBeFile();
        expect("{$this->artifactPath}/.github/prompts/cmd2.prompt.md")->toBeFile();
    });

    it('writes commands without description', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->commands()->write([
            'minimal' => [
                'name' => 'minimal',
                'description' => '',
                'content' => 'Just content',
            ],
        ]);

        $content = file_get_contents("{$this->artifactPath}/.github/prompts/minimal.prompt.md");
        expect($content)->toContain("agent: 'agent'");
        expect($content)->not->toContain('description:');
        expect($content)->toContain('Just content');
    });
});
