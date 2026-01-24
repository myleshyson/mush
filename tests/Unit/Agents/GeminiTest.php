<?php

use Myleshyson\Mush\Agents\Gemini\Gemini;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../../artifacts/agents/gemini';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

describe('Gemini', function () {
    it('returns correct name', function () {
        $agent = new Gemini($this->artifactPath);
        expect($agent->name())->toBe('Gemini');
    });

    it('returns correct paths', function () {
        $agent = new Gemini($this->artifactPath);
        expect($agent->guidelines()->path())->toBe('GEMINI.md');
        expect($agent->skills()->path())->toBe('.gemini/skills/');
        expect($agent->mcp()->path())->toBe('.gemini/settings.json');
    });

    it('uses default detection paths', function () {
        $agent = new Gemini($this->artifactPath);
        expect($agent->detectionPaths())->toBe([
            'GEMINI.md',
            '.gemini/settings.json',
        ]);
    });

    it('detects when GEMINI.md exists', function () {
        file_put_contents("{$this->artifactPath}/GEMINI.md", '# Test');
        $agent = new Gemini($this->artifactPath);
        expect($agent->detect())->toBeTrue();
    });

    it('transforms MCP config correctly', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'server' => [
                'command' => ['cmd', 'arg'],
                'env' => ['K' => 'V'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        expect($config['mcpServers']['server']['command'])->toBe('cmd');
        expect($config['mcpServers']['server']['args'])->toBe(['arg']);
    });

    it('transforms MCP config with remote server', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'remote' => [
                'url' => 'https://example.com/mcp',
                'headers' => ['Auth' => 'token'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        expect($config['mcpServers']['remote']['url'])->toBe('https://example.com/mcp');
        expect($config['mcpServers']['remote']['headers'])->toBe(['Auth' => 'token']);
    });

    it('skips non-array config values', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'valid' => ['command' => ['cmd']],
            'invalid' => 'not-an-array',
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        expect($config['mcpServers'])->toHaveKey('valid');
        expect($config['mcpServers'])->not->toHaveKey('invalid');
    });

    it('merges MCP config with existing settings', function () {
        mkdir("{$this->artifactPath}/.gemini", 0777, true);
        file_put_contents("{$this->artifactPath}/.gemini/settings.json", json_encode([
            'mcpServers' => ['existing' => ['command' => 'old']],
            'otherSetting' => true,
        ]));

        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        expect($config['mcpServers']['existing'])->toBe(['command' => 'old']);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherSetting'])->toBeTrue();
    });

    it('continues processing after invalid config (not break)', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'first' => ['command' => ['cmd1', 'arg1']],
            'invalid' => 'not-an-array',
            'third' => ['command' => ['cmd3', 'arg3']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        // Both valid configs should be present - proves continue not break
        expect($config['mcpServers'])->toHaveKey('first');
        expect($config['mcpServers'])->toHaveKey('third');
        expect($config['mcpServers']['first']['command'])->toBe('cmd1');
        expect($config['mcpServers']['third']['command'])->toBe('cmd3');
    });

    it('creates args only when command has more than 1 element', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'single' => ['command' => ['only-command']],
            'double' => ['command' => ['cmd', 'arg']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        // Single element: no args key
        expect($config['mcpServers']['single']['command'])->toBe('only-command');
        expect($config['mcpServers']['single'])->not->toHaveKey('args');
        // Two elements: has args
        expect($config['mcpServers']['double']['command'])->toBe('cmd');
        expect($config['mcpServers']['double']['args'])->toBe(['arg']);
    });

    it('includes env when present in local server config', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'with-env' => [
                'command' => ['cmd'],
                'env' => ['VAR' => 'value'],
            ],
            'without-env' => [
                'command' => ['cmd2'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        expect($config['mcpServers']['with-env']['env'])->toBe(['VAR' => 'value']);
        expect($config['mcpServers']['without-env'])->not->toHaveKey('env');
    });

    it('handles command as string instead of array', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'string-cmd' => ['command' => 'simple-string'],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        expect($config['mcpServers']['string-cmd']['command'])->toBe('simple-string');
        expect($config['mcpServers']['string-cmd'])->not->toHaveKey('args');
    });

    it('merges when existing file has non-array mcpServers', function () {
        mkdir("{$this->artifactPath}/.gemini", 0777, true);
        file_put_contents("{$this->artifactPath}/.gemini/settings.json", json_encode([
            'mcpServers' => 'invalid-string',
            'otherKey' => 'preserved',
        ]));

        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('correctly merges when both existing and new have servers', function () {
        mkdir("{$this->artifactPath}/.gemini", 0777, true);
        file_put_contents("{$this->artifactPath}/.gemini/settings.json", json_encode([
            'mcpServers' => [
                'existing' => ['command' => 'old', 'env' => ['OLD' => 'val']],
            ],
        ]));

        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd', 'arg']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        // Both servers should exist
        expect($config['mcpServers'])->toHaveKey('existing');
        expect($config['mcpServers'])->toHaveKey('new');
        expect($config['mcpServers']['existing']['command'])->toBe('old');
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['mcpServers']['new']['args'])->toBe(['arg']);
    });

    it('writes empty MCP config as object not array', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->mcp()->write([]);

        $rawContent = file_get_contents("{$this->artifactPath}/.gemini/settings.json");
        // Ensure the JSON contains {} not [] for mcpServers
        expect($rawContent)->toContain('"mcpServers": {}');

        $config = json_decode($rawContent, true);
        expect($config['mcpServers'])->toBe([]);
    });

    it('does not support agents', function () {
        $agent = new Gemini($this->artifactPath);
        expect($agent->agents())->toBeNull();
    });

    it('returns correct commands path', function () {
        $agent = new Gemini($this->artifactPath);
        expect($agent->commands()->path())->toBe('.gemini/commands/');
    });

    it('writes commands correctly with TOML format', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->commands()->write([
            'test-command' => [
                'name' => 'test-command',
                'description' => 'A test command',
                'content' => 'Do something specific',
            ],
        ]);

        // Gemini uses .toml extension
        expect("{$this->artifactPath}/.gemini/commands/test-command.toml")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.gemini/commands/test-command.toml");
        expect($content)->toContain('description = "A test command"');
        expect($content)->toContain('prompt = """');
        expect($content)->toContain('Do something specific');
    });

    it('writes multiple commands', function () {
        $agent = new Gemini($this->artifactPath);
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

        expect("{$this->artifactPath}/.gemini/commands/cmd1.toml")->toBeFile();
        expect("{$this->artifactPath}/.gemini/commands/cmd2.toml")->toBeFile();
    });

    it('writes commands without description', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->commands()->write([
            'minimal' => [
                'name' => 'minimal',
                'description' => '',
                'content' => 'Just content',
            ],
        ]);

        $content = file_get_contents("{$this->artifactPath}/.gemini/commands/minimal.toml");
        expect($content)->not->toContain('description =');
        expect($content)->toContain('prompt = """');
        expect($content)->toContain('Just content');
    });

    it('escapes special characters in TOML description', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->commands()->write([
            'escaped' => [
                'name' => 'escaped',
                'description' => 'Contains "quotes" and \\backslash',
                'content' => 'Content',
            ],
        ]);

        $content = file_get_contents("{$this->artifactPath}/.gemini/commands/escaped.toml");
        expect($content)->toContain('description = "Contains \\"quotes\\" and \\\\backslash"');
    });
});
