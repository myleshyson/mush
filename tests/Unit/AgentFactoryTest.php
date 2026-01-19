<?php

use Myleshyson\Fusion\Agents\ClaudeCode;
use Myleshyson\Fusion\Agents\Codex;
use Myleshyson\Fusion\Agents\Copilot;
use Myleshyson\Fusion\Agents\Cursor;
use Myleshyson\Fusion\Agents\Gemini;
use Myleshyson\Fusion\Agents\Junie;
use Myleshyson\Fusion\Agents\OpenCode;
use Myleshyson\Fusion\Support\AgentFactory;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../artifacts/agent-factory';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

describe('AgentFactory', function () {
    describe('agentClasses()', function () {
        it('returns all supported agent classes', function () {
            $classes = AgentFactory::agentClasses();

            expect($classes)->toBeArray();
            expect($classes)->toHaveCount(7);
            expect($classes)->toContain(ClaudeCode::class);
            expect($classes)->toContain(OpenCode::class);
            expect($classes)->toContain(Junie::class);
            expect($classes)->toContain(Gemini::class);
            expect($classes)->toContain(Copilot::class);
            expect($classes)->toContain(Codex::class);
            expect($classes)->toContain(Cursor::class);
        });
    });

    describe('optionMap()', function () {
        it('returns map of option names to agent classes', function () {
            $map = AgentFactory::optionMap();

            expect($map)->toBeArray();
            expect($map)->toHaveCount(7);
            expect($map['claude'])->toBe(ClaudeCode::class);
            expect($map['opencode'])->toBe(OpenCode::class);
            expect($map['phpstorm'])->toBe(Junie::class);
            expect($map['gemini'])->toBe(Gemini::class);
            expect($map['copilot'])->toBe(Copilot::class);
            expect($map['codex'])->toBe(Codex::class);
            expect($map['cursor'])->toBe(Cursor::class);
        });
    });

    describe('promptOptions()', function () {
        it('returns options for interactive prompt', function () {
            $options = AgentFactory::promptOptions($this->artifactPath);

            expect($options)->toBeArray();
            expect($options)->toHaveCount(7);
            expect($options['claude'])->toBe('Claude Code');
            expect($options['opencode'])->toBe('OpenCode');
            expect($options['phpstorm'])->toBe('Junie (Junie)');
            expect($options['gemini'])->toBe('Gemini');
            expect($options['copilot'])->toBe('GitHub Copilot');
            expect($options['codex'])->toBe('OpenAI Codex');
            expect($options['cursor'])->toBe('Cursor');
        });
    });

    describe('fromOptionName()', function () {
        it('creates agent instance from option name', function () {
            $agent = AgentFactory::fromOptionName('claude', $this->artifactPath);
            expect($agent)->toBeInstanceOf(ClaudeCode::class);

            $agent = AgentFactory::fromOptionName('cursor', $this->artifactPath);
            expect($agent)->toBeInstanceOf(Cursor::class);
        });

        it('throws exception for invalid option name', function () {
            expect(fn () => AgentFactory::fromOptionName('invalid', $this->artifactPath))
                ->toThrow(InvalidArgumentException::class, 'Unknown agent option: invalid');
        });
    });

    describe('fromOptionNames()', function () {
        it('creates multiple agent instances from option names', function () {
            $agents = AgentFactory::fromOptionNames(['claude', 'cursor', 'opencode'], $this->artifactPath);

            expect($agents)->toHaveCount(3);
            expect($agents[0])->toBeInstanceOf(ClaudeCode::class);
            expect($agents[1])->toBeInstanceOf(Cursor::class);
            expect($agents[2])->toBeInstanceOf(OpenCode::class);
        });
    });

    describe('all()', function () {
        it('creates instances for all supported agents', function () {
            $agents = AgentFactory::all($this->artifactPath);

            expect($agents)->toHaveCount(7);
        });
    });

    describe('detectAll()', function () {
        it('returns empty array when no agents detected', function () {
            $agents = AgentFactory::detectAll($this->artifactPath);

            expect($agents)->toBeArray();
            expect($agents)->toBeEmpty();
        });

        it('detects agents based on their detection paths', function () {
            // Create files for Claude and Cursor
            mkdir("{$this->artifactPath}/.claude", 0777, true);
            file_put_contents("{$this->artifactPath}/.cursorrules", '# Cursor');

            $agents = AgentFactory::detectAll($this->artifactPath);

            expect($agents)->toHaveCount(2);
            $names = array_map(fn ($a) => $a->name(), $agents);
            expect($names)->toContain('Claude Code');
            expect($names)->toContain('Cursor');
        });
    });
});

describe('Agent optionName() static method', function () {
    it('ClaudeCode returns claude', function () {
        expect(ClaudeCode::optionName())->toBe('claude');
    });

    it('OpenCode returns opencode', function () {
        expect(OpenCode::optionName())->toBe('opencode');
    });

    it('Junie returns phpstorm', function () {
        expect(Junie::optionName())->toBe('phpstorm');
    });

    it('Gemini returns gemini', function () {
        expect(Gemini::optionName())->toBe('gemini');
    });

    it('Copilot returns copilot', function () {
        expect(Copilot::optionName())->toBe('copilot');
    });

    it('Codex returns codex', function () {
        expect(Codex::optionName())->toBe('codex');
    });

    it('Cursor returns cursor', function () {
        expect(Cursor::optionName())->toBe('cursor');
    });
});
