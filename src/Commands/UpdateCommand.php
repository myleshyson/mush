<?php

namespace Myleshyson\Mush\Commands;

use Myleshyson\Mush\Compilers\AgentsCompiler;
use Myleshyson\Mush\Compilers\CommandsCompiler;
use Myleshyson\Mush\Compilers\GuidelinesCompiler;
use Myleshyson\Mush\Compilers\SkillsCompiler;
use Myleshyson\Mush\Support\AgentFactory;
use Myleshyson\Mush\Support\GitignoreUpdater;
use Myleshyson\Mush\Support\McpConfigReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'update',
    description: 'Sync all detected agent files with current guidelines, skills, and MCP config.',
)]
class UpdateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'guideline-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Additional paths to write guidelines (can be specified multiple times)'
            )
            ->addOption(
                'skill-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Additional paths to write skills (can be specified multiple times)'
            )
            ->addOption(
                'mcp-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Additional paths to write MCP config (can be specified multiple times)'
            )
            ->addOption(
                'agents-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Additional paths to write agents (can be specified multiple times)'
            )
            ->addOption(
                'commands-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Additional paths to write commands (can be specified multiple times)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $workingDirectory */
        $workingDirectory = $input->getOption('working-dir') ?? getcwd();
        $mushPath = $workingDirectory.'/.mush';

        // Check if .mush directory exists
        if (! is_dir($mushPath)) {
            $output->writeln('<error>Mush is not initialized in this directory.</error>');
            $output->writeln('Run <info>mush install</info> first to set up your project.');

            return Command::FAILURE;
        }

        // Auto-detect which agents are configured in the project
        $agents = AgentFactory::detectAll($workingDirectory);

        if (empty($agents)) {
            $output->writeln('<error>No agents detected in this project.</error>');
            $output->writeln('Run <info>mush install</info> to configure agents.');

            return Command::FAILURE;
        }

        // Compile guidelines, skills, agents, and commands
        $guidelinesCompiler = new GuidelinesCompiler;
        $skillsCompiler = new SkillsCompiler;
        $agentsCompiler = new AgentsCompiler;
        $commandsCompiler = new CommandsCompiler;

        $guidelines = $guidelinesCompiler->compile($mushPath.'/guidelines');
        $skills = $skillsCompiler->compile($mushPath.'/skills');
        $compiledAgents = $agentsCompiler->compile($mushPath.'/agents');
        $compiledCommands = $commandsCompiler->compile($mushPath.'/commands');

        // Format output content
        $content = $this->formatOutput($guidelines, $skills);

        // Read MCP config (base + override)
        $mcpConfig = McpConfigReader::read($mushPath);

        // Write to each detected agent's paths
        $writtenPaths = [];

        foreach ($agents as $agent) {
            $agent->guidelines()?->write($content);
            $agent->skills()?->write($skills);
            $agent->mcp()?->write($mcpConfig);
            $agent->agents()?->write($compiledAgents);
            $agent->commands()?->write($compiledCommands);

            if ($agent->guidelines() !== null) {
                $writtenPaths[] = $agent->guidelines()->path();
            }
            if ($agent->skills() !== null) {
                $writtenPaths[] = $agent->skills()->path();
            }
            if ($agent->mcp() !== null) {
                $writtenPaths[] = $agent->mcp()->path();
            }
            if ($agent->agents() !== null) {
                $writtenPaths[] = $agent->agents()->path();
            }
            if ($agent->commands() !== null) {
                $writtenPaths[] = $agent->commands()->path();
            }

            $output->writeln("  Updated <info>{$agent->name()}</info>");
        }

        // Handle additional custom paths
        /** @var string[] $customGuidelinePaths */
        $customGuidelinePaths = $input->getOption('guideline-path');
        /** @var string[] $customSkillPaths */
        $customSkillPaths = $input->getOption('skill-path');
        /** @var string[] $customMcpPaths */
        $customMcpPaths = $input->getOption('mcp-path');
        /** @var string[] $customAgentsPaths */
        $customAgentsPaths = $input->getOption('agents-path');
        /** @var string[] $customCommandsPaths */
        $customCommandsPaths = $input->getOption('commands-path');

        foreach ($customGuidelinePaths as $path) {
            $this->writeCustomGuidelines($workingDirectory, $path, $content);
            $writtenPaths[] = $path;
            $output->writeln("  Updated custom guideline path: <info>{$path}</info>");
        }

        foreach ($customSkillPaths as $path) {
            $this->writeCustomSkills($workingDirectory, $path, $skills);
            $writtenPaths[] = $path;
            $output->writeln("  Updated custom skill path: <info>{$path}</info>");
        }

        foreach ($customMcpPaths as $path) {
            $this->writeCustomMcp($workingDirectory, $path, $mcpConfig);
            $writtenPaths[] = $path;
            $output->writeln("  Updated custom MCP path: <info>{$path}</info>");
        }

        foreach ($customAgentsPaths as $path) {
            $this->writeCustomAgents($workingDirectory, $path, $compiledAgents);
            $writtenPaths[] = $path;
            $output->writeln("  Updated custom agents path: <info>{$path}</info>");
        }

        foreach ($customCommandsPaths as $path) {
            $this->writeCustomCommands($workingDirectory, $path, $compiledCommands);
            $writtenPaths[] = $path;
            $output->writeln("  Updated custom commands path: <info>{$path}</info>");
        }

        // Update .gitignore
        $gitignoreUpdater = new GitignoreUpdater($workingDirectory);
        $gitignoreUpdater->addPaths($writtenPaths);

        $output->writeln('');
        $output->writeln('<info>All agent files updated successfully!</info>');

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, array{name: string, description: string, content: string}>  $skills
     */
    protected function formatOutput(string $guidelines, array $skills): string
    {
        $output = "<mush-guidelines>\n\n";
        $output .= "=== Guidelines ===\n\n";
        $output .= $guidelines ?: '(No guidelines defined yet)';
        $output .= "\n\n=== Skills ===\n\n";

        if (empty($skills)) {
            $output .= '(No skills defined yet)';
        } else {
            $output .= "Available skills:\n";
            foreach ($skills as $skillName => $skillData) {
                $output .= "- **{$skillName}**";
                if (! empty($skillData['description'])) {
                    $output .= ": {$skillData['description']}";
                }
                $output .= "\n";
            }
        }

        $output .= "\n</mush-guidelines>\n";

        return $output;
    }

    protected function writeCustomGuidelines(string $workingDirectory, string $path, string $content): void
    {
        $fullPath = $this->resolvePath($workingDirectory, $path);
        $this->ensureDirectoryExists($fullPath);
        file_put_contents($fullPath, $content);
    }

    /**
     * @param  array<string, array{name: string, description: string, content: string}>  $skills
     */
    protected function writeCustomSkills(string $workingDirectory, string $path, array $skills): void
    {
        $basePath = $this->resolvePath($workingDirectory, $path);
        $this->ensureDirectoryExists($basePath);

        foreach ($skills as $skillName => $skillData) {
            // Each skill is a subdirectory containing a SKILL.md file
            $skillDir = rtrim($basePath, '/').'/'.$skillName;
            if (! is_dir($skillDir)) {
                mkdir($skillDir, 0755, true);
            }
            $skillPath = $skillDir.'/SKILL.md';
            $content = $this->reconstructSkillContent($skillData);
            file_put_contents($skillPath, $content);
        }
    }

    /**
     * Reconstruct the full SKILL.md content from parsed skill data.
     *
     * @param  array{name: string, description: string, content: string}  $skillData
     */
    protected function reconstructSkillContent(array $skillData): string
    {
        $output = "---\n";
        $output .= "name: {$skillData['name']}\n";
        if ($skillData['description'] !== '') {
            $output .= "description: {$skillData['description']}\n";
        }
        $output .= "---\n\n";
        $output .= $skillData['content'];

        return $output;
    }

    /**
     * @param  array<string, mixed>  $mcpConfig
     */
    protected function writeCustomMcp(string $workingDirectory, string $path, array $mcpConfig): void
    {
        $fullPath = $this->resolvePath($workingDirectory, $path);
        $this->ensureDirectoryExists($fullPath);

        // For custom paths, use a generic format (same as Claude/Cursor)
        $mcpServers = [];
        foreach ($mcpConfig as $name => $config) {
            if (! is_array($config)) {
                continue;
            }

            $server = [];

            if (isset($config['command'])) {
                $command = $config['command'];
                $server['command'] = is_array($command) ? $command[0] : $command;
                if (is_array($command) && count($command) > 1) {
                    $server['args'] = array_slice($command, 1);
                }
                if (isset($config['env'])) {
                    $server['env'] = $config['env'];
                }
            } elseif (isset($config['url'])) {
                $server['url'] = $config['url'];
                if (isset($config['headers'])) {
                    $server['headers'] = $config['headers'];
                }
            }

            $mcpServers[$name] = $server;
        }

        // Merge with existing if file exists
        $existingConfig = [];
        if (file_exists($fullPath)) {
            $content = file_get_contents($fullPath);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                $existingConfig = is_array($decoded) ? $decoded : [];
            }
        }

        $newConfig = ['mcpServers' => $mcpServers];
        $merged = array_replace_recursive($existingConfig, $newConfig);

        file_put_contents($fullPath, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    protected function resolvePath(string $workingDirectory, string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($workingDirectory, '/').'/'.ltrim($path, './');
    }

    protected function ensureDirectoryExists(string $path): void
    {
        $dir = str_ends_with($path, '/') ? $path : dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * @param  array<string, array{name: string, description: string, content: string}>  $agents
     */
    protected function writeCustomAgents(string $workingDirectory, string $path, array $agents): void
    {
        $basePath = $this->resolvePath($workingDirectory, $path);
        $this->ensureDirectoryExists($basePath.'/');

        foreach ($agents as $agentName => $agentData) {
            $agentPath = rtrim($basePath, '/').'/'.$agentName.'.md';
            $content = $this->reconstructAgentContent($agentData);
            file_put_contents($agentPath, $content);
        }
    }

    /**
     * Reconstruct the full agent content from parsed agent data.
     *
     * @param  array{name: string, description: string, content: string}  $agentData
     */
    protected function reconstructAgentContent(array $agentData): string
    {
        $output = "---\n";
        $output .= "name: {$agentData['name']}\n";
        if ($agentData['description'] !== '') {
            $output .= "description: {$agentData['description']}\n";
        }
        $output .= "---\n\n";
        $output .= $agentData['content'];

        return $output;
    }

    /**
     * @param  array<string, array{name: string, description: string, content: string}>  $commands
     */
    protected function writeCustomCommands(string $workingDirectory, string $path, array $commands): void
    {
        $basePath = $this->resolvePath($workingDirectory, $path);
        $this->ensureDirectoryExists($basePath.'/');

        foreach ($commands as $commandName => $commandData) {
            $commandPath = rtrim($basePath, '/').'/'.$commandName.'.md';
            $content = $this->reconstructCommandContent($commandData);
            file_put_contents($commandPath, $content);
        }
    }

    /**
     * Reconstruct the full command content from parsed command data.
     *
     * @param  array{name: string, description: string, content: string}  $commandData
     */
    protected function reconstructCommandContent(array $commandData): string
    {
        $output = "---\n";
        if ($commandData['description'] !== '') {
            $output .= "description: {$commandData['description']}\n";
        }
        $output .= "---\n\n";
        $output .= $commandData['content'];

        return $output;
    }
}
