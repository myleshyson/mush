# Fusion

A CLI tool that helps teams manage AI guidelines, skills, and MCP server configurations for AI coding agents.

## Overview

Fusion compiles markdown files from a `.fusion` folder into output files for various AI coding agents. It also manages MCP (Model Context Protocol) server configurations across these agents.

## CLI Commands

```bash
# Show help
./vendor/bin/fusion

# Interactive setup - creates .fusion/ folder and writes agent files
./vendor/bin/fusion install

# Non-interactive update - syncs all configured agent files
./vendor/bin/fusion update

# Update with additional custom paths (for custom/unsupported agents)
./vendor/bin/fusion update --guideline-path=./custom/RULES.md --skill-path=./custom/skills/ --mcp-path=./custom/mcp.json
```

## Supported Agents

| Agent | Guidelines Path | Skills Path | MCP Path |
|-------|-----------------|-------------|----------|
| Claude Code | `.claude/CLAUDE.md` | `.claude/skills/` | `.claude/mcp.json` |
| OpenCode | `AGENTS.md` | `.opencode/skills/` | `opencode.json` (merged) |
| PhpStorm (Junie) | `.junie/guidelines.md` | `.junie/skills/` | `.junie/mcp.json` |
| Gemini | `GEMINI.md` | `.gemini/skills/` | `.gemini/mcp.json` |
| GitHub Copilot | `.github/copilot-instructions.md` | `.github/skills/` | `.vscode/mcp.json` |
| OpenAI Codex | `CODEX.md` | `.codex/skills/` | `.codex/mcp.json` |
| Cursor | `.cursorrules` | `.cursor/skills/` | `.cursor/mcp.json` |

## Project Structure

```
src/
├── App.php                      # Symfony Console application
├── Contracts/
│   └── AgentInterface.php       # Contract for all agent implementations
├── Enums/
│   └── Agent.php                # Enum of agent types
├── Agents/
│   ├── ClaudeCode.php
│   ├── OpenCode.php             # Special: merges MCP into opencode.json
│   ├── PhpStorm.php
│   ├── Gemini.php
│   ├── Copilot.php
│   ├── Codex.php
│   └── Cursor.php
├── Support/
│   ├── AgentFactory.php         # Creates agent instances from enum/config
│   └── GitignoreUpdater.php     # Adds paths to .gitignore
├── Compilers/
│   ├── GuidelinesCompiler.php   # Compiles .fusion/guidelines/*.md
│   └── SkillsCompiler.php       # Compiles .fusion/skills/*.md
└── Commands/
    ├── InstallCommand.php       # Interactive setup
    └── UpdateCommand.php        # Non-interactive sync
```

## Folder Structure

### Created by `fusion install`

```
.fusion/
├── fusion.yaml           # Stores selected agents
├── guidelines/
│   ├── .gitignore        # Contains: *\n!.gitignore
│   └── (user adds *.md files here)
├── skills/
│   ├── .gitignore        # Contains: *\n!.gitignore
│   └── (user adds *.md files here)
└── mcp.json              # Shared MCP server definitions
```

### fusion.yaml

```yaml
agents:
  - claude-code
  - opencode
  - cursor
```

### mcp.json (Source Configuration)

```json
{
  "servers": {
    "database": {
      "command": ["npx", "-y", "@modelcontextprotocol/server-postgres"],
      "env": {
        "POSTGRES_CONNECTION_STRING": "${DATABASE_URL}"
      }
    },
    "context7": {
      "url": "https://mcp.context7.com/mcp",
      "headers": {
        "CONTEXT7_API_KEY": "${CONTEXT7_API_KEY}"
      }
    }
  }
}
```

**Note:** Environment variables (`${VAR}`) are NOT resolved by Fusion. They are kept as-is for each agent to resolve at runtime.

## Output Formats

### Guidelines & Skills

All agents receive the same compiled markdown format:

```markdown
<fusion-guidelines>

=== Guidelines ===

{compiled guidelines from .fusion/guidelines/*.md, alphabetically sorted}

=== Skills ===

{compiled skills from .fusion/skills/*.md, alphabetically sorted}

</fusion-guidelines>
```

### MCP Configurations

Each agent has its own MCP JSON format. Fusion transforms the source `mcp.json` to each agent's expected format.

#### Cursor (`.cursor/mcp.json`)
```json
{
  "mcpServers": {
    "database": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-postgres"],
      "env": {
        "POSTGRES_CONNECTION_STRING": "${DATABASE_URL}"
      }
    },
    "context7": {
      "url": "https://mcp.context7.com/mcp",
      "headers": {
        "CONTEXT7_API_KEY": "${CONTEXT7_API_KEY}"
      }
    }
  }
}
```

#### VS Code/Copilot (`.vscode/mcp.json`)
```json
{
  "servers": {
    "database": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-postgres"],
      "env": {
        "POSTGRES_CONNECTION_STRING": "${DATABASE_URL}"
      }
    },
    "context7": {
      "type": "http",
      "url": "https://mcp.context7.com/mcp",
      "headers": {
        "CONTEXT7_API_KEY": "${CONTEXT7_API_KEY}"
      }
    }
  }
}
```

#### OpenCode (`opencode.json`)
```json
{
  "mcp": {
    "database": {
      "type": "local",
      "command": ["npx", "-y", "@modelcontextprotocol/server-postgres"],
      "environment": {
        "POSTGRES_CONNECTION_STRING": "${DATABASE_URL}"
      }
    },
    "context7": {
      "type": "remote",
      "url": "https://mcp.context7.com/mcp",
      "headers": {
        "CONTEXT7_API_KEY": "${CONTEXT7_API_KEY}"
      }
    }
  }
}
```

#### Claude Code (`.claude/mcp.json`)
```json
{
  "mcpServers": {
    "database": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-postgres"],
      "env": {
        "POSTGRES_CONNECTION_STRING": "${DATABASE_URL}"
      }
    },
    "context7": {
      "url": "https://mcp.context7.com/mcp",
      "headers": {
        "CONTEXT7_API_KEY": "${CONTEXT7_API_KEY}"
      }
    }
  }
}
```

## Key Behaviors

1. **Always merge** - When writing MCP configs, always merge with existing file content (preserves user's other settings)
2. **No secret resolution** - `${VAR}` syntax is preserved in output files for agents to resolve at runtime
3. **Auto-update .gitignore** - Any path Fusion writes to is automatically added to `.gitignore` if not already present
4. **Alphabetical ordering** - Guidelines and skills are sorted alphabetically in output
5. **Fail fast** - Stop on first error (missing config, invalid YAML, etc.)

## Command Details

### `fusion install`

Interactive command that sets up a new Fusion project.

**Flow:**
1. Check if `.fusion/` exists - error if so ("already initialized, use `fusion update`")
2. Prompt user to select agents (multiselect from all 7 supported agents)
3. Create `.fusion/` folder structure
4. Write `fusion.yaml` with selected agents
5. Write empty `mcp.json` template
6. Compile guidelines/skills (empty on first run)
7. Write to each selected agent's paths
8. Update `.gitignore` with all written paths
9. Display success message

### `fusion update`

Non-interactive command that syncs all configured agent files.

**Options:**
- `--guideline-path=PATH` - Additional paths to write guidelines (repeatable)
- `--skill-path=PATH` - Additional paths to write skills (repeatable)
- `--mcp-path=PATH` - Additional paths to write MCP config (repeatable)

**Flow:**
1. Check if `.fusion/fusion.yaml` exists - error if not ("run `fusion install` first")
2. Read `fusion.yaml` to get configured agents
3. Compile `.fusion/guidelines/*.md` into combined markdown
4. Compile `.fusion/skills/*.md` into skills collection
5. Read `.fusion/mcp.json` for MCP server definitions
6. For each configured agent:
   - Transform MCP config to agent's format
   - Merge and write to agent's MCP path
   - Write guidelines to agent's guidelines path
   - Copy skills to agent's skills path
7. For each custom path from options:
   - Write to specified paths
8. Update `.gitignore` with all written paths
9. Display success message

## AgentInterface Contract

Each agent class must implement:

```php
interface AgentInterface
{
    public function name(): string;
    public function guidelinesPath(): string;
    public function skillsPath(): string;
    public function mcpPath(): string;
    public function writeGuidelines(string $content): void;
    public function writeSkills(array $skills): void;
    public function writeMcpConfig(array $servers): void;
}
```

## Dependencies

### Runtime
- `symfony/console` - CLI framework
- `symfony/yaml` - YAML parsing
- `laravel/prompts` - Interactive prompts

### Development
- `pestphp/pest` - Testing
- `zenstruck/console-test` - Console command testing
- `laravel/pint` - Code style
- `phpstan/phpstan` - Static analysis
