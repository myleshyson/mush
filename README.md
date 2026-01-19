```
 ▄▄▄▄▄▄▄▄▄▄▄  ▄         ▄  ▄▄▄▄▄▄▄▄▄▄▄  ▄▄▄▄▄▄▄▄▄▄▄  ▄▄▄▄▄▄▄▄▄▄▄  ▄▄        ▄ 
▐░░░░░░░░░░░▌▐░▌       ▐░▌▐░░░░░░░░░░░▌▐░░░░░░░░░░░▌▐░░░░░░░░░░░▌▐░░▌      ▐░▌
▐░█▀▀▀▀▀▀▀▀▀ ▐░▌       ▐░▌▐░█▀▀▀▀▀▀▀▀▀  ▀▀▀▀█░█▀▀▀▀ ▐░█▀▀▀▀▀▀▀█░▌▐░▌░▌     ▐░▌
▐░▌          ▐░▌       ▐░▌▐░▌               ▐░▌     ▐░▌       ▐░▌▐░▌▐░▌    ▐░▌
▐░█▄▄▄▄▄▄▄▄▄ ▐░▌       ▐░▌▐░█▄▄▄▄▄▄▄▄▄      ▐░▌     ▐░▌       ▐░▌▐░▌ ▐░▌   ▐░▌
▐░░░░░░░░░░░▌▐░▌       ▐░▌▐░░░░░░░░░░░▌     ▐░▌     ▐░▌       ▐░▌▐░▌  ▐░▌  ▐░▌
▐░█▀▀▀▀▀▀▀▀▀ ▐░▌       ▐░▌ ▀▀▀▀▀▀▀▀▀█░▌     ▐░▌     ▐░▌       ▐░▌▐░▌   ▐░▌ ▐░▌
▐░▌          ▐░▌       ▐░▌          ▐░▌     ▐░▌     ▐░▌       ▐░▌▐░▌    ▐░▌▐░▌
▐░▌          ▐░█▄▄▄▄▄▄▄█░▌ ▄▄▄▄▄▄▄▄▄█░▌ ▄▄▄▄█░█▄▄▄▄ ▐░█▄▄▄▄▄▄▄█░▌▐░▌     ▐░▐░▌
▐░▌          ▐░░░░░░░░░░░▌▐░░░░░░░░░░░▌▐░░░░░░░░░░░▌▐░░░░░░░░░░░▌▐░▌      ▐░░▌
 ▀            ▀▀▀▀▀▀▀▀▀▀▀  ▀▀▀▀▀▀▀▀▀▀▀  ▀▀▀▀▀▀▀▀▀▀▀  ▀▀▀▀▀▀▀▀▀▀▀  ▀        ▀▀  
```

A CLI tool that syncs AI agent configurations across your team. Define guidelines, skills, and MCP servers once in
`.fusion/` and automatically sync them to Claude Code, Cursor, Copilot, Gemini, OpenCode, Codex, and Junie.

## Installation

### Quick Install (Recommended)

```bash
curl -fsSL https://raw.githubusercontent.com/myleshyson/fusion/main/install.sh | sh
```

### Custom Install Location

```bash
FUSION_INSTALL_DIR=~/.local/bin curl -fsSL https://raw.githubusercontent.com/myleshyson/fusion/main/install.sh | sh
```

### Install Specific Version

```bash
FUSION_VERSION=v1.0.0 curl -fsSL https://raw.githubusercontent.com/myleshyson/fusion/main/install.sh | sh
```

### Manual Download

Download the appropriate binary for your platform from
the [releases page](https://github.com/myleshyson/fusion/releases):

- `fusion-linux-x86_64` - Linux (Intel/AMD)
- `fusion-linux-aarch64` - Linux (ARM64)
- `fusion-macos-x86_64` - macOS (Intel)
- `fusion-macos-aarch64` - macOS (Apple Silicon)
- `fusion-windows-x64.exe` - Windows (64-bit)

### Install via Composer (PHP 8.2+)

If you have PHP and Composer installed:

```bash
composer global require myleshyson/fusion
```

Or add as a dev dependency to your project:

```bash
composer require --dev myleshyson/fusion
./vendor/bin/fusion install
```

## Getting Started

### Initialize a New Project

```bash
# Interactive mode - select which agents to configure
fusion install

# Or specify agents directly
fusion install --claude --cursor --copilot
```

This creates a `.fusion/` directory with:

```
.fusion/
├── guidelines/     # Shared guidelines (markdown files)
├── skills/         # Reusable skills (subdirectories with SKILL.md)
└── mcp.json        # MCP server configurations
```

### Update Agent Files

After modifying your `.fusion/` configuration, sync changes to all detected agents:

```bash
fusion update
```

Fusion automatically detects which agents are configured in your project and updates their respective files.

## Configuration

### Guidelines

Add markdown files to `.fusion/guidelines/` to define shared instructions:

```markdown
<!-- .fusion/guidelines/code-style.md -->

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

Skills are reusable instruction sets that agents can invoke. Create a subdirectory in `.fusion/skills/` with a`SKILL.md`
file:

```markdown
<!-- .fusion/skills/testing/SKILL.md -->
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

Configure MCP (Model Context Protocol) servers in `.fusion/mcp.json`:

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

Fusion transforms this configuration to each agent's expected format.

## Commands

### `fusion install`

Initialize Fusion in a project.

```bash
# Interactive mode
fusion install

# Specify agents
fusion install --claude --cursor --copilot --gemini --opencode --codex --junie

# All agents
fusion install --claude --cursor --copilot --gemini --opencode --codex --junie
```

### `fusion update`

Sync `.fusion/` configuration to all detected agents.

```bash
# Auto-detect agents
fusion update

# Custom paths for additional outputs
fusion update --guideline-path=./custom/RULES.md
fusion update --skill-path=./custom/skills/
fusion update --mcp-path=./custom/mcp.json
```

## Supported Agents

| Agent          | Guidelines                        | Skills              | MCP                     |
|----------------|-----------------------------------|---------------------|-------------------------|
| Claude Code    | `.claude/CLAUDE.md`               | `.claude/skills/`   | `.claude/mcp.json`      |
| Cursor         | `.cursorrules`                    | `.cursor/skills/`   | `.cursor/mcp.json`      |
| GitHub Copilot | `.github/copilot-instructions.md` | `.github/skills/`   | `.vscode/mcp.json`      |
| Gemini         | `GEMINI.md`                       | `.gemini/skills/`   | `.gemini/settings.json` |
| OpenCode       | `AGENTS.md`                       | `.opencode/skills/` | `opencode.json`         |
| OpenAI Codex   | `AGENTS.md`                       | `.codex/skills/`    | -                       |
| Junie          | `.junie/guidelines.md`            | `.junie/skills/`    | `.junie/mcp/mcp.json`   |

## Example Workflow

1. **Initialize Fusion**
   ```bash
   cd my-project
   fusion install --claude --cursor
   ```

2. **Add guidelines**
   ```bash
   echo "# Always use TypeScript" > .fusion/guidelines/typescript.md
   ```

3. **Add a skill**
   ```bash
   mkdir -p .fusion/skills/api-design
   cat > .fusion/skills/api-design/SKILL.md << 'EOF'
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
   fusion update
   ```

5. **Commit and share**
   ```bash
   git add .fusion/
   git commit -m "Add shared AI agent configuration"
   ```

Your team now has consistent AI agent behavior across all their tools.

## License

MIT
