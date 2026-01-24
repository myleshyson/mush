<?php

use Myleshyson\Mush\Agents\Claude\Claude;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../../artifacts/agents/claude';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

describe('Claude', function () {
    it('returns correct name', function () {
        $agent = new Claude($this->artifactPath);
        expect($agent->name())->toBe('Claude Code');
    });

    it('returns correct paths', function () {
        $agent = new Claude($this->artifactPath);
        expect($agent->guidelines()->path())->toBe('.claude/CLAUDE.md');
        expect($agent->skills()->path())->toBe('.claude/skills/');
        expect($agent->mcp()->path())->toBe('.claude/mcp.json');
    });

    it('returns correct detection paths', function () {
        $agent = new Claude($this->artifactPath);
        expect($agent->detectionPaths())->toBe([
            'CLAUDE.md',
            '.claude/CLAUDE.md',
            '.claude/',
            '.claude/mcp.json',
        ]);
    });

    it('detects when CLAUDE.md exists', function () {
        file_put_contents("{$this->artifactPath}/CLAUDE.md", '# Test');
        $agent = new Claude($this->artifactPath);
        expect($agent->detect())->toBeTrue();
    });

    it('detects when .claude directory exists', function () {
        mkdir("{$this->artifactPath}/.claude", 0777, true);
        $agent = new Claude($this->artifactPath);
        expect($agent->detect())->toBeTrue();
    });

    it('does not detect when no paths exist', function () {
        $agent = new Claude($this->artifactPath);
        expect($agent->detect())->toBeFalse();
    });

    it('writes guidelines correctly', function () {
        $agent = new Claude($this->artifactPath);
        $agent->guidelines()->write('# Test Guidelines');

        expect("{$this->artifactPath}/.claude/CLAUDE.md")->toBeFile();
        expect(file_get_contents("{$this->artifactPath}/.claude/CLAUDE.md"))->toBe('# Test Guidelines');
    });

    it('writes skills correctly', function () {
        $agent = new Claude($this->artifactPath);
        $agent->skills()->write([
            'skill1' => [
                'name' => 'skill1',
                'description' => 'First skill',
                'content' => '# Skill 1',
            ],
            'skill2' => [
                'name' => 'skill2',
                'description' => 'Second skill',
                'content' => '# Skill 2',
            ],
        ]);

        // Skills are written as subdirectories containing SKILL.md files
        expect("{$this->artifactPath}/.claude/skills/skill1/SKILL.md")->toBeFile();
        expect("{$this->artifactPath}/.claude/skills/skill2/SKILL.md")->toBeFile();

        $skill1Content = file_get_contents("{$this->artifactPath}/.claude/skills/skill1/SKILL.md");
        $skill2Content = file_get_contents("{$this->artifactPath}/.claude/skills/skill2/SKILL.md");

        expect($skill1Content)->toContain('name: skill1');
        expect($skill1Content)->toContain('description: First skill');
        expect($skill1Content)->toContain('# Skill 1');
        expect($skill2Content)->toContain('name: skill2');
        expect($skill2Content)->toContain('description: Second skill');
        expect($skill2Content)->toContain('# Skill 2');
    });

    it('transforms MCP config with local server', function () {
        $agent = new Claude($this->artifactPath);
        $agent->mcp()->write([
            'database' => [
                'command' => ['npx', '-y', '@modelcontextprotocol/server-postgres'],
                'env' => ['DB_URL' => 'postgres://localhost'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['database']['command'])->toBe('npx');
        expect($config['mcpServers']['database']['args'])->toBe(['-y', '@modelcontextprotocol/server-postgres']);
        expect($config['mcpServers']['database']['env'])->toBe(['DB_URL' => 'postgres://localhost']);
    });

    it('transforms MCP config with remote server', function () {
        $agent = new Claude($this->artifactPath);
        $agent->mcp()->write([
            'context7' => [
                'url' => 'https://mcp.context7.com/mcp',
                'headers' => ['API_KEY' => 'secret'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['context7']['url'])->toBe('https://mcp.context7.com/mcp');
        expect($config['mcpServers']['context7']['headers'])->toBe(['API_KEY' => 'secret']);
    });

    it('merges MCP config with existing file', function () {
        mkdir("{$this->artifactPath}/.claude", 0777, true);
        file_put_contents("{$this->artifactPath}/.claude/mcp.json", json_encode([
            'mcpServers' => [
                'existing' => ['command' => 'existing-cmd'],
            ],
            'otherKey' => 'preserved',
        ]));

        $agent = new Claude($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['existing'])->toBe(['command' => 'existing-cmd']);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('skips non-array configs and continues processing remaining servers', function () {
        $agent = new Claude($this->artifactPath);
        $agent->mcp()->write([
            'first' => ['command' => ['cmd1']],
            'invalid' => 'not-an-array',
            'third' => ['command' => ['cmd3']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers'])->toHaveKey('first');
        expect($config['mcpServers'])->toHaveKey('third');
        expect($config['mcpServers'])->not->toHaveKey('invalid');
    });

    it('handles command with single element (no args)', function () {
        $agent = new Claude($this->artifactPath);
        $agent->mcp()->write([
            'simple' => ['command' => ['just-command']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['simple']['command'])->toBe('just-command');
        expect($config['mcpServers']['simple'])->not->toHaveKey('args');
    });

    it('handles command as string instead of array', function () {
        $agent = new Claude($this->artifactPath);
        $agent->mcp()->write([
            'string-cmd' => ['command' => 'simple-command'],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['string-cmd']['command'])->toBe('simple-command');
        expect($config['mcpServers']['string-cmd'])->not->toHaveKey('args');
    });

    it('merges when existing file has non-array mcpServers', function () {
        mkdir("{$this->artifactPath}/.claude", 0777, true);
        file_put_contents("{$this->artifactPath}/.claude/mcp.json", json_encode([
            'mcpServers' => 'invalid-string',
            'otherKey' => 'preserved',
        ]));

        $agent = new Claude($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('writes MCP config with env variables', function () {
        $agent = new Claude($this->artifactPath);
        $agent->mcp()->write([
            'db' => [
                'command' => ['psql'],
                'env' => ['DATABASE_URL' => '${DB_URL}'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['db']['env']['DATABASE_URL'])->toBe('${DB_URL}');
    });

    it('writes empty MCP config as object not array', function () {
        $agent = new Claude($this->artifactPath);
        $agent->mcp()->write([]);

        $rawContent = file_get_contents("{$this->artifactPath}/.claude/mcp.json");
        // Ensure the JSON contains {} not [] for mcpServers
        expect($rawContent)->toContain('"mcpServers": {}');

        $config = json_decode($rawContent, true);
        expect($config['mcpServers'])->toBe([]);
    });

    it('returns correct agents path', function () {
        $agent = new Claude($this->artifactPath);
        expect($agent->agents()->path())->toBe('.claude/agents/');
    });

    it('returns correct commands path', function () {
        $agent = new Claude($this->artifactPath);
        expect($agent->commands()->path())->toBe('.claude/commands/');
    });

    it('writes agents correctly', function () {
        $agent = new Claude($this->artifactPath);
        $agent->agents()->write([
            'test-agent' => [
                'name' => 'Test Agent',
                'description' => 'A test agent',
                'content' => '# Agent Instructions',
            ],
        ]);

        expect("{$this->artifactPath}/.claude/agents/test-agent.md")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.claude/agents/test-agent.md");
        expect($content)->toContain('name: Test Agent');
        expect($content)->toContain('description: A test agent');
        expect($content)->toContain('# Agent Instructions');
    });

    it('writes multiple agents', function () {
        $agent = new Claude($this->artifactPath);
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

        expect("{$this->artifactPath}/.claude/agents/agent1.md")->toBeFile();
        expect("{$this->artifactPath}/.claude/agents/agent2.md")->toBeFile();
    });

    it('writes agents without description', function () {
        $agent = new Claude($this->artifactPath);
        $agent->agents()->write([
            'minimal' => [
                'name' => 'Minimal Agent',
                'description' => '',
                'content' => 'Just content',
            ],
        ]);

        $content = file_get_contents("{$this->artifactPath}/.claude/agents/minimal.md");
        expect($content)->toContain('name: Minimal Agent');
        expect($content)->not->toContain('description:');
        expect($content)->toContain('Just content');
    });

    it('writes commands correctly', function () {
        $agent = new Claude($this->artifactPath);
        $agent->commands()->write([
            'test-command' => [
                'name' => 'test-command',
                'description' => 'A test command',
                'content' => '# Command Instructions',
            ],
        ]);

        expect("{$this->artifactPath}/.claude/commands/test-command.md")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.claude/commands/test-command.md");
        expect($content)->toContain('description: A test command');
        expect($content)->toContain('# Command Instructions');
    });

    it('writes multiple commands', function () {
        $agent = new Claude($this->artifactPath);
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

        expect("{$this->artifactPath}/.claude/commands/cmd1.md")->toBeFile();
        expect("{$this->artifactPath}/.claude/commands/cmd2.md")->toBeFile();
    });

    it('writes commands without description', function () {
        $agent = new Claude($this->artifactPath);
        $agent->commands()->write([
            'minimal' => [
                'name' => 'minimal',
                'description' => '',
                'content' => 'Just content',
            ],
        ]);

        $content = file_get_contents("{$this->artifactPath}/.claude/commands/minimal.md");
        expect($content)->not->toContain('description:');
        expect($content)->toContain('Just content');
    });
});
