import js from '@eslint/js'
import tseslint from '@typescript-eslint/eslint-plugin'
import tsparser from '@typescript-eslint/parser'
import vue from 'eslint-plugin-vue'
import vueParser from 'vue-eslint-parser'

export default [
  // Base JavaScript rules
  {
    ...js.configs.recommended,
    languageOptions: {
      globals: {
        // Browser globals
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        navigator: 'readonly',
        localStorage: 'readonly',
        sessionStorage: 'readonly',
        fetch: 'readonly',
        setTimeout: 'readonly',
        setInterval: 'readonly',
        clearTimeout: 'readonly',
        clearInterval: 'readonly',
        
        // Node.js globals for build tools
        process: 'readonly',
        Buffer: 'readonly',
      }
    }
  },

  // Vue 3 recommended rules
  ...vue.configs['flat/recommended'],

  // TypeScript rules
  {
    files: ['**/*.ts', '**/*.tsx', '**/*.vue'],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tsparser,
        ecmaVersion: 2022,
        sourceType: 'module',
        extraFileExtensions: ['.vue']
      },
      globals: {
        // Browser globals
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        navigator: 'readonly',
        localStorage: 'readonly',
        sessionStorage: 'readonly',
        fetch: 'readonly',
        setTimeout: 'readonly',
        setInterval: 'readonly',
        clearTimeout: 'readonly',
        clearInterval: 'readonly',
        
        // Node.js globals for build tools
        process: 'readonly',
        Buffer: 'readonly',
        
        // Vue specific
        defineProps: 'readonly',
        defineEmits: 'readonly',
        defineExpose: 'readonly',
        withDefaults: 'readonly',
      }
    },
    plugins: {
      '@typescript-eslint': tseslint,
    },
    rules: {
      ...tseslint.configs.recommended.rules,
      
      // TypeScript specific rules
      '@typescript-eslint/no-unused-vars': ['error', { 
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_'
      }],
      '@typescript-eslint/no-explicit-any': 'warn',
      '@typescript-eslint/explicit-function-return-type': 'off',
      '@typescript-eslint/explicit-module-boundary-types': 'off',
      '@typescript-eslint/no-non-null-assertion': 'warn',
      
      // Vue specific rules
      'vue/html-self-closing': ['error', {
        html: {
          void: 'never',
          normal: 'always',
          component: 'always'
        },
        svg: 'always',
        math: 'always'
      }],
      'vue/max-attributes-per-line': ['error', {
        singleline: { max: 3 },
        multiline: { max: 1 }
      }],
      'vue/component-name-in-template-casing': ['error', 'PascalCase'],
      'vue/component-definition-name-casing': ['error', 'PascalCase'],
      'vue/prop-name-casing': ['error', 'camelCase'],
      'vue/custom-event-name-casing': ['error', 'camelCase'],
      'vue/v-on-event-hyphenation': ['error', 'never'],
      'vue/attribute-hyphenation': ['error', 'never'],
      
      // Accessibility rules
      'vue/require-v-for-key': 'error',
      'vue/no-v-html': 'warn', // Security: warn about v-html usage
      
      // Security rules
      'no-eval': 'error',
      'no-implied-eval': 'error',
      'no-script-url': 'error',
      
      // Code quality rules
      'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
      'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
      'prefer-const': 'error',
      'no-var': 'error',
      'eqeqeq': ['error', 'always'],
      'curly': ['error', 'all'],
      'no-multi-spaces': 'error',
      'no-trailing-spaces': 'error',
      'comma-dangle': ['error', 'only-multiline'],
      'semi': ['error', 'never'],
      'quotes': ['error', 'single', { allowTemplateLiterals: true }],
      
      // Import rules
      'no-duplicate-imports': 'error',
    }
  },

  // Specific configuration for Vue files
  {
    files: ['**/*.vue'],
    rules: {
      // Allow single word component names for specific cases
      'vue/multi-word-component-names': ['error', {
        ignores: ['Toast', 'Modal', 'Avatar', 'Badge', 'Card', 'Button']
      }],
      
      // Require default props for optional props
      'vue/require-default-prop': 'error',
      
      // Prevent unused components
      'vue/no-unused-components': 'error',
      
      // Prevent unused variables in template
      'vue/no-unused-vars': 'error',
    }
  },

  // Test files configuration
  {
    files: ['**/*.test.ts', '**/*.test.js', '**/*.spec.ts', '**/*.spec.js'],
    rules: {
      '@typescript-eslint/no-explicit-any': 'off',
      'no-console': 'off',
    }
  },

  // Configuration files
  {
    files: ['*.config.js', '*.config.ts', 'vite.config.*', 'vitest.config.*'],
    rules: {
      'no-console': 'off',
      '@typescript-eslint/no-var-requires': 'off',
    }
  },

  // Global ignores
  {
    ignores: [
      'node_modules/',
      'vendor/',
      'storage/',
      'bootstrap/cache/',
      'public/build/',
      'resources/js/tests/setup.ts', // Test setup file
      '**/*.d.ts', // Type definition files
    ]
  }
]