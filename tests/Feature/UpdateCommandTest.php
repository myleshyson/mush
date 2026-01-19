<?php

use Myleshyson\Fusion\App;
use Myleshyson\Fusion\Commands\UpdateCommand;
use Zenstruck\Console\Test\TestCommand;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../artifacts/update-command';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

it('updates agent files based on auto-detection', function () {
    // Set up .fusion directory
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);

    // Write mcp.json
    file_put_contents("{$fusionPath}/mcp.json", json_encode([
        'servers' => [],
    ]));

    // Write a guideline
    file_put_contents("{$fusionPath}/guidelines/test.md", '# Test Guideline');

    // Create an existing Claude Code config file to trigger detection
    mkdir("{$this->artifactPath}/.claude", 0777, true);
    file_put_contents("{$this->artifactPath}/.claude/CLAUDE.md", '# Placeholder');

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath}")
        ->assertSuccessful()
        ->assertOutputContains('Updated Claude Code');

    // Verify the guideline content was written
    $content = file_get_contents("{$this->artifactPath}/.claude/CLAUDE.md");
    expect($content)->toContain('Test Guideline');
});

it('lists available skills in agent guidelines file', function () {
    // Set up .fusion directory
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);
    file_put_contents("{$fusionPath}/mcp.json", json_encode(['servers' => []]));

    // Create skills using subdirectory structure
    mkdir("{$fusionPath}/skills/tailwind", 0777, true);
    mkdir("{$fusionPath}/skills/testing", 0777, true);
    file_put_contents("{$fusionPath}/skills/tailwind/SKILL.md", '# Tailwind CSS skill content');
    file_put_contents("{$fusionPath}/skills/testing/SKILL.md", '# Testing skill content');

    // Create an existing Claude Code config file to trigger detection
    mkdir("{$this->artifactPath}/.claude", 0777, true);
    file_put_contents("{$this->artifactPath}/.claude/CLAUDE.md", '# Placeholder');

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath}")
        ->assertSuccessful();

    // Verify skills are listed by name in the guidelines file
    $content = file_get_contents("{$this->artifactPath}/.claude/CLAUDE.md");
    expect($content)->toContain('Available skills:');
    expect($content)->toContain('- tailwind');
    expect($content)->toContain('- testing');

    // Verify full skill content is NOT in the guidelines file
    expect($content)->not->toContain('Tailwind CSS skill content');
    expect($content)->not->toContain('Testing skill content');
});

it('fails if fusion is not initialized', function () {
    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath}")
        ->assertStatusCode(1)
        ->assertOutputContains('not initialized');
});

it('fails if no agents are detected', function () {
    // Set up .fusion directory but no agent files
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);
    file_put_contents("{$fusionPath}/mcp.json", json_encode(['servers' => []]));

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath}")
        ->assertStatusCode(1)
        ->assertOutputContains('No agents detected');
});

it('accepts custom guideline paths', function () {
    // Set up .fusion directory
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);
    file_put_contents("{$fusionPath}/mcp.json", json_encode(['servers' => []]));

    // Create an existing Cursor config file to trigger detection
    file_put_contents("{$this->artifactPath}/.cursorrules", '# Placeholder');

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --guideline-path=./custom/RULES.md")
        ->assertSuccessful()
        ->assertOutputContains('custom guideline path');
});

it('detects multiple agents', function () {
    // Set up .fusion directory
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);
    file_put_contents("{$fusionPath}/mcp.json", json_encode(['servers' => []]));

    // Create config files for multiple agents
    mkdir("{$this->artifactPath}/.claude", 0777, true);
    file_put_contents("{$this->artifactPath}/.claude/CLAUDE.md", '# Placeholder');
    file_put_contents("{$this->artifactPath}/.cursorrules", '# Placeholder');

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath}")
        ->assertSuccessful()
        ->assertOutputContains('Updated Claude Code')
        ->assertOutputContains('Updated Cursor');
});

it('accepts custom skill paths', function () {
    // Set up .fusion directory
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);
    file_put_contents("{$fusionPath}/mcp.json", json_encode(['servers' => []]));

    // Write a skill using the subdirectory structure
    mkdir("{$fusionPath}/skills/my-skill", 0777, true);
    file_put_contents("{$fusionPath}/skills/my-skill/SKILL.md", '# My Skill Content');

    // Create an existing Cursor config file to trigger detection
    file_put_contents("{$this->artifactPath}/.cursorrules", '# Placeholder');

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --skill-path=./custom/skills/")
        ->assertSuccessful()
        ->assertOutputContains('custom skill path');

    // Verify skill was written to custom path with subdirectory structure
    expect("{$this->artifactPath}/custom/skills/my-skill/SKILL.md")->toBeFile();
    expect(file_get_contents("{$this->artifactPath}/custom/skills/my-skill/SKILL.md"))->toContain('My Skill Content');
});

it('accepts custom MCP paths', function () {
    // Set up .fusion directory
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);
    file_put_contents("{$fusionPath}/mcp.json", json_encode([
        'servers' => [
            'test-server' => [
                'command' => ['npx', 'test-server'],
                'env' => ['KEY' => 'value'],
            ],
        ],
    ]));

    // Create an existing Cursor config file to trigger detection
    file_put_contents("{$this->artifactPath}/.cursorrules", '# Placeholder');

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --mcp-path=./custom/mcp.json")
        ->assertSuccessful()
        ->assertOutputContains('custom MCP path');

    // Verify MCP config was written to custom path
    expect("{$this->artifactPath}/custom/mcp.json")->toBeFile();
    $config = json_decode(file_get_contents("{$this->artifactPath}/custom/mcp.json"), true);
    expect($config['mcpServers']['test-server']['command'])->toBe('npx');
});

it('merges custom MCP path with existing file', function () {
    // Set up .fusion directory
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);
    file_put_contents("{$fusionPath}/mcp.json", json_encode([
        'servers' => [
            'new-server' => ['command' => ['npx', 'new']],
        ],
    ]));

    // Create existing custom MCP file
    mkdir("{$this->artifactPath}/custom", 0777, true);
    file_put_contents("{$this->artifactPath}/custom/mcp.json", json_encode([
        'mcpServers' => ['existing' => ['command' => 'old']],
        'otherSetting' => true,
    ]));

    // Create an existing Cursor config file to trigger detection
    file_put_contents("{$this->artifactPath}/.cursorrules", '# Placeholder');

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --mcp-path=./custom/mcp.json")
        ->assertSuccessful();

    // Verify merge happened correctly
    $config = json_decode(file_get_contents("{$this->artifactPath}/custom/mcp.json"), true);
    expect($config['mcpServers']['existing'])->toBe(['command' => 'old']);
    expect($config['mcpServers']['new-server']['command'])->toBe('npx');
    expect($config['otherSetting'])->toBeTrue();
});

it('handles remote servers in custom MCP paths', function () {
    // Set up .fusion directory
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);
    file_put_contents("{$fusionPath}/mcp.json", json_encode([
        'servers' => [
            'remote-server' => [
                'url' => 'https://api.example.com/mcp',
                'headers' => ['Auth' => 'token'],
            ],
        ],
    ]));

    // Create an existing Cursor config file to trigger detection
    file_put_contents("{$this->artifactPath}/.cursorrules", '# Placeholder');

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --mcp-path=./custom/mcp.json")
        ->assertSuccessful();

    // Verify remote server was written correctly
    $config = json_decode(file_get_contents("{$this->artifactPath}/custom/mcp.json"), true);
    expect($config['mcpServers']['remote-server']['url'])->toBe('https://api.example.com/mcp');
    expect($config['mcpServers']['remote-server']['headers'])->toBe(['Auth' => 'token']);
});

it('handles absolute paths for custom paths', function () {
    // Set up .fusion directory
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);
    file_put_contents("{$fusionPath}/mcp.json", json_encode(['servers' => []]));
    file_put_contents("{$fusionPath}/guidelines/test.md", '# Test');

    // Create an existing Cursor config file to trigger detection
    file_put_contents("{$this->artifactPath}/.cursorrules", '# Placeholder');

    // Use absolute path for custom guideline
    $absolutePath = "{$this->artifactPath}/absolute/RULES.md";

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --guideline-path={$absolutePath}")
        ->assertSuccessful();

    expect($absolutePath)->toBeFile();
});
