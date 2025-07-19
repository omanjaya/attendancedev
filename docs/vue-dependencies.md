# Vue + shadcn/ui Dependencies for AttendanceHub

## Required NPM Packages

```bash
# Core Vue 3 packages
npm install vue@^3.3.0 @vitejs/plugin-vue

# shadcn/ui Vue components (install via CLI)
npx shadcn-vue@latest init
npx shadcn-vue@latest add card
npx shadcn-vue@latest add button
npx shadcn-vue@latest add badge

# Lucide Vue icons
npm install lucide-vue-next

# TypeScript support (optional)
npm install -D typescript vue-tsc @vue/tsconfig
```

## Package.json devDependencies to add:

```json
{
  "devDependencies": {
    "@vitejs/plugin-vue": "^4.4.0",
    "vue": "^3.3.4",
    "vue-tsc": "^1.8.15",
    "typescript": "^5.2.2"
  },
  "dependencies": {
    "lucide-vue-next": "^0.292.0"
  }
}
```

## shadcn/ui Configuration

After running `npx shadcn-vue@latest init`, update your `components.json`:

```json
{
  "$schema": "https://ui.shadcn.com/schema.json",
  "style": "default",
  "rsc": false,
  "tsx": false,
  "tailwind": {
    "config": "tailwind.config.js",
    "css": "resources/css/app.css",
    "baseColor": "slate",
    "cssVariables": true
  },
  "aliases": {
    "components": "@/components",
    "utils": "@/lib/utils"
  }
}
```

## Installation Steps:

1. **Install core dependencies:**

   ```bash
   npm install vue@^3.3.0 @vitejs/plugin-vue lucide-vue-next
   ```

2. **Initialize shadcn/ui:**

   ```bash
   npx shadcn-vue@latest init
   ```

3. **Add required components:**

   ```bash
   npx shadcn-vue@latest add card button badge
   ```

4. **Update Vite config** (already done in the files above)

5. **Build the assets:**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

## File Structure After Setup:

```
resources/
├── js/
│   ├── components/
│   │   ├── ui/           # shadcn/ui components
│   │   │   ├── card.vue
│   │   │   ├── button.vue
│   │   │   └── badge.vue
│   │   └── AttendanceDashboard.vue
│   ├── lib/
│   │   └── utils.ts      # shadcn/ui utilities
│   ├── types/
│   │   └── shadcn.d.ts
│   └── app.js
└── css/
    └── app.css           # Tailwind + shadcn/ui styles
```

## Expected Result:

After completing the installation, you'll have:

- ✅ Fully functional Vue 3 dashboard
- ✅ shadcn/ui components working
- ✅ Dark/light theme toggle
- ✅ Responsive design
- ✅ Laravel API integration ready
- ✅ TypeScript support
- ✅ Real-time clock updates
- ✅ Professional modern UI

## Access URLs:

- Tailwind Dashboard: `/dashboard`
- Vue Sidebar Dashboard: `/vue-dashboard`
- API Endpoint: `/api/v1/dashboard/attendance`
