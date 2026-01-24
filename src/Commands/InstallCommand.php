<?php

namespace Myleshyson\Mush\Commands;

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

use function Laravel\Prompts\multiselect;

#[AsCommand(
    name: 'install',
    description: 'Initialize a new Mush project and write agent configuration files.',
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
        $mushPath = $workingDirectory.'/.mush';

        // Get agents from options or prompt interactively
        $selectedAgents = $this->getSelectedAgents($input, $workingDirectory);

        // Create .mush directory structure
        $this->createMushStructure($mushPath);

        // Write empty mcp.json template
        $this->writeMcpTemplate($mushPath);

        // Compile guidelines and skills (will be empty on first run)
        $guidelinesCompiler = new GuidelinesCompiler;
        $skillsCompiler = new SkillsCompiler;

        $guidelines = $guidelinesCompiler->compile($mushPath.'/guidelines');
        $skills = $skillsCompiler->compile($mushPath.'/skills');

        // Format output content
        $content = $this->formatOutput($guidelines, $skills);

        // Read MCP config
        $mcpConfig = McpConfigReader::read($mushPath);

        // Write to each selected agent's paths
        $agents = AgentFactory::fromOptionNames($selectedAgents, $workingDirectory);
        $writtenPaths = [];

        foreach ($agents as $agent) {
            $agent->guidelines()?->write($content);
            $agent->skills()?->write($skills);
            $agent->mcp()?->write($mcpConfig);

            if ($agent->guidelines() !== null) {
                $writtenPaths[] = $agent->guidelines()->path();
            }
            if ($agent->skills() !== null) {
                $writtenPaths[] = $agent->skills()->path();
            }
            if ($agent->mcp() !== null) {
                $writtenPaths[] = $agent->mcp()->path();
            }
        }

        // Update .gitignore
        $gitignoreUpdater = new GitignoreUpdater($workingDirectory);
        $writtenPaths[] = '.mush/mcp.override.json'; // Always gitignore local overrides
        $gitignoreUpdater->addPaths($writtenPaths);

        $output->writeln('<info>Mush initialized successfully!</info>');
        $output->writeln('');
        $output->writeln('Next steps:');
        $output->writeln('  1. Add guideline markdown files to <comment>.mush/guidelines/</comment>');
        $output->writeln('  2. Add skills as subdirectories with SKILL.md to <comment>.mush/skills/</comment>');
        $output->writeln('  3. Add custom agents as markdown files to <comment>.mush/agents/</comment>');
        $output->writeln('  4. Add slash commands as markdown files to <comment>.mush/commands/</comment>');
        $output->writeln('  5. Configure MCP servers in <comment>.mush/mcp.json</comment>');
        $output->writeln('  6. Run <comment>mush update</comment> to sync changes');

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
            default: ['claude', 'opencode', 'copilot'],
            required: true,
        );

        return $selected;
    }

    protected function createMushStructure(string $mushPath): void
    {
        if (! is_dir($mushPath)) {
            mkdir($mushPath, 0755, true);
        }

        $directories = ['guidelines', 'skills', 'agents', 'commands'];

        foreach ($directories as $dir) {
            if (! is_dir($mushPath.'/'.$dir)) {
                mkdir($mushPath.'/'.$dir, 0755, true);
            }
        }

        // Create .gitignore files to prevent accidental commits of source files
        // but keep the directories
        $gitignoreContent = "*\n!.gitignore\n";

        foreach ($directories as $dir) {
            if (! file_exists($mushPath.'/'.$dir.'/.gitignore')) {
                file_put_contents($mushPath.'/'.$dir.'/.gitignore', $gitignoreContent);
            }
        }
    }

    protected function writeMcpTemplate(string $mushPath): void
    {
        $template = [
            'servers' => [
                // Empty template - users will add their own servers
            ],
        ];

        if (! file_exists($mushPath.'/mcp.json')) {
            file_put_contents(
                $mushPath.'/mcp.json',
                json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            );
        }
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
                $output .= "- {$skillName}";
                if (! empty($skillData['description'])) {
                    $output .= ": {$skillData['description']}";
                }
                $output .= "\n";
            }
        }

        $output .= "\n</mush-guidelines>\n";

        return $output;
    }
}
