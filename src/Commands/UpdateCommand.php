<?php

namespace Myleshyson\Fusion\Commands;

use Myleshyson\Fusion\Compilers\GuidelinesCompiler;
use Myleshyson\Fusion\Compilers\SkillsCompiler;
use Myleshyson\Fusion\Support\AgentFactory;
use Myleshyson\Fusion\Support\GitignoreUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'update',
    description: 'Sync all configured agent files with current guidelines, skills, and MCP config.',
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $input->getOption('working-dir') ?? getcwd();
        $fusionPath = $workingDirectory.'/.fusion';
        $configPath = $fusionPath.'/fusion.yaml';

        // Check if .fusion/fusion.yaml exists
        if (! file_exists($configPath)) {
            $output->writeln('<error>Fusion is not initialized in this directory.</error>');
            $output->writeln('Run <info>fusion install</info> first to set up your project.');

            return Command::FAILURE;
        }

        // Read fusion.yaml
        $config = Yaml::parseFile($configPath);
        $configuredAgents = $config['agents'] ?? [];

        if (empty($configuredAgents)) {
            $output->writeln('<error>No agents configured in fusion.yaml.</error>');
            $output->writeln('Run <info>fusion install</info> to configure agents.');

            return Command::FAILURE;
        }

        // Compile guidelines and skills
        $guidelinesCompiler = new GuidelinesCompiler;
        $skillsCompiler = new SkillsCompiler;

        $guidelines = $guidelinesCompiler->compile($fusionPath.'/guidelines');
        $skills = $skillsCompiler->compile($fusionPath.'/skills');

        // Format output content
        $content = $this->formatOutput($guidelines, $skills);

        // Read MCP config
        $mcpConfig = $this->readMcpConfig($fusionPath);

        // Write to each configured agent's paths
        $agents = AgentFactory::fromArray($configuredAgents, $workingDirectory);
        $writtenPaths = [];

        foreach ($agents as $agent) {
            $agent->writeGuidelines($content);
            $agent->writeSkills($skills);
            $agent->writeMcpConfig($mcpConfig);

            $writtenPaths[] = $agent->guidelinesPath();
            $writtenPaths[] = $agent->skillsPath();
            $writtenPaths[] = $agent->mcpPath();

            $output->writeln("  Updated <info>{$agent->name()}</info>");
        }

        // Handle additional custom paths
        $customGuidelinePaths = $input->getOption('guideline-path');
        $customSkillPaths = $input->getOption('skill-path');
        $customMcpPaths = $input->getOption('mcp-path');

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

        // Update .gitignore
        $gitignoreUpdater = new GitignoreUpdater($workingDirectory);
        $gitignoreUpdater->addPaths($writtenPaths);

        $output->writeln('');
        $output->writeln('<info>All agent files updated successfully!</info>');

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, string>  $skills
     */
    protected function formatOutput(string $guidelines, array $skills): string
    {
        $output = "<fusion-guidelines>\n\n";
        $output .= "=== Guidelines ===\n\n";
        $output .= $guidelines ?: '(No guidelines defined yet)';
        $output .= "\n\n=== Skills ===\n\n";

        if (empty($skills)) {
            $output .= '(No skills defined yet)';
        } else {
            $output .= implode("\n\n", $skills);
        }

        $output .= "\n\n</fusion-guidelines>\n";

        return $output;
    }

    /**
     * @return array<string, mixed>
     */
    protected function readMcpConfig(string $fusionPath): array
    {
        $mcpPath = $fusionPath.'/mcp.json';
        if (! file_exists($mcpPath)) {
            return [];
        }

        $content = file_get_contents($mcpPath);
        if ($content === false) {
            return [];
        }

        $config = json_decode($content, true);

        return $config['servers'] ?? [];
    }

    protected function writeCustomGuidelines(string $workingDirectory, string $path, string $content): void
    {
        $fullPath = $this->resolvePath($workingDirectory, $path);
        $this->ensureDirectoryExists($fullPath);
        file_put_contents($fullPath, $content);
    }

    /**
     * @param  array<string, string>  $skills
     */
    protected function writeCustomSkills(string $workingDirectory, string $path, array $skills): void
    {
        $fullPath = $this->resolvePath($workingDirectory, $path);
        $this->ensureDirectoryExists($fullPath);

        foreach ($skills as $filename => $content) {
            $skillPath = rtrim($fullPath, '/').'/'.$filename;
            file_put_contents($skillPath, $content);
        }
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
                $existingConfig = json_decode($content, true) ?? [];
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
}
