import js from '@eslint/js'
import globals from 'globals'
import reactHooks from 'eslint-plugin-react-hooks'
import reactRefresh from 'eslint-plugin-react-refresh'
import tseslint from 'typescript-eslint'
import { defineConfig, globalIgnores } from 'eslint/config'

export default defineConfig([
  globalIgnores(['dist']),
  {
    files: ['**/*.{ts,tsx}'],
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
      'no-restricted-syntax': [
        'error',
        {
          selector: 'JSXElement[openingElement.name.name="a"][openingElement.attributes.0.name.name="href"]',
          message: '❌ Use <Link to="..."> from @tanstack/react-router instead of <a href="..."> for internal navigation. External links (https://, mailto:, etc.) are OK with <a>.',
        },
      ],
    },
  },
])
