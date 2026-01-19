<?php

use Myleshyson\Fusion\Compilers\GuidelinesCompiler;
use Myleshyson\Fusion\Compilers\SkillsCompiler;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../artifacts/compilers';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

// ==================== GuidelinesCompiler Tests ====================

describe('GuidelinesCompiler', function () {
    it('returns empty string when directory does not exist', function () {
        $compiler = new GuidelinesCompiler;
        $result = $compiler->compile("{$this->artifactPath}/nonexistent");
        expect($result)->toBe('');
    });

    it('returns empty string when directory has no markdown files', function () {
        mkdir("{$this->artifactPath}/guidelines", 0777, true);
        file_put_contents("{$this->artifactPath}/guidelines/readme.txt", 'Not markdown');

        $compiler = new GuidelinesCompiler;
        $result = $compiler->compile("{$this->artifactPath}/guidelines");
        expect($result)->toBe('');
    });

    it('compiles single markdown file', function () {
        mkdir("{$this->artifactPath}/guidelines", 0777, true);
        file_put_contents("{$this->artifactPath}/guidelines/test.md", '# Test Guideline');

        $compiler = new GuidelinesCompiler;
        $result = $compiler->compile("{$this->artifactPath}/guidelines");
        expect($result)->toBe('# Test Guideline');
    });

    it('compiles multiple files sorted alphabetically', function () {
        mkdir("{$this->artifactPath}/guidelines", 0777, true);
        // Create files in reverse alphabetical order to ensure ksort is tested
        file_put_contents("{$this->artifactPath}/guidelines/z-last.md", '# Last');
        file_put_contents("{$this->artifactPath}/guidelines/a-first.md", '# First');
        file_put_contents("{$this->artifactPath}/guidelines/m-middle.md", '# Middle');

        $compiler = new GuidelinesCompiler;
        $result = $compiler->compile("{$this->artifactPath}/guidelines");

        // The exact order matters - must be alphabetically sorted
        expect($result)->toBe("# First\n\n# Middle\n\n# Last");
    });

    it('sorts files alphabetically regardless of creation order', function () {
        // Create a subdirectory with numeric prefix to test sorting more explicitly
        mkdir("{$this->artifactPath}/guidelines-sort", 0777, true);
        // Create in non-alphabetical order: 3, 1, 2
        file_put_contents("{$this->artifactPath}/guidelines-sort/3-third.md", 'Third');
        file_put_contents("{$this->artifactPath}/guidelines-sort/1-first.md", 'First');
        file_put_contents("{$this->artifactPath}/guidelines-sort/2-second.md", 'Second');

        $compiler = new GuidelinesCompiler;
        $result = $compiler->compile("{$this->artifactPath}/guidelines-sort");

        // Result must be in alphabetical order by filename
        expect($result)->toBe("First\n\nSecond\n\nThird");
    });

    it('skips empty files', function () {
        mkdir("{$this->artifactPath}/guidelines", 0777, true);
        file_put_contents("{$this->artifactPath}/guidelines/empty.md", '');
        file_put_contents("{$this->artifactPath}/guidelines/whitespace.md", '   ');
        file_put_contents("{$this->artifactPath}/guidelines/real.md", '# Real Content');

        $compiler = new GuidelinesCompiler;
        $result = $compiler->compile("{$this->artifactPath}/guidelines");
        expect($result)->toBe('# Real Content');
    });

    it('trims whitespace from content', function () {
        mkdir("{$this->artifactPath}/guidelines", 0777, true);
        file_put_contents("{$this->artifactPath}/guidelines/test.md", "  \n# Test\n  ");

        $compiler = new GuidelinesCompiler;
        $result = $compiler->compile("{$this->artifactPath}/guidelines");
        expect($result)->toBe('# Test');
    });

    it('ignores .gitignore files', function () {
        mkdir("{$this->artifactPath}/guidelines", 0777, true);
        file_put_contents("{$this->artifactPath}/guidelines/.gitignore", '*');
        file_put_contents("{$this->artifactPath}/guidelines/test.md", '# Test');

        $compiler = new GuidelinesCompiler;
        $result = $compiler->compile("{$this->artifactPath}/guidelines");
        expect($result)->toBe('# Test');
    });
});

// ==================== SkillsCompiler Tests ====================
// Skills use a subdirectory structure where each skill is a directory containing a SKILL.md file
// Example: skills/tailwind/SKILL.md, skills/testing/SKILL.md
// Skills return structured data with name, description, and content parsed from YAML frontmatter

describe('SkillsCompiler', function () {
    it('returns empty array when directory does not exist', function () {
        $compiler = new SkillsCompiler;
        $result = $compiler->compile("{$this->artifactPath}/nonexistent");
        expect($result)->toBe([]);
    });

    it('returns empty array when directory has no skill subdirectories', function () {
        mkdir("{$this->artifactPath}/skills", 0777, true);
        // Flat files should be ignored - only subdirectories with SKILL.md are recognized
        file_put_contents("{$this->artifactPath}/skills/readme.txt", 'Not a skill');
        file_put_contents("{$this->artifactPath}/skills/random.md", 'Also not a skill');

        $compiler = new SkillsCompiler;
        $result = $compiler->compile("{$this->artifactPath}/skills");
        expect($result)->toBe([]);
    });

    it('compiles single skill directory without frontmatter', function () {
        mkdir("{$this->artifactPath}/skills/test", 0777, true);
        file_put_contents("{$this->artifactPath}/skills/test/SKILL.md", '# Test Skill');

        $compiler = new SkillsCompiler;
        $result = $compiler->compile("{$this->artifactPath}/skills");

        expect($result)->toHaveKey('test');
        expect($result['test']['name'])->toBe('test');
        expect($result['test']['description'])->toBe('');
        expect($result['test']['content'])->toBe('# Test Skill');
    });

    it('parses YAML frontmatter to extract name and description', function () {
        mkdir("{$this->artifactPath}/skills/tailwind", 0777, true);
        $content = <<<'SKILL'
---
name: tailwind-css
description: Helps with Tailwind CSS styling and utilities
---

# Tailwind CSS Skill

Use this for styling.
SKILL;
        file_put_contents("{$this->artifactPath}/skills/tailwind/SKILL.md", $content);

        $compiler = new SkillsCompiler;
        $result = $compiler->compile("{$this->artifactPath}/skills");

        expect($result)->toHaveKey('tailwind');
        expect($result['tailwind']['name'])->toBe('tailwind-css');
        expect($result['tailwind']['description'])->toBe('Helps with Tailwind CSS styling and utilities');
        expect($result['tailwind']['content'])->toBe("# Tailwind CSS Skill\n\nUse this for styling.");
    });

    it('compiles multiple skill directories sorted alphabetically', function () {
        mkdir("{$this->artifactPath}/skills/z-skill", 0777, true);
        mkdir("{$this->artifactPath}/skills/a-skill", 0777, true);
        file_put_contents("{$this->artifactPath}/skills/z-skill/SKILL.md", '# Z Skill');
        file_put_contents("{$this->artifactPath}/skills/a-skill/SKILL.md", '# A Skill');

        $compiler = new SkillsCompiler;
        $result = $compiler->compile("{$this->artifactPath}/skills");

        expect(array_keys($result))->toBe(['a-skill', 'z-skill']);
        expect($result['a-skill']['content'])->toBe('# A Skill');
        expect($result['z-skill']['content'])->toBe('# Z Skill');
    });

    it('sorts skill directories alphabetically regardless of creation order', function () {
        mkdir("{$this->artifactPath}/skills-sort", 0777, true);
        // Create in non-alphabetical order: 3, 1, 2
        mkdir("{$this->artifactPath}/skills-sort/3-third", 0777, true);
        mkdir("{$this->artifactPath}/skills-sort/1-first", 0777, true);
        mkdir("{$this->artifactPath}/skills-sort/2-second", 0777, true);
        file_put_contents("{$this->artifactPath}/skills-sort/3-third/SKILL.md", 'Third');
        file_put_contents("{$this->artifactPath}/skills-sort/1-first/SKILL.md", 'First');
        file_put_contents("{$this->artifactPath}/skills-sort/2-second/SKILL.md", 'Second');

        $compiler = new SkillsCompiler;
        $result = $compiler->compile("{$this->artifactPath}/skills-sort");

        // Array keys must be in alphabetical order
        expect(array_keys($result))->toBe(['1-first', '2-second', '3-third']);
        // Verify content values match the sorted order
        expect($result['1-first']['content'])->toBe('First');
        expect($result['2-second']['content'])->toBe('Second');
        expect($result['3-third']['content'])->toBe('Third');
    });

    it('skips skill directories with empty SKILL.md files', function () {
        mkdir("{$this->artifactPath}/skills/empty", 0777, true);
        mkdir("{$this->artifactPath}/skills/whitespace", 0777, true);
        mkdir("{$this->artifactPath}/skills/real", 0777, true);
        file_put_contents("{$this->artifactPath}/skills/empty/SKILL.md", '');
        file_put_contents("{$this->artifactPath}/skills/whitespace/SKILL.md", '   ');
        file_put_contents("{$this->artifactPath}/skills/real/SKILL.md", '# Real');

        $compiler = new SkillsCompiler;
        $result = $compiler->compile("{$this->artifactPath}/skills");

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('real');
        expect($result['real']['content'])->toBe('# Real');
    });

    it('trims whitespace from skill content', function () {
        mkdir("{$this->artifactPath}/skills/test", 0777, true);
        file_put_contents("{$this->artifactPath}/skills/test/SKILL.md", "  \n# Test\n  ");

        $compiler = new SkillsCompiler;
        $result = $compiler->compile("{$this->artifactPath}/skills");
        expect($result['test']['content'])->toBe('# Test');
    });

    it('uses directory name as fallback when name is not in frontmatter', function () {
        mkdir("{$this->artifactPath}/skills/my-skill", 0777, true);
        $content = <<<'SKILL'
---
description: A skill without a name field
---

# Content here
SKILL;
        file_put_contents("{$this->artifactPath}/skills/my-skill/SKILL.md", $content);

        $compiler = new SkillsCompiler;
        $result = $compiler->compile("{$this->artifactPath}/skills");

        expect($result['my-skill']['name'])->toBe('my-skill');
        expect($result['my-skill']['description'])->toBe('A skill without a name field');
    });
});
