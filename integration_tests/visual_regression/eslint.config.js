/**
 * Code style of the visual regression suite (maintainer 2026-10-20), checked with ESLint + @stylistic:
 *
 *   npm run lint        report
 *   npm run lint:fix    fix what can be fixed
 *
 * - 4 spaces (as .editorconfig), single quotes, semicolons, lines up to 120 characters (as PHP's PSR-12 here).
 * - Braces with inner spaces, WordPress style: `const { test, expect } = require(...)`, `{ fullPage: true }`.
 * - Object and array literals with two or more entries: one entry per line, trailing comma.
 * - No string concatenation with +: template literals (backticks). Long text spans lines inside one template literal;
 *   about() collapses its whitespace.
 *
 * Also used for bin/screenshots/shoot.js (its `npm run lint` points here). Only for code written for this suite and
 * its tools: older JS (Mocha suite, js_smoke, public/js) is not reformatted; calendar_block uses wp-scripts.
 */
const stylistic = require('@stylistic/eslint-plugin');

module.exports = [
    {
        ignores: [
            'node_modules/**',
            '.auth/**',
        ],
    },
    {
        files: ['**/*.js'],
        languageOptions: {
            ecmaVersion: 2023,
            sourceType: 'commonjs',
            globals: {
                require: 'readonly',
                module: 'writable',
                process: 'readonly',
                __dirname: 'readonly',
                console: 'readonly',
                Buffer: 'readonly',
                setTimeout: 'readonly',
                // Browser code inside page.evaluate() / waitForFunction().
                window: 'readonly',
                document: 'readonly',
                getComputedStyle: 'readonly',
                wp: 'readonly',
            },
        },
        plugins: {
            '@stylistic': stylistic,
        },
        rules: {
            '@stylistic/indent': [
                'error',
                4,
            ],
            '@stylistic/quotes': [
                'error',
                'single',
                { avoidEscape: true },
            ],
            '@stylistic/semi': [
                'error',
                'always',
            ],
            '@stylistic/max-len': [
                'error',
                {
                    code: 120,
                    ignoreUrls: true,
                    ignoreRegExpLiterals: true,
                },
            ],
            '@stylistic/comma-dangle': [
                'error',
                'always-multiline',
            ],
            '@stylistic/object-curly-spacing': [
                'error',
                'always',
            ],
            '@stylistic/object-curly-newline': [
                'error',
                {
                    ObjectExpression: {
                        minProperties: 2,
                        multiline: true,
                        consistent: true,
                    },
                    ObjectPattern: {
                        multiline: true,
                        consistent: true,
                    },
                },
            ],
            '@stylistic/object-property-newline': [
                'error',
                { allowAllPropertiesOnSameLine: false },
            ],
            '@stylistic/array-bracket-newline': [
                'error',
                {
                    multiline: true,
                    minItems: 2,
                },
            ],
            '@stylistic/array-element-newline': [
                'error',
                {
                    ArrayExpression: { minItems: 2 },
                    ArrayPattern: 'never',
                },
            ],
            '@stylistic/eol-last': [
                'error',
                'always',
            ],
            '@stylistic/no-trailing-spaces': 'error',
            'prefer-template': 'error',
            'no-restricted-syntax': [
                'error',
                {
                    selector: `BinaryExpression[operator='+']:matches([left.type='TemplateLiteral'],
                        [right.type='TemplateLiteral'], [left.raw=/^['"]/], [right.raw=/^['"]/])`,
                    message: 'No string concatenation with +: use a template literal (backticks).',
                },
            ],
        },
    },
];
