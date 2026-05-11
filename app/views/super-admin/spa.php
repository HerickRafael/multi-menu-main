<?php
declare(strict_types=1);
/** @var string $title */
/** @var string $csrfToken */
/** @var string $superAdminName */

$moduleBase = htmlspecialchars(rtrim(base_url('assets/superadmin/js'), '/'), ENT_QUOTES, 'UTF-8');
$csrf = htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8');
$name = htmlspecialchars($superAdminName ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="csrf-token" content="<?= $csrf ?>">
  <meta name="superadmin-name" content="<?= $name ?>">
  <meta name="superadmin-logout" content="<?= htmlspecialchars(base_url('superadmin/logout'), ENT_QUOTES, 'UTF-8') ?>">
  <meta name="app-base-path" content="">
  <title><?= htmlspecialchars($title ?? 'Super Admin', ENT_QUOTES, 'UTF-8') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            sidebar: '#111827',
          },
        },
      },
    };
  </script>
  <script type="importmap">
  {
    "imports": {
      "react": "https://esm.sh/react@18.2.0",
      "react/jsx-runtime": "https://esm.sh/react@18.2.0/jsx-runtime",
      "react-dom/client": "https://esm.sh/react-dom@18.2.0/client",
      "htm": "https://esm.sh/htm@3.1.1",
      "lucide-react": "https://esm.sh/lucide-react@0.460.0?deps=react@18.2.0",
      "@/components/ui/button": "<?= $moduleBase ?>/components/ui/button.js",
      "@/components/ui/card": "<?= $moduleBase ?>/components/ui/card.js",
      "@/components/ui/badge": "<?= $moduleBase ?>/components/ui/badge.js",
      "@/components/ui/utils": "<?= $moduleBase ?>/components/ui/utils.js"
    }
  }
  </script>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
  <div id="superadmin-root"></div>
  <script type="module" src="<?= $moduleBase ?>/main.js"></script>
</body>
</html>
