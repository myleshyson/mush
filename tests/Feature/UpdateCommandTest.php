<?php

use Myleshyson\Fusion\App;
use Myleshyson\Fusion\Commands\UpdateCommand;
use Symfony\Component\Yaml\Yaml;
use Zenstruck\Console\Test\TestCommand;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../artifacts/update-command';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

it('updates agent files from fusion config', function () {
    // Set up .fusion directory with config
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);

    // Write fusion.yaml
    file_put_contents("{$fusionPath}/fusion.yaml", Yaml::dump([
        'agents' => ['claude-code'],
    ]));

    // Write mcp.json
    file_put_contents("{$fusionPath}/mcp.json", json_encode([
        'servers' => [],
    ]));

    // Write a guideline
    file_put_contents("{$fusionPath}/guidelines/test.md", '# Test Guideline');

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath}")
        ->assertSuccessful()
        ->assertOutputContains('Updated Claude Code');
});

it('fails if fusion is not initialized', function () {
    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath}")
        ->assertStatusCode(1)
        ->assertOutputContains('not initialized');
});

it('accepts custom guideline paths', function () {
    // Set up .fusion directory with config
    $fusionPath = "{$this->artifactPath}/.fusion";
    mkdir($fusionPath, 0777, true);
    mkdir("{$fusionPath}/guidelines", 0777, true);
    mkdir("{$fusionPath}/skills", 0777, true);

    file_put_contents("{$fusionPath}/fusion.yaml", Yaml::dump([
        'agents' => ['claude-code'],
    ]));
    file_put_contents("{$fusionPath}/mcp.json", json_encode(['servers' => []]));

    $command = new UpdateCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --guideline-path=./custom/RULES.md")
        ->assertSuccessful()
        ->assertOutputContains('custom guideline path');
});
