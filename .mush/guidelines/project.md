# Project Guidelines

## Git Workflow

- **Always ask permission before pushing** anything to the repository
- **Always check remote tags** before creating a new tag to avoid duplicates:
  ```bash
  git ls-remote --tags origin | sort -t '/' -k 3 -V | tail -10
  ```
- Ask before committing changes unless explicitly instructed to commit

## Code Quality

After writing or modifying PHP code, always run the following checks:

1. **Pint** (code formatting): `./vendor/bin/pint`
2. **PHPStan** (static analysis): `./vendor/bin/phpstan`
3. **Pest** (tests): `./vendor/bin/pest`

Run all three together:
```bash
./vendor/bin/pest && ./vendor/bin/pint && ./vendor/bin/phpstan
```

For mutation testing on specific files:
```bash
XDEBUG_MODE=coverage ./vendor/bin/pest --mutate --path=src/Path/To/File.php
```

## Agent Development

When updating agent logic, paths, or configuration:

1. **Always look up the official documentation** for that agent before making changes
2. Agent documentation sources:
   - Claude Code: https://docs.anthropic.com/en/docs/claude-code
   - Cursor: https://docs.cursor.com
   - GitHub Copilot: https://docs.github.com/en/copilot
   - OpenAI Codex CLI: https://github.com/openai/codex
   - Gemini Code Assist: https://cloud.google.com/gemini/docs
   - JetBrains Junie: https://www.jetbrains.com/help/junie
   - OpenCode: https://opencode.ai/docs

3. Verify the correct file paths and configuration formats for each agent
4. Test changes with `./bin/mush update` in a test project

## Testing

- LSP errors in test files about `$this->artifactPath` are false positives (Pest PHP syntax)
- When adding new features, include tests that cover edge cases
- Aim for good mutation testing coverage when practical
