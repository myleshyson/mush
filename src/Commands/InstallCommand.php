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

use function Laravel\Prompts\multiselect;

#[AsCommand(
    name: 'install',
    description: 'Initialize a new Fusion project and write agent configuration files.',
)]
class InstallCommand extends Command
{
    protected function configure(): void
    {
        // Dynamically add options for each agent
        foreach (AgentFactory::agentClasses() as $agentClass) {
            $optionName = $agentClass::optionName();
            $this->addOption(
                $optionName,
                null,
                InputOption::VALUE_NONE,
                "Install support for {$optionName}"
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $workingDirectory */
        $workingDirectory = $input->getOption('working-dir') ?? getcwd();
        $fusionPath = $workingDirectory.'/.fusion';

        // Check if .fusion already exists
        if (is_dir($fusionPath)) {
            $output->writeln('<error>Fusion is already initialized in this directory.</error>');
            $output->writeln('Run <info>fusion update</info> to sync agent files.');

            return Command::FAILURE;
        }

        // Get agents from options or prompt interactively
        $selectedAgents = $this->getSelectedAgents($input, $workingDirectory);

        // Create .fusion directory structure
        $this->createFusionStructure($fusionPath);

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
        $agents = AgentFactory::fromOptionNames($selectedAgents, $workingDirectory);
        $writtenPaths = [];

        foreach ($agents as $agent) {
            $agent->writeGuidelines($content);
            $agent->writeSkills($skills);
            $agent->writeMcpConfig($mcpConfig);

            $writtenPaths[] = $agent->guidelinesPath();
            $writtenPaths[] = $agent->skillsPath();
            if ($agent->mcpPath() !== '') {
                $writtenPaths[] = $agent->mcpPath();
            }
        }

        // Update .gitignore
        $gitignoreUpdater = new GitignoreUpdater($workingDirectory);
        $gitignoreUpdater->addPaths($writtenPaths);

        $output->writeln('<info>Fusion initialized successfully!</info>');
        $output->writeln('');
        $output->writeln('Next steps:');
        $output->writeln('  1. Add guideline markdown files to <comment>.fusion/guidelines/</comment>');
        $output->writeln('  2. Add skills as subdirectories with SKILL.md to <comment>.fusion/skills/</comment>');
        $output->writeln('  3. Configure MCP servers in <comment>.fusion/mcp.json</comment>');
        $output->writeln('  4. Run <comment>fusion update</comment> to sync changes');

        return Command::SUCCESS;
    }

    /**
     * Get selected agents from CLI options or interactive prompt.
     *
     * @return string[]
     */
    protected function getSelectedAgents(InputInterface $input, string $workingDirectory): array
    {
        // Check if any agent options were provided
        $selectedFromOptions = [];
        foreach (AgentFactory::agentClasses() as $agentClass) {
            $optionName = $agentClass::optionName();
            if ($input->getOption($optionName)) {
                $selectedFromOptions[] = $optionName;
            }
        }

        if (! empty($selectedFromOptions)) {
            return $selectedFromOptions;
        }

        // No options provided, prompt interactively
        /** @var string[] $selected */
        $selected = multiselect(
            label: 'Which agents would you like to support?',
            options: AgentFactory::promptOptions($workingDirectory),
            required: true,
        );

        return $selected;
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

    /**
     * @param  array<string, array{name: string, description: string, content: string}>  $skills
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
            $output .= "Available skills:\n";
            foreach ($skills as $skillName => $skillData) {
                $output .= "- {$skillName}";
                if (! empty($skillData['description'])) {
                    $output .= ": {$skillData['description']}";
                }
                $output .= "\n";
            }
        }

        $output .= "\n</fusion-guidelines>\n";

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
        if (! is_array($config)) {
            return [];
        }

        /** @var array<string, mixed> */
        return $config['servers'] ?? [];
    }
}
