<?php

namespace Myleshyson\Fusion\Commands;

use Myleshyson\Fusion\Compilers\GuidelinesCompiler;
use Myleshyson\Fusion\Compilers\SkillsCompiler;
use Myleshyson\Fusion\Enums\Agent;
use Myleshyson\Fusion\Support\AgentFactory;
use Myleshyson\Fusion\Support\GitignoreUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\multiselect;

#[AsCommand(
    name: 'install',
    description: 'Initialize a new Fusion project and write agent configuration files.',
)]
class InstallCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'agent',
            'a',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Agent(s) to install (can be specified multiple times for non-interactive mode)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = $input->getOption('working-dir') ?? getcwd();
        $fusionPath = $workingDirectory.'/.fusion';

        // Check if .fusion already exists
        if (is_dir($fusionPath)) {
            $output->writeln('<error>Fusion is already initialized in this directory.</error>');
            $output->writeln('Run <info>fusion update</info> to sync agent files.');

            return Command::FAILURE;
        }

        // Get agents from option or prompt interactively
        $agentOptions = $input->getOption('agent');
        if (! empty($agentOptions)) {
            $selectedAgents = $agentOptions;
        } else {
            $selectedAgents = multiselect(
                label: 'Which agents would you like to support?',
                options: Agent::options(),
                required: true,
            );
        }

        // Create .fusion directory structure
        $this->createFusionStructure($fusionPath);

        // Write fusion.yaml with selected agents
        $this->writeFusionConfig($fusionPath, $selectedAgents);

        // Write empty mcp.json template
        $this->writeMcpTemplate($fusionPath);

        // Compile guidelines and skills (will be empty on first run)
        $guidelinesCompiler = new GuidelinesCompiler;
        $skillsCompiler = new SkillsCompiler;

        $guidelines = $guidelinesCompiler->compile($fusionPath.'/guidelines');
        $skills = $skillsCompiler->compile($fusionPath.'/skills');

        // Format output content
        $content = $this->formatOutput($guidelines, $skills);

        // Read MCP config
        $mcpConfig = $this->readMcpConfig($fusionPath);

        // Write to each selected agent's paths
        $agents = AgentFactory::fromArray($selectedAgents, $workingDirectory);
        $writtenPaths = [];

        foreach ($agents as $agent) {
            $agent->writeGuidelines($content);
            $agent->writeSkills($skills);
            $agent->writeMcpConfig($mcpConfig);

            $writtenPaths[] = $agent->guidelinesPath();
            $writtenPaths[] = $agent->skillsPath();
            $writtenPaths[] = $agent->mcpPath();
        }

        // Update .gitignore
        $gitignoreUpdater = new GitignoreUpdater($workingDirectory);
        $gitignoreUpdater->addPaths($writtenPaths);

        $output->writeln('<info>Fusion initialized successfully!</info>');
        $output->writeln('');
        $output->writeln('Next steps:');
        $output->writeln('  1. Add guideline markdown files to <comment>.fusion/guidelines/</comment>');
        $output->writeln('  2. Add skill markdown files to <comment>.fusion/skills/</comment>');
        $output->writeln('  3. Configure MCP servers in <comment>.fusion/mcp.json</comment>');
        $output->writeln('  4. Run <comment>fusion update</comment> to sync changes');

        return Command::SUCCESS;
    }

    protected function createFusionStructure(string $fusionPath): void
    {
        // Create directories
        mkdir($fusionPath, 0755, true);
        mkdir($fusionPath.'/guidelines', 0755, true);
        mkdir($fusionPath.'/skills', 0755, true);

        // Create .gitignore files to prevent accidental commits of source files
        // but keep the directories
        $gitignoreContent = "*\n!.gitignore\n";
        file_put_contents($fusionPath.'/guidelines/.gitignore', $gitignoreContent);
        file_put_contents($fusionPath.'/skills/.gitignore', $gitignoreContent);
    }

    /**
     * @param  string[]  $agents
     */
    protected function writeFusionConfig(string $fusionPath, array $agents): void
    {
        $config = [
            'agents' => $agents,
        ];

        file_put_contents(
            $fusionPath.'/fusion.yaml',
            Yaml::dump($config, 2, 2)
        );
    }

    protected function writeMcpTemplate(string $fusionPath): void
    {
        $template = [
            'servers' => [
                // Empty template - users will add their own servers
            ],
        ];

        file_put_contents(
            $fusionPath.'/mcp.json',
            json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );
    }

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
}
