```
 ▄▄       ▄▄  ▄         ▄  ▄▄▄▄▄▄▄▄▄▄▄  ▄         ▄ 
▐░░▌     ▐░░▌▐░▌       ▐░▌▐░░░░░░░░░░░▌▐░▌       ▐░▌
▐░▌░▌   ▐░▐░▌▐░▌       ▐░▌▐░█▀▀▀▀▀▀▀▀▀ ▐░▌       ▐░▌
▐░▌▐░▌ ▐░▌▐░▌▐░▌       ▐░▌▐░▌          ▐░▌       ▐░▌
▐░▌ ▐░▐░▌ ▐░▌▐░▌       ▐░▌▐░█▄▄▄▄▄▄▄▄▄ ▐░█▄▄▄▄▄▄▄█░▌
▐░▌  ▐░▌  ▐░▌▐░▌       ▐░▌▐░░░░░░░░░░░▌▐░░░░░░░░░░░▌
▐░▌   ▀   ▐░▌▐░▌       ▐░▌ ▀▀▀▀▀▀▀▀▀█░▌▐░█▀▀▀▀▀▀▀█░▌
▐░▌       ▐░▌▐░▌       ▐░▌          ▐░▌▐░▌       ▐░▌
▐░▌       ▐░▌▐░█▄▄▄▄▄▄▄█░▌ ▄▄▄▄▄▄▄▄▄█░▌▐░▌       ▐░▌
▐░▌       ▐░▌▐░░░░░░░░░░░▌▐░░░░░░░░░░░▌▐░▌       ▐░▌
 ▀         ▀  ▀▀▀▀▀▀▀▀▀▀▀  ▀▀▀▀▀▀▀▀▀▀▀  ▀         ▀ 
```

A CLI tool that syncs AI agent configurations across your team. Define guidelines, skills, custom agents, slash commands, and MCP servers once in
`.mush/` and automatically sync them to Claude Code, Cursor, Copilot, Gemini, OpenCode, Codex, and Junie.

## Installation

### Quick Install (Recommended)

```bash
curl -fsSL https://raw.githubusercontent.com/myleshyson/mush/main/install.sh | sh
```

### Custom Install Location

```bash
curl -fsSL https://raw.githubusercontent.com/myleshyson/mush/main/install.sh | MUSH_INSTALL_DIR=~/.local/bin sh
```

### Install Specific Version

```bash
curl -fsSL https://raw.githubusercontent.com/myleshyson/mush/main/install.sh | MUSH_VERSION=v1.0.0 sh
```

### Manual Download

Download the appropriate binary for your platform from
the [releases page](https://github.com/myleshyson/mush/releases):

- `mush-linux-x86_64` - Linux (Intel/AMD)
- `mush-linux-aarch64` - Linux (ARM64)
- `mush-macos-x86_64` - macOS (Intel)
- `mush-macos-aarch64` - macOS (Apple Silicon)
- `mush-windows-x64.exe` - Windows (64-bit)

### Install via Composer (PHP 8.2+)

If you have PHP and Composer installed:

```bash
composer global require myleshyson/mush
```

Or add as a dev dependency to your project:

```bash
composer require --dev myleshyson/mush
./vendor/bin/mush install
```

## Getting Started

### Initialize a New Project

```bash
# Interactive mode - select which agents to configure
mush install

# Or specify agents directly
mush install --claude --cursor --copilot
```

This creates a `.mush/` directory with:

```
.mush/
├── guidelines/     # Shared guidelines (markdown files)
├── skills/         # Reusable skills (subdirectories with SKILL.md)
├── agents/         # Custom agents (markdown files)
├── commands/       # Slash commands (markdown files)
└── mcp.json        # MCP server configurations
```

### Update Agent Files

After modifying your `.mush/` configuration, sync changes to all detected agents:

```bash
mush update
```

Mush automatically detects which agents are configured in your project and updates their respective files.

## Configuration

### Guidelines

Add markdown files to `.mush/guidelines/` to define shared instructions:

```markdown
<!-- .mush/guidelines/code-style.md -->

# Code Style

- Use consistent indentation (4 spaces)
- Write descriptive variable names
- Add comments for complex logic
```

Guidelines are compiled alphabetically, so prefix filenames with numbers to control order:

- `01-overview.md`
- `02-code-style.md`
- `03-testing.md`

### Skills

Skills are reusable instruction sets that agents can invoke. Create a subdirectory in `.mush/skills/` with a`SKILL.md`
file:

```markdown
<!-- .mush/skills/testing/SKILL.md -->
---
name: testing
description: Helps write and run tests for the codebase
---

# Testing Guidelines

When writing tests:

- Use descriptive test names
- Follow AAA pattern (Arrange, Act, Assert)
- Mock external dependencies
```

The `description` field helps agents decide when to apply the skill.

### MCP Servers

Configure MCP (Model Context Protocol) servers in `.mush/mcp.json`:

```json
{
  "servers": {
    "database": {
      "command": [
        "npx",
        "-y",
        "@modelcontextprotocol/server-postgres"
      ],
      "env": {
        "DATABASE_URL": "postgres://localhost/mydb"
      }
    },
    "github": {
      "command": [
        "npx",
        "-y",
        "@modelcontextprotocol/server-github"
      ],
      "env": {
        "GITHUB_TOKEN": "${GITHUB_TOKEN}"
      }
    },
    "remote-api": {
      "url": "https://api.example.com/mcp",
      "headers": {
        "Authorization": "Bearer ${API_TOKEN}"
      }
    }
  }
}
```

Mush transforms this configuration to each agent's expected format.

### Custom Agents

Custom agents are specialized personas that can be invoked during conversations. Create markdown files in `.mush/agents/`:

```markdown
<!-- .mush/agents/security-reviewer.md -->
---
name: security-reviewer
description: Reviews code for security vulnerabilities and best practices
---

# Security Reviewer

You are a security expert. When reviewing code:

- Check for injection vulnerabilities (SQL, XSS, command injection)
- Verify authentication and authorization logic
- Look for sensitive data exposure
- Ensure proper input validation
```

The `name` field is the identifier used to invoke the agent, and `description` helps users understand when to use it.

### Slash Commands

Slash commands are reusable prompts that can be invoked with a `/` prefix. Create markdown files in `.mush/commands/`:

```markdown
<!-- .mush/commands/review.md -->
---
name: review
description: Review code for quality and best practices
---

Please review the selected code for:

1. Code quality and readability
2. Potential bugs or edge cases
3. Performance considerations
4. Adherence to project conventions
```

Commands provide a quick way to run common prompts without retyping them.

### Local Overrides

For personal MCP servers that shouldn't be committed (local databases, personal API tokens), create `.mush/mcp.override.json`:

```json
{
  "servers": {
    "github": {
      "command": ["npx", "-y", "@modelcontextprotocol/server-github"],
      "env": {
        "GITHUB_TOKEN": "my-personal-token"
      }
    },
    "local-db": {
      "command": ["npx", "-y", "@modelcontextprotocol/server-postgres"],
      "env": {
        "DATABASE_URL": "postgres://localhost/mydevdb"
      }
    }
  }
}
```

Override servers completely replace matching servers from `mcp.json`. This file is automatically added to `.gitignore` during `mush install`.

For personal guidelines or skills that shouldn't be shared, add them to `.mush/` and gitignore them. Use the `!` prefix to explicitly track shared files:

```gitignore
# Ignore all mush config by default
.mush/guidelines/*
.mush/skills/*

# Track shared files
!.mush/guidelines/code-style.md
!.mush/guidelines/testing.md
!.mush/skills/api-design/
```

## Commands

### `mush install`

Initialize Mush in a project.

```bash
# Interactive mode
mush install

# Specify agents
mush install --claude --cursor --copilot --gemini --opencode --codex --junie

# All agents
mush install --claude --cursor --copilot --gemini --opencode --codex --junie
```

### `mush update`

Sync `.mush/` configuration to all detected agents.

```bash
# Auto-detect agents
mush update

# Custom paths for additional outputs
mush update --guideline-path=./custom/RULES.md
mush update --skill-path=./custom/skills/
mush update --mcp-path=./custom/mcp.json
mush update --agents-path=./custom/agents/
mush update --commands-path=./custom/commands/
```

## Supported Agents

| Agent          | Guidelines                        | Skills              | MCP                     | Agents                | Commands                     |
|----------------|-----------------------------------|---------------------|-------------------------|-----------------------|------------------------------|
| Claude Code    | `.claude/CLAUDE.md`               | `.claude/skills/`   | `.claude/mcp.json`      | `.claude/agents/`     | `.claude/commands/`          |
| Cursor         | `.cursor/rules/mush.mdc`          | `.cursor/skills/`   | `.cursor/mcp.json`      | —                     | `.cursor/commands/`          |
| GitHub Copilot | `.github/copilot-instructions.md` | `.github/skills/`   | `.vscode/mcp.json`      | `.github/agents/`     | `.github/prompts/`\*\*       |
| Gemini         | `GEMINI.md`                       | `.gemini/skills/`   | `.gemini/settings.json` | —                     | `.gemini/commands/`\*\*\*    |
| OpenCode       | `AGENTS.md`                       | `.opencode/skills/` | `opencode.json`         | `.opencode/agents/`   | `.opencode/commands/`        |
| OpenAI Codex   | `AGENTS.md`                       | `.codex/skills/`    | —\*                     | —                     | —                            |
| Junie          | `.junie/guidelines.md`            | `.junie/skills/`    | `.junie/mcp/mcp.json`   | —                     | `.junie/commands/`           |

\*OpenAI Codex supports MCP via `~/.codex/config.toml`, but only at the global/user level, not per-project. Mush focuses on project-level configuration, so Codex MCP is not currently supported.

\*\*GitHub Copilot uses `.prompt.md` extension for commands.

\*\*\*Gemini uses TOML format for commands.

## Example Workflow

1. **Initialize Mush**
   ```bash
   cd my-project
   mush install --claude --cursor
   ```

2. **Add guidelines**
   ```bash
   echo "# Always use TypeScript" > .mush/guidelines/typescript.md
   ```

3. **Add a skill**
   ```bash
   mkdir -p .mush/skills/api-design
   cat > .mush/skills/api-design/SKILL.md << 'EOF'
   ---
   name: api-design
   description: Helps design RESTful APIs following best practices
   ---
   
   # API Design Guidelines
   
   - Use plural nouns for resources
   - Use HTTP methods appropriately
   - Return appropriate status codes
   EOF
   ```

4. **Sync to agents**
   ```bash
   mush update
   ```

5. **Commit and share**
   ```bash
   git add .mush/
   git commit -m "Add shared AI agent configuration"
   ```

Your team now has consistent AI agent behavior across all their tools.

## License

MIT
