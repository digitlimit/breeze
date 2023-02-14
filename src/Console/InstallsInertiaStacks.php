<?php

namespace Laravel\Breeze\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait InstallsInertiaStacks
{
    /**
     * Install the Inertia Vue Breeze stack.
     *
     * @return int|null
     */
    protected function installInertiaVueStack()
    {
        // Install Inertia...
        if (! $this->requireComposerPackages(['inertiajs/inertia-laravel:^0.6.8', 'laravel/sanctum:^3.2', 'tightenco/ziggy:^1.0'])) {
            return 1;
        }

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@inertiajs/vue3' => '^1.0.2',
                '@tailwindcss/forms' => '^0.5.3',
                '@vitejs/plugin-vue' => '^4.0.0',
                'autoprefixer' => '^10.4.12',
                'postcss' => '^8.4.18',
                'tailwindcss' => '^3.2.1',
                'vue' => '^3.2.41',
            ] + $packages;
        });

        // Controllers...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Controllers'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/app/Http/Controllers', app_path('Http/Controllers'));

        // Requests...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Requests'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/app/Http/Requests', app_path('Http/Requests'));

        // Middleware...
        $this->installMiddlewareAfter('SubstituteBindings::class', '\App\Http\Middleware\HandleInertiaRequests::class');
        $this->installMiddlewareAfter('\App\Http\Middleware\HandleInertiaRequests::class', '\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class');

        copy(__DIR__.'/../../stubs/inertia-common/app/Http/Middleware/HandleInertiaRequests.php', app_path('Http/Middleware/HandleInertiaRequests.php'));

        // Views...
        copy(__DIR__.'/../../stubs/inertia-vue/resources/views/app.blade.php', resource_path('views/app.blade.php'));

        // Components + Pages...
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Components'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Pages'));

        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/Components', resource_path('js/Components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/Layouts', resource_path('js/Layouts'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/Pages', resource_path('js/Pages'));

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(resource_path('js'))
                ->name('*.vue')
                ->notName('Welcome.vue')
            );
        }

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/tests/Feature', base_path('tests/Feature'));

        // Routes...
        copy(__DIR__.'/../../stubs/inertia-common/routes/web.php', base_path('routes/web.php'));
        copy(__DIR__.'/../../stubs/inertia-common/routes/auth.php', base_path('routes/auth.php'));

        // "Dashboard" Route...
        $this->replaceInFile('/home', '/dashboard', resource_path('js/Pages/Welcome.vue'));
        $this->replaceInFile('Home', 'Dashboard', resource_path('js/Pages/Welcome.vue'));
        $this->replaceInFile('/home', '/dashboard', app_path('Providers/RouteServiceProvider.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/default/resources/css/app.css', resource_path('css/app.css'));
        copy(__DIR__.'/../../stubs/default/postcss.config.js', base_path('postcss.config.js'));
        copy(__DIR__.'/../../stubs/inertia-common/tailwind.config.js', base_path('tailwind.config.js'));
        copy(__DIR__.'/../../stubs/inertia-vue/vite.config.js', base_path('vite.config.js'));
        copy(__DIR__.'/../../stubs/inertia-vue/resources/js/app.ts', resource_path('js/app.ts'));

        if ($this->option('ssr')) {
            $this->installInertiaVueSsrStack();
        }

        if ($this->option('typescript')) {
            $this->updateNodePackages(function ($packages) {
                return [
                    '@types/ziggy-js' => '^1.3.2',
                    'typescript' => '^4.9.4',
                    'vue-tsc' => '^1.0.24',
                ] + $packages;
            });
            (new Filesystem)->ensureDirectoryExists(resource_path('js/types'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/resources/js/types', resource_path('js/types'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/types', resource_path('js/types'));
            copy(__DIR__.'/../../stubs/inertia-vue/tsconfig.json', base_path('tsconfig.json'));
            if (file_exists(resource_path('js/bootstrap.js'))) {
                rename(resource_path('js/bootstrap.js'), resource_path('js/bootstrap.ts'));
            }
            $this->replaceInFile('"vite build', '"vue-tsc && vite build', base_path('package.json'));
            $this->removeJavaScript();
        } else {
            copy(__DIR__.'/../../stubs/inertia-common/jsconfig.json', base_path('jsconfig.json'));
            $this->renameFileExtensions('ts', 'js', resource_path('js'));
            $this->replaceInFile('.ts', '.js', base_path('vite.config.js'));
            $this->replaceInFile('.ts', '.js', resource_path('views/app.blade.php'));
            $this->removeTypeScript();
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');
        $this->components->info('Breeze scaffolding installed successfully.');
    }

    /**
     * Install the Inertia Vue SSR stack into the application.
     *
     * @return void
     */
    protected function installInertiaVueSsrStack()
    {
        $this->updateNodePackages(function ($packages) {
            return [
                '@vue/server-renderer' => '^3.2.31',
            ] + $packages;
        });

        copy(__DIR__.'/../../stubs/inertia-vue/resources/js/ssr.ts', resource_path('js/ssr.ts'));
        $this->replaceInFile("input: 'resources/js/app.js',", "input: 'resources/js/app.js',".PHP_EOL."            ssr: 'resources/js/ssr.js',", base_path('vite.config.js'));
        $this->replaceInFile('"vite build', '"vite build && vite build --ssr', base_path('package.json'));
        $this->replaceInFile('/node_modules', '/bootstrap/ssr'.PHP_EOL.'/node_modules', base_path('.gitignore'));
    }

    /**
     * Install the Inertia React Breeze stack.
     *
     * @return int|null
     */
    protected function installInertiaReactStack()
    {
        // Install Inertia...
        if (! $this->requireComposerPackages(['inertiajs/inertia-laravel:^0.6.8', 'laravel/sanctum:^3.2', 'tightenco/ziggy:^1.0'])) {
            return 1;
        }

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@headlessui/react' => '^1.4.2',
                '@inertiajs/react' => '^1.0.2',
                '@tailwindcss/forms' => '^0.5.3',
                '@vitejs/plugin-react' => '^3.0.0',
                'autoprefixer' => '^10.4.12',
                'postcss' => '^8.4.18',
                'tailwindcss' => '^3.2.1',
                'react' => '^18.2.0',
                'react-dom' => '^18.2.0',
            ] + $packages;
        });

        // Controllers...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Controllers'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/app/Http/Controllers', app_path('Http/Controllers'));

        // Requests...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Requests'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/app/Http/Requests', app_path('Http/Requests'));

        // Middleware...
        $this->installMiddlewareAfter('SubstituteBindings::class', '\App\Http\Middleware\HandleInertiaRequests::class');
        $this->installMiddlewareAfter('\App\Http\Middleware\HandleInertiaRequests::class', '\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class');

        copy(__DIR__.'/../../stubs/inertia-common/app/Http/Middleware/HandleInertiaRequests.php', app_path('Http/Middleware/HandleInertiaRequests.php'));

        // Views...
        copy(__DIR__.'/../../stubs/inertia-react/resources/views/app.blade.php', resource_path('views/app.blade.php'));

        // Components + Pages...
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Components'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Pages'));

        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/Components', resource_path('js/Components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/Layouts', resource_path('js/Layouts'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/Pages', resource_path('js/Pages'));

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(resource_path('js'))
                ->name('*.tsx')
                ->notName('Welcome.tsx')
            );
        }

        // Tests...
        $this->installTests();
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/tests/Feature', base_path('tests/Feature'));

        // Routes...
        copy(__DIR__.'/../../stubs/inertia-common/routes/web.php', base_path('routes/web.php'));
        copy(__DIR__.'/../../stubs/inertia-common/routes/auth.php', base_path('routes/auth.php'));

        // "Dashboard" Route...
        $this->replaceInFile('/home', '/dashboard', resource_path('js/Pages/Welcome.tsx'));
        $this->replaceInFile('Home', 'Dashboard', resource_path('js/Pages/Welcome.tsx'));
        $this->replaceInFile('/home', '/dashboard', app_path('Providers/RouteServiceProvider.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/default/resources/css/app.css', resource_path('css/app.css'));
        copy(__DIR__.'/../../stubs/default/postcss.config.js', base_path('postcss.config.js'));
        copy(__DIR__.'/../../stubs/inertia-common/tailwind.config.js', base_path('tailwind.config.js'));
        copy(__DIR__.'/../../stubs/inertia-react/vite.config.js', base_path('vite.config.js'));
        copy(__DIR__.'/../../stubs/inertia-react/resources/js/app.tsx', resource_path('js/app.tsx'));

        if (file_exists(resource_path('js/app.js'))) {
            unlink(resource_path('js/app.js'));
        }

        if ($this->option('ssr')) {
            $this->installInertiaReactSsrStack();
        }

        if ($this->option('typescript')) {
            $this->updateNodePackages(function ($packages) {
                return [
                    '@types/node' => '^18.13.0',
                    '@types/react' => '^18.0.28',
                    '@types/react-dom' => '^18.0.10',
                    '@types/ziggy-js' => '^1.3.2',
                    'typescript' => '^4.9.4',
                ] + $packages;
            });
            (new Filesystem)->ensureDirectoryExists(resource_path('js/types'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/resources/js/types', resource_path('js/types'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/types', resource_path('js/types'));
            copy(__DIR__.'/../../stubs/inertia-react/tsconfig.json', base_path('tsconfig.json'));
            if (file_exists(resource_path('js/bootstrap.js'))) {
                rename(resource_path('js/bootstrap.js'), resource_path('js/bootstrap.ts'));
            }
            $this->replaceInFile('.vue', '.tsx', base_path('tailwind.config.js'));
            $this->replaceInFile('"vite build', '"tsc && vite build', base_path('package.json'));
            $this->removeJavaScript();
        } else {
            copy(__DIR__.'/../../stubs/inertia-common/jsconfig.json', base_path('jsconfig.json'));
            $this->renameFileExtensions('tsx', 'jsx', resource_path('js'));
            $this->replaceInFile('.vue', '.jsx', base_path('tailwind.config.js'));
            $this->replaceInFile('.tsx', '.jsx', base_path('vite.config.js'));
            $this->replaceInFile('.tsx', '.jsx', resource_path('js/app.jsx'));
            $this->replaceInFile('.tsx', '.jsx', resource_path('js/ssr.jsx'));
            $this->replaceInFile('.tsx', '.jsx', resource_path('views/app.blade.php'));
            $this->removeTypeScript();
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');
        $this->components->info('Breeze scaffolding installed successfully.');
    }

    /**
     * Install the Inertia React SSR stack into the application.
     *
     * @return void
     */
    protected function installInertiaReactSsrStack()
    {
        copy(__DIR__.'/../../stubs/inertia-react/resources/js/ssr.tsx', resource_path('js/ssr.tsx'));
        $this->replaceInFile("input: 'resources/js/app.tsx',", "input: 'resources/js/app.tsx',".PHP_EOL."            ssr: 'resources/js/ssr.tsx',", base_path('vite.config.js'));
        $this->replaceInFile('vite build', 'vite build && vite build --ssr', base_path('package.json'));
        $this->replaceInFile('/node_modules', '/bootstrap/ssr'.PHP_EOL.'/node_modules', base_path('.gitignore'));
    }

    /**
     * Remove TypeScript code from the project.
     *
     * @return void
     */
    protected function removeTypeScript()
    {
        $finder = (new Finder)
                ->in(resource_path('js'))
                ->name('/\.(jsx?|vue)/');

        foreach ($finder as $file) {
            $contents = $file->getContents();

            // Remove lines containing the line-wise `// ts-only` marker.
            $contents = preg_replace('/^.*\/\/\s?ts-only$(?:\r\n|\n)?/m', '', $contents);

            // Remove inline `/* ts-begin */ ... /* ts-end */` blocks.
            $contents = preg_replace('/\/\*\s?ts-begin.*?ts-end\s?\*\//s', '', $contents);

            // Remove line-wise `// ts-begin ... // ts-end` blocks.
            $contents = preg_replace('/\/\/\s?ts-begin.*?\/\/\s?ts-end(?:\r\n|\n)?/s', '', $contents);

            // Remove lines containing the line-wise `// js-(begin|end)` markers.
            $contents = preg_replace('/^.*\/\/\s?js-(begin|end)$(?:\r\n|\n)?/m', '', $contents);

            // Remove Vue component `lang="ts"` attribute
            $contents = str_replace(' lang="ts"', '', $contents);

            file_put_contents($file->getPathname(), $contents);
        }
    }

    /**
     * Remove JavaScript-only code from the project.
     *
     * @return void
     */
    protected function removeJavaScript()
    {
        $finder = (new Finder)
                ->in(resource_path('js'))
                ->name('/\.(tsx?|vue)/');

        foreach ($finder as $file) {
            $contents = $file->getContents();

            // Remove line-wise `// js-begin ... // js-end` blocks
            $contents = preg_replace('/\/\/ js-begin.*?\/\/ js-end(?:\r\n|\n)?/s', '', $contents);

            // Remove line-wise `// ts-(only|begin|end)` markers.
            $contents = preg_replace('/ *\/\/\s?ts-(only|begin|end)/', '', $contents);

            // Remove /* ts-begin */ and /* ts-end */ markers.
            $contents = preg_replace('/\/\*\s?ts-(begin|end)\s?\*\//', '', $contents);

            file_put_contents($file->getPathname(), $contents);
        }
    }
}
