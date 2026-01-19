<?php

use Myleshyson\Fusion\App;
use Myleshyson\Fusion\Commands\InstallCommand;
use Zenstruck\Console\Test\TestCommand;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../artifacts/install-command';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

it('initializes a fusion project correctly', function () {
    $command = new InstallCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --agent=claude-code")
        ->assertSuccessful()
        ->assertOutputContains('Fusion initialized successfully!');

    // Verify .fusion directory structure
    expect("{$this->artifactPath}/.fusion")->toBeDirectory();
    expect("{$this->artifactPath}/.fusion/fusion.yaml")->toBeFile();
    expect("{$this->artifactPath}/.fusion/guidelines")->toBeDirectory();
    expect("{$this->artifactPath}/.fusion/guidelines/.gitignore")->toBeFile();
    expect("{$this->artifactPath}/.fusion/skills")->toBeDirectory();
    expect("{$this->artifactPath}/.fusion/skills/.gitignore")->toBeFile();
    expect("{$this->artifactPath}/.fusion/mcp.json")->toBeFile();
});

it('fails if fusion is already initialized', function () {
    // Create existing .fusion directory
    mkdir("{$this->artifactPath}/.fusion", 0777, true);

    $command = new InstallCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --agent=claude-code")
        ->assertStatusCode(1)
        ->assertOutputContains('already initialized');
});

it('supports multiple agents via --agent option', function () {
    $command = new InstallCommand;
    $command->setApplication(App::build());

    TestCommand::for($command)
        ->execute("--working-dir={$this->artifactPath} --agent=claude-code --agent=cursor")
        ->assertSuccessful();

    // Verify fusion.yaml contains both agents
    $config = file_get_contents("{$this->artifactPath}/.fusion/fusion.yaml");
    expect($config)->toContain('claude-code');
    expect($config)->toContain('cursor');
});
