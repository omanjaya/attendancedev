import js from '@eslint/js'
import globals from 'globals'
import reactHooks from 'eslint-plugin-react-hooks'
import reactRefresh from 'eslint-plugin-react-refresh'
import tseslint from 'typescript-eslint'
import { defineConfig, globalIgnores } from 'eslint/config'

export default defineConfig([
  globalIgnores(['dist', 'e2e/**/*']),
  {
    files: ['**/*.{ts,tsx}'],
    ignores: ['e2e/**/*'],
    extends: [
      js.configs.recommended,
      tseslint.configs.recommended,
      reactHooks.configs.flat.recommended,
      reactRefresh.configs.vite,
    ],
    languageOptions: {
      ecmaVersion: 2020,
      globals: globals.browser,
    },
    rules: {
      // Prevent internal links using <a href> instead of <Link to>
      // TODO: Re-enable with smarter selector that excludes external links
      // 'no-restricted-syntax': [
      //   'error',
      //   {
      //     selector: 'JSXElement[openingElement.name.name="a"][openingElement.attributes.0.name.name="href"]',
      //     message: '❌ Use <Link to="..."> from @tanstack/react-router instead of <a href="..."> for internal navigation.',
      //   },
      // ],
      // TypeScript rules - warn instead of error for gradual migration
      '@typescript-eslint/no-explicit-any': 'warn',
      '@typescript-eslint/no-unused-vars': ['error', {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_',
        caughtErrorsIgnorePattern: '^_'
      }],
      '@typescript-eslint/no-empty-object-type': 'off',
      // React hooks - adjust strictness for newer plugin rules
      'react-hooks/exhaustive-deps': 'warn',
      'react-hooks/rules-of-hooks': 'error',
      // These are experimental rules from react-hooks plugin - disable
      'react-hooks/refs': 'off',
      'react-hooks/immutability': 'off',
      'react-hooks/set-state-in-effect': 'off',
      'react-hooks/incompatible-library': 'off',
      'react-hooks/purity': 'off',
      'react-hooks/preserve-manual-memoization': 'off',
      // React refresh - warn instead of error for non-component exports
      'react-refresh/only-export-components': 'warn',
    },
  },
])
