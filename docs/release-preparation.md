# Release Prepartion Plan (draft)

Steps:
1. Pre-validation
2. Validation
3. Documentation verification
4. Bundle preparation

## Before validation

### Update language files

1. Regenerate the POT (replaces your xgettext command)
```bash
wp i18n make-pot . languages/openporte.pot \
  --domain=openporte \
  --exclude=vendor,local,tests \
  --slug=openporte
```

2. Update existing PO files from the new POT
```bash
wp i18n update-po languages/openporte.pot languages/
sed -i '' \
  's/Project-Id-Version: OpenPorte Spam Protection.*/Project-Id-Version: OpenPorte Spam Protection 1.27.1"/' \
  languages/openporte-*.po
```

3. Compile MO binaries
```bash
wp i18n make-mo languages/
```

## Validation

See acceptance/openporte-v1.27.0.md (no change in validation for v1.27.1)


## Documentation verification


## Bundle preparation
First merge PR, then:

```bash
# 1. After GitHub merge:
git checkout main
git fetch origin
git merge --ff-only origin/main   # fast-forward, no new commit needed

# 2. Tag on main (now pointing at the merge commit):
VERSION="v1.27.1"
git tag -a ${VERSION} -m "Release ${VERSION}"
git push origin ${VERSION}

# 3. Create bundle
wp dist-archive .
```



