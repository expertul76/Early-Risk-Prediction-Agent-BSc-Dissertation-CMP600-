<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Student Risk Prediction') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg-primary': '#0a0a0a',
                        'bg-secondary': '#111111',
                        'bg-tertiary': '#1a1a1a',
                        'accent': '#6366f1',
                        'accent-light': '#818cf8',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
</head>
<body>
    <div class="noise-overlay"></div>

    <?php if ($user ?? null): ?>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="px-5 mb-6">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span class="text-sm font-semibold" style="color: var(--text-primary);">Risk Prediction</span>
            </div>
            <span class="text-xs" style="color: var(--text-muted);"><?= htmlspecialchars($user['school_name'] ?? '') ?></span>
        </div>

        <nav>
            <?php
            $currentPath = Request::uri();
            $links = [
                ['/dashboard', 'Dashboard', '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
                ['/courses', 'Courses', '<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>'],
                ['/groups', 'Student Groups', '<path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>'],
                ['/staff', 'Staff', '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
            ];
            foreach ($links as [$path, $label, $iconPath]):
                $isActive = strpos($currentPath, $path) === 0;
            ?>
            <a href="<?= $path ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><?= $iconPath ?></svg>
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="absolute bottom-0 left-0 right-0 p-4 border-t" style="border-color: var(--border);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs" style="color: var(--text-primary);"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
                    <p class="text-xs" style="color: var(--text-muted);"><?= htmlspecialchars(ucfirst($user['role'])) ?></p>
                </div>
                <a href="/logout" class="text-xs" style="color: var(--text-muted);" title="Logout">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php $flash = Response::getFlash(); if ($flash): ?>
        <div class="flash flash-<?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <?php else: ?>
    <!-- No sidebar for auth pages -->
    <main>
        <?php $flash = Response::getFlash(); if ($flash): ?>
        <div class="flash flash-<?= $flash['type'] ?>" style="max-width: 480px; margin: 16px auto;">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>
    <?php endif; ?>

    <script src="/assets/js/app.js"></script>
</body>
</html>
