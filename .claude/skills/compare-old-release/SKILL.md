---
name: compare-old-release
description: How to answer "did this work in <tag>?" for this plugin - why a git worktree with symlinked vendor/ lies, and the checkout-files + wp eval-file method that works. Use when comparing current behaviour against an older release or tag.
---

# Comparing behaviour against an older release


To answer "did this work in <tag>?", **do not** `git worktree add` and symlink `vendor/` into it.
`vendor/composer/autoload_psr4.php` maps `Osec\` to `$baseDir . '/src'`, where `$baseDir` is resolved from the
autoloader's own location - so a symlinked `vendor/` silently loads `src/` from the **main checkout**, and the
old tag appears to behave like current `HEAD`. Verify with
`php -r 'require "vendor/autoload.php"; echo (new ReflectionClass(Osec\App\Model\PostTypeEvent\EventInstance::class))->getFileName();'`
before trusting any worktree result. A real `cp -a vendor` works, but the old tag's bootstrap may not survive a
current dev database (e.g. pre-77524dd5 `verifySqlSchema()` aborts the PHPUnit bootstrap).

What works reliably instead, on the live dev site:

1. `git checkout <tag> -- <the few files in the code path>` (or all of `src/`)
2. drive the real path with `/usr/local/bin/wp eval-file <script>` - each WP-CLI call is a fresh process, so
   there is no opcache/worker staleness to fight
3. `git checkout HEAD -- <same paths>` to restore

Two traps when restoring: `git checkout <tag> -- src/` **resurrects files deleted since** that tag (they come
back staged as added, and `git checkout HEAD -- src/` will not remove them - `git rm` them explicitly), and a
`git diff --stat <tag>..HEAD -- <path>` that prints nothing means the file is byte-identical, which is usually
a faster answer than any checkout.
