<?php

use Myleshyson\Mush\Agents\OpenCode\OpenCode;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../../artifacts/agents/opencode';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

describe('OpenCode', function () {
    it('returns correct name', function () {
        $agent = new OpenCode($this->artifactPath);
        expect($agent->name())->toBe('OpenCode');
    });

    it('returns correct paths', function () {
        $agent = new OpenCode($this->artifactPath);
        expect($agent->guidelines()->path())->toBe('AGENTS.md');
        expect($agent->skills()->path())->toBe('.opencode/skills/');
        expect($agent->mcp()->path())->toBe('opencode.json');
    });

    it('returns correct detection paths', function () {
        $agent = new OpenCode($this->artifactPath);
        expect($agent->detectionPaths())->toBe([
            'AGENTS.md',
            'opencode.json',
            '.opencode/',
        ]);
    });

    it('transforms MCP config with local server', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->mcp()->write([
            'database' => [
                'command' => ['npx', '-y', 'server'],
                'env' => ['KEY' => 'value'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        expect($config['mcp']['database']['type'])->toBe('local');
        expect($config['mcp']['database']['command'])->toBe(['npx', '-y', 'server']);
        expect($config['mcp']['database']['environment'])->toBe(['KEY' => 'value']);
    });

    it('transforms MCP config with remote server', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->mcp()->write([
            'remote' => [
                'url' => 'https://example.com/mcp',
                'headers' => ['Auth' => 'token'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        expect($config['mcp']['remote']['type'])->toBe('remote');
        expect($config['mcp']['remote']['url'])->toBe('https://example.com/mcp');
        expect($config['mcp']['remote']['headers'])->toBe(['Auth' => 'token']);
    });

    it('merges MCP config preserving other opencode.json settings', function () {
        file_put_contents("{$this->artifactPath}/opencode.json", json_encode([
            'mcp' => ['existing' => ['type' => 'local', 'command' => ['cmd']]],
            'theme' => 'dark',
        ]));

        $agent = new OpenCode($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        expect($config['mcp']['existing'])->toBe(['type' => 'local', 'command' => ['cmd']]);
        expect($config['mcp']['new']['type'])->toBe('local');
        expect($config['theme'])->toBe('dark');
    });

    it('continues processing after invalid config (not break)', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->mcp()->write([
            'first' => ['command' => ['cmd1']],
            'invalid' => 'not-an-array',
            'third' => ['command' => ['cmd3']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        // Both valid configs should be present - proves continue not break
        expect($config['mcp'])->toHaveKey('first');
        expect($config['mcp'])->toHaveKey('third');
        expect($config['mcp']['first']['type'])->toBe('local');
        expect($config['mcp']['third']['type'])->toBe('local');
    });

    it('includes environment when env is present', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->mcp()->write([
            'with-env' => [
                'command' => ['cmd'],
                'env' => ['VAR' => 'value'],
            ],
            'without-env' => [
                'command' => ['cmd2'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        expect($config['mcp']['with-env']['environment'])->toBe(['VAR' => 'value']);
        expect($config['mcp']['without-env'])->not->toHaveKey('environment');
    });

    it('includes headers when present in remote server', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->mcp()->write([
            'with-headers' => [
                'url' => 'https://example.com/mcp',
                'headers' => ['Auth' => 'token'],
            ],
            'without-headers' => [
                'url' => 'https://other.com/mcp',
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        expect($config['mcp']['with-headers']['headers'])->toBe(['Auth' => 'token']);
        expect($config['mcp']['without-headers'])->not->toHaveKey('headers');
    });

    it('merges when existing file has non-array mcp', function () {
        file_put_contents("{$this->artifactPath}/opencode.json", json_encode([
            'mcp' => 'invalid-string',
            'otherKey' => 'preserved',
        ]));

        $agent = new OpenCode($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        expect($config['mcp']['new']['type'])->toBe('local');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('correctly merges when both existing and new have mcp servers', function () {
        file_put_contents("{$this->artifactPath}/opencode.json", json_encode([
            'mcp' => [
                'existing' => ['type' => 'local', 'command' => ['old'], 'environment' => ['OLD' => 'val']],
            ],
        ]));

        $agent = new OpenCode($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        // Both servers should exist
        expect($config['mcp'])->toHaveKey('existing');
        expect($config['mcp'])->toHaveKey('new');
        expect($config['mcp']['existing']['command'])->toBe(['old']);
        expect($config['mcp']['new']['command'])->toBe(['new-cmd']);
    });

    it('writes empty MCP config as object not array', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->mcp()->write([]);

        $rawContent = file_get_contents("{$this->artifactPath}/opencode.json");
        // Ensure the JSON contains {} not [] for mcp
        expect($rawContent)->toContain('"mcp": {}');

        $config = json_decode($rawContent, true);
        expect($config['mcp'])->toBe([]);
    });

    it('returns correct agents path', function () {
        $agent = new OpenCode($this->artifactPath);
        expect($agent->agents()->path())->toBe('.opencode/agents/');
    });

    it('returns correct commands path', function () {
        $agent = new OpenCode($this->artifactPath);
        expect($agent->commands()->path())->toBe('.opencode/commands/');
    });

    it('writes agents correctly', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->agents()->write([
            'test-agent' => [
                'name' => 'Test Agent',
                'description' => 'A test agent',
                'content' => '# Agent Instructions',
            ],
        ]);

        expect("{$this->artifactPath}/.opencode/agents/test-agent.md")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.opencode/agents/test-agent.md");
        // OpenCode agents only use description, not name in frontmatter
        expect($content)->toContain('description: A test agent');
        expect($content)->toContain('# Agent Instructions');
    });

    it('writes multiple agents', function () {
        $agent = new OpenCode($this->artifactPath);
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

        expect("{$this->artifactPath}/.opencode/agents/agent1.md")->toBeFile();
        expect("{$this->artifactPath}/.opencode/agents/agent2.md")->toBeFile();
    });

    it('writes commands correctly', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->commands()->write([
            'test-command' => [
                'name' => 'test-command',
                'description' => 'A test command',
                'content' => '# Command Instructions',
            ],
        ]);

        expect("{$this->artifactPath}/.opencode/commands/test-command.md")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.opencode/commands/test-command.md");
        expect($content)->toContain('description: A test command');
        expect($content)->toContain('# Command Instructions');
    });

    it('writes multiple commands', function () {
        $agent = new OpenCode($this->artifactPath);
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

        expect("{$this->artifactPath}/.opencode/commands/cmd1.md")->toBeFile();
        expect("{$this->artifactPath}/.opencode/commands/cmd2.md")->toBeFile();
    });

    it('writes commands without description', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->commands()->write([
            'minimal' => [
                'name' => 'minimal',
                'description' => '',
                'content' => 'Just content',
            ],
        ]);

        $content = file_get_contents("{$this->artifactPath}/.opencode/commands/minimal.md");
        expect($content)->not->toContain('description:');
        expect($content)->toContain('Just content');
    });
});
