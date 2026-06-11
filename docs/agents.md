# Release Checklist

## Files to update (in order)

1. **`composer.json`** — bump `"version"` to the new version
2. **`src/AddonsRegistry.php`** — add any new addon entries (if applicable)

## Steps

```bash
# 1. Edit the files above, then:
git add composer.json src/AddonsRegistry.php
git commit -m "chore: bump version to vX.X.X"

# 2. Tag and push
git tag vX.X.X
git push origin main
git push origin vX.X.X
```

## After pushing

- Trigger a **Packagist** update (webhook fires automatically, or hit "Update" manually) so `composer require acrossai-co/addons-page ^X.X.X` resolves.
