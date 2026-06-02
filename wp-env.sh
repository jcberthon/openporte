#!/usr/bin/env bash
set -euo pipefail

if [ -f '.wp-env.conf' ]; then
  # shellcheck source=.wp-env.conf
  source .wp-env.conf
else
  echo 'Please create a .wp-env.conf file with the following content:' >&2
  echo 'REMOTE_USER=your-ssh-username' >&2
  echo 'REMOTE_HOST=your-ssh-host' >&2
  echo 'REMOTE_PATH=path/to/remote/directory' >&2
  exit 1
fi

if [ ! -d '.git' ]; then
  echo 'This script must be run from the root of the repository.' >&2
  exit 1
fi

rsync -az --delete --exclude='.git' --exclude='.DS_Store' --exclude='local' \
    --exclude='protect,r .wordpress-org' --exclude='protect,r .distignore' \
    . ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/ && \
ssh ${REMOTE_USER}@${REMOTE_HOST} "source ~/.wpenvrc && cd ~/${REMOTE_PATH} && wp-env $*"

