<?php

use Myleshyson\Fusion\Agents\ClaudeCode;
use Myleshyson\Fusion\Agents\Codex;
use Myleshyson\Fusion\Agents\Copilot;
use Myleshyson\Fusion\Agents\Cursor;
use Myleshyson\Fusion\Agents\Gemini;
use Myleshyson\Fusion\Agents\Junie;
use Myleshyson\Fusion\Agents\OpenCode;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../artifacts/agents';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

// ==================== ClaudeCode Tests ====================

describe('ClaudeCode', function () {
    it('returns correct name', function () {
        $agent = new ClaudeCode($this->artifactPath);
        expect($agent->name())->toBe('Claude Code');
    });

    it('returns correct paths', function () {
        $agent = new ClaudeCode($this->artifactPath);
        expect($agent->guidelinesPath())->toBe('.claude/CLAUDE.md');
        expect($agent->skillsPath())->toBe('.claude/skills/');
        expect($agent->mcpPath())->toBe('.claude/mcp.json');
    });

    it('returns correct detection paths', function () {
        $agent = new ClaudeCode($this->artifactPath);
        expect($agent->detectionPaths())->toBe([
            'CLAUDE.md',
            '.claude/CLAUDE.md',
            '.claude/',
            '.claude/mcp.json',
        ]);
    });

    it('detects when CLAUDE.md exists', function () {
        file_put_contents("{$this->artifactPath}/CLAUDE.md", '# Test');
        $agent = new ClaudeCode($this->artifactPath);
        expect($agent->detect())->toBeTrue();
    });

    it('detects when .claude directory exists', function () {
        mkdir("{$this->artifactPath}/.claude", 0777, true);
        $agent = new ClaudeCode($this->artifactPath);
        expect($agent->detect())->toBeTrue();
    });

    it('does not detect when no paths exist', function () {
        $agent = new ClaudeCode($this->artifactPath);
        expect($agent->detect())->toBeFalse();
    });

    it('writes guidelines correctly', function () {
        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeGuidelines('# Test Guidelines');

        expect("{$this->artifactPath}/.claude/CLAUDE.md")->toBeFile();
        expect(file_get_contents("{$this->artifactPath}/.claude/CLAUDE.md"))->toBe('# Test Guidelines');
    });

    it('writes skills correctly', function () {
        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeSkills([
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
        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeMcpConfig([
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
        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeMcpConfig([
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

        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['existing'])->toBe(['command' => 'existing-cmd']);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('skips non-array configs and continues processing remaining servers', function () {
        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeMcpConfig([
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
        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeMcpConfig([
            'simple' => ['command' => ['just-command']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['simple']['command'])->toBe('just-command');
        expect($config['mcpServers']['simple'])->not->toHaveKey('args');
    });

    it('handles command as string instead of array', function () {
        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeMcpConfig([
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

        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('writes MCP config with env variables', function () {
        $agent = new ClaudeCode($this->artifactPath);
        $agent->writeMcpConfig([
            'db' => [
                'command' => ['psql'],
                'env' => ['DATABASE_URL' => '${DB_URL}'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.claude/mcp.json"), true);
        expect($config['mcpServers']['db']['env']['DATABASE_URL'])->toBe('${DB_URL}');
    });
});

// ==================== OpenCode Tests ====================

describe('OpenCode', function () {
    it('returns correct name', function () {
        $agent = new OpenCode($this->artifactPath);
        expect($agent->name())->toBe('OpenCode');
    });

    it('returns correct paths', function () {
        $agent = new OpenCode($this->artifactPath);
        expect($agent->guidelinesPath())->toBe('AGENTS.md');
        expect($agent->skillsPath())->toBe('.opencode/skills/');
        expect($agent->mcpPath())->toBe('opencode.json');
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        expect($config['mcp']['existing'])->toBe(['type' => 'local', 'command' => ['cmd']]);
        expect($config['mcp']['new']['type'])->toBe('local');
        expect($config['theme'])->toBe('dark');
    });

    it('continues processing after invalid config (not break)', function () {
        $agent = new OpenCode($this->artifactPath);
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/opencode.json"), true);
        // Both servers should exist
        expect($config['mcp'])->toHaveKey('existing');
        expect($config['mcp'])->toHaveKey('new');
        expect($config['mcp']['existing']['command'])->toBe(['old']);
        expect($config['mcp']['new']['command'])->toBe(['new-cmd']);
    });
});

// ==================== Cursor Tests ====================

describe('Cursor', function () {
    it('returns correct name', function () {
        $agent = new Cursor($this->artifactPath);
        expect($agent->name())->toBe('Cursor');
    });

    it('returns correct paths', function () {
        $agent = new Cursor($this->artifactPath);
        expect($agent->guidelinesPath())->toBe('.cursorrules');
        expect($agent->skillsPath())->toBe('.cursor/skills/');
        expect($agent->mcpPath())->toBe('.cursor/mcp.json');
    });

    it('returns correct detection paths', function () {
        $agent = new Cursor($this->artifactPath);
        expect($agent->detectionPaths())->toBe([
            '.cursorrules',
            '.cursor/',
            '.cursor/mcp.json',
        ]);
    });

    it('transforms MCP config correctly', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->writeMcpConfig([
            'server' => [
                'command' => ['npx', 'server'],
                'env' => ['KEY' => 'val'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        expect($config['mcpServers']['server']['command'])->toBe('npx');
        expect($config['mcpServers']['server']['args'])->toBe(['server']);
        expect($config['mcpServers']['server']['env'])->toBe(['KEY' => 'val']);
    });

    it('merges MCP config with existing', function () {
        mkdir("{$this->artifactPath}/.cursor", 0777, true);
        file_put_contents("{$this->artifactPath}/.cursor/mcp.json", json_encode([
            'mcpServers' => ['old' => ['command' => 'old']],
        ]));

        $agent = new Cursor($this->artifactPath);
        $agent->writeMcpConfig([
            'new' => ['url' => 'https://example.com'],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        expect($config['mcpServers']['old'])->toBe(['command' => 'old']);
        expect($config['mcpServers']['new']['url'])->toBe('https://example.com');
    });

    it('continues processing after invalid config (not break)', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->writeMcpConfig([
            'first' => ['command' => ['cmd1', 'arg1']],
            'invalid' => 'not-an-array',
            'third' => ['command' => ['cmd3', 'arg3']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        // Both valid configs should be present - proves continue not break
        expect($config['mcpServers'])->toHaveKey('first');
        expect($config['mcpServers'])->toHaveKey('third');
    });

    it('creates args only when command has more than 1 element', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->writeMcpConfig([
            'single' => ['command' => ['only-command']],
            'double' => ['command' => ['cmd', 'arg']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        // Single element: no args key
        expect($config['mcpServers']['single']['command'])->toBe('only-command');
        expect($config['mcpServers']['single'])->not->toHaveKey('args');
        // Two elements: has args
        expect($config['mcpServers']['double']['command'])->toBe('cmd');
        expect($config['mcpServers']['double']['args'])->toBe(['arg']);
    });

    it('includes env when present in local server config', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->writeMcpConfig([
            'with-env' => [
                'command' => ['cmd'],
                'env' => ['VAR' => 'value'],
            ],
            'without-env' => [
                'command' => ['cmd2'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        expect($config['mcpServers']['with-env']['env'])->toBe(['VAR' => 'value']);
        expect($config['mcpServers']['without-env'])->not->toHaveKey('env');
    });

    it('merges when existing file has non-array mcpServers', function () {
        mkdir("{$this->artifactPath}/.cursor", 0777, true);
        file_put_contents("{$this->artifactPath}/.cursor/mcp.json", json_encode([
            'mcpServers' => 'invalid-string',
            'otherKey' => 'preserved',
        ]));

        $agent = new Cursor($this->artifactPath);
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('correctly merges when both existing and new have servers', function () {
        mkdir("{$this->artifactPath}/.cursor", 0777, true);
        file_put_contents("{$this->artifactPath}/.cursor/mcp.json", json_encode([
            'mcpServers' => [
                'existing' => ['command' => 'old', 'env' => ['OLD' => 'val']],
            ],
        ]));

        $agent = new Cursor($this->artifactPath);
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd', 'arg']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        // Both servers should exist
        expect($config['mcpServers'])->toHaveKey('existing');
        expect($config['mcpServers'])->toHaveKey('new');
        expect($config['mcpServers']['existing']['command'])->toBe('old');
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['mcpServers']['new']['args'])->toBe(['arg']);
    });
});

// ==================== Copilot Tests ====================

describe('Copilot', function () {
    it('returns correct name', function () {
        $agent = new Copilot($this->artifactPath);
        expect($agent->name())->toBe('GitHub Copilot');
    });

    it('returns correct paths', function () {
        $agent = new Copilot($this->artifactPath);
        expect($agent->guidelinesPath())->toBe('.github/copilot-instructions.md');
        expect($agent->skillsPath())->toBe('.github/skills/');
        expect($agent->mcpPath())->toBe('.vscode/mcp.json');
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
            'new' => ['command' => ['new']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.vscode/mcp.json"), true);
        expect($config['servers']['existing'])->toBe(['command' => 'cmd']);
        expect($config['servers']['new']['command'])->toBe('new');
    });

    it('skips non-array configs and continues processing remaining servers', function () {
        $agent = new Copilot($this->artifactPath);
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
});

// ==================== Gemini Tests ====================

describe('Gemini', function () {
    it('returns correct name', function () {
        $agent = new Gemini($this->artifactPath);
        expect($agent->name())->toBe('Gemini');
    });

    it('returns correct paths', function () {
        $agent = new Gemini($this->artifactPath);
        expect($agent->guidelinesPath())->toBe('GEMINI.md');
        expect($agent->skillsPath())->toBe('.gemini/skills/');
        expect($agent->mcpPath())->toBe('.gemini/settings.json');
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.gemini/settings.json"), true);
        expect($config['mcpServers']['existing'])->toBe(['command' => 'old']);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherSetting'])->toBeTrue();
    });

    it('continues processing after invalid config (not break)', function () {
        $agent = new Gemini($this->artifactPath);
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
        $agent->writeMcpConfig([
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
});

// ==================== Codex Tests ====================

describe('Codex', function () {
    it('returns correct name', function () {
        $agent = new Codex($this->artifactPath);
        expect($agent->name())->toBe('OpenAI Codex');
    });

    it('returns correct paths', function () {
        $agent = new Codex($this->artifactPath);
        expect($agent->guidelinesPath())->toBe('AGENTS.md');
        expect($agent->skillsPath())->toBe('.codex/skills/');
        expect($agent->mcpPath())->toBe('');
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

    it('does not write MCP config since it is not supported', function () {
        $agent = new Codex($this->artifactPath);
        $agent->writeMcpConfig([
            'db' => [
                'command' => ['npx', 'server'],
                'env' => ['URL' => 'postgres://'],
            ],
        ]);

        // No file should be created since mcpPath is empty
        expect(file_exists("{$this->artifactPath}/.codex/mcp.json"))->toBeFalse();
    });
});

// ==================== Junie Tests ====================

describe('Junie', function () {
    it('returns correct name', function () {
        $agent = new Junie($this->artifactPath);
        expect($agent->name())->toBe('Junie (Junie)');
    });

    it('returns correct paths', function () {
        $agent = new Junie($this->artifactPath);
        expect($agent->guidelinesPath())->toBe('.junie/guidelines.md');
        expect($agent->skillsPath())->toBe('.junie/skills/');
        expect($agent->mcpPath())->toBe('.junie/mcp/mcp.json');
    });

    it('uses default detection paths', function () {
        $agent = new Junie($this->artifactPath);
        expect($agent->detectionPaths())->toBe([
            '.junie/guidelines.md',
            '.junie/mcp/mcp.json',
        ]);
    });

    it('transforms MCP config with local server', function () {
        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'db' => ['command' => ['psql'], 'env' => ['URL' => 'pg://']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        expect($config['mcpServers']['db']['command'])->toBe('psql');
        expect($config['mcpServers']['db']['env'])->toBe(['URL' => 'pg://']);
    });

    it('transforms MCP config with remote server', function () {
        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'remote' => [
                'url' => 'https://api.example.com/mcp',
                'headers' => ['Token' => 'secret'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        expect($config['mcpServers']['remote']['url'])->toBe('https://api.example.com/mcp');
        expect($config['mcpServers']['remote']['headers'])->toBe(['Token' => 'secret']);
    });

    it('skips non-array config values', function () {
        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'valid' => ['command' => ['cmd']],
            'invalid' => 12345,
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        expect($config['mcpServers'])->toHaveKey('valid');
        expect($config['mcpServers'])->not->toHaveKey('invalid');
    });

    it('merges MCP config with existing servers', function () {
        mkdir("{$this->artifactPath}/.junie/mcp", 0777, true);
        file_put_contents("{$this->artifactPath}/.junie/mcp/mcp.json", json_encode([
            'mcpServers' => ['existing' => ['command' => 'old']],
            'settings' => ['key' => 'value'],
        ]));

        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        expect($config['mcpServers']['existing'])->toBe(['command' => 'old']);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['settings'])->toBe(['key' => 'value']);
    });

    it('continues processing after invalid config (not break)', function () {
        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'first' => ['command' => ['cmd1', 'arg1']],
            'invalid' => 'not-an-array',
            'third' => ['command' => ['cmd3', 'arg3']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        // Both valid configs should be present - proves continue not break
        expect($config['mcpServers'])->toHaveKey('first');
        expect($config['mcpServers'])->toHaveKey('third');
        expect($config['mcpServers']['first']['command'])->toBe('cmd1');
        expect($config['mcpServers']['third']['command'])->toBe('cmd3');
    });

    it('creates args only when command has more than 1 element', function () {
        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'single' => ['command' => ['only-command']],
            'double' => ['command' => ['cmd', 'arg']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        // Single element: no args key
        expect($config['mcpServers']['single']['command'])->toBe('only-command');
        expect($config['mcpServers']['single'])->not->toHaveKey('args');
        // Two elements: has args
        expect($config['mcpServers']['double']['command'])->toBe('cmd');
        expect($config['mcpServers']['double']['args'])->toBe(['arg']);
    });

    it('includes env when present in local server config', function () {
        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'with-env' => [
                'command' => ['cmd'],
                'env' => ['VAR' => 'value'],
            ],
            'without-env' => [
                'command' => ['cmd2'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        expect($config['mcpServers']['with-env']['env'])->toBe(['VAR' => 'value']);
        expect($config['mcpServers']['without-env'])->not->toHaveKey('env');
    });

    it('handles command as string instead of array', function () {
        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'string-cmd' => ['command' => 'simple-string'],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        expect($config['mcpServers']['string-cmd']['command'])->toBe('simple-string');
        expect($config['mcpServers']['string-cmd'])->not->toHaveKey('args');
    });

    it('merges when existing file has non-array mcpServers', function () {
        mkdir("{$this->artifactPath}/.junie/mcp", 0777, true);
        file_put_contents("{$this->artifactPath}/.junie/mcp/mcp.json", json_encode([
            'mcpServers' => 'invalid-string',
            'otherKey' => 'preserved',
        ]));

        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('correctly merges when both existing and new have servers', function () {
        mkdir("{$this->artifactPath}/.junie/mcp", 0777, true);
        file_put_contents("{$this->artifactPath}/.junie/mcp/mcp.json", json_encode([
            'mcpServers' => [
                'existing' => ['command' => 'old', 'env' => ['OLD' => 'val']],
            ],
        ]));

        $agent = new Junie($this->artifactPath);
        $agent->writeMcpConfig([
            'new' => ['command' => ['new-cmd', 'arg']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.junie/mcp/mcp.json"), true);
        // Both servers should exist
        expect($config['mcpServers'])->toHaveKey('existing');
        expect($config['mcpServers'])->toHaveKey('new');
        expect($config['mcpServers']['existing']['command'])->toBe('old');
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['mcpServers']['new']['args'])->toBe(['arg']);
    });
});
