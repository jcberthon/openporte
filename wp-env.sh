#!/usr/bin/env bash
# wp-env.sh - Remote WordPress environment helper for OpenPorte plugin testing
#
# USAGE:
#   ./wp-env.sh [OPTIONS] [wp-env COMMAND]
#
# DESCRIPTION:
#   Synchronizes the local repository to a remote host and runs wp-env commands
#   there. Useful for testing the plugin with different PHP and WordPress versions.
#
# OPTIONS:
#   --php-version, -p  Override PHP version on the remote (via WP_ENV_PHP_VERSION)
#   --wp-version, -w   Override WordPress Core version on the remote (via WP_ENV_CORE)
#
#   These options can appear anywhere in the command line and are removed before
#   passing arguments to wp-env. Environment variables are set on the remote host
#   via ssh -o SetEnv, so no remote filesystem modifications are needed.
#
# EXAMPLES:
#   # Start with default versions
#   ./wp-env.sh start
#
#   # Override PHP version
#   ./wp-env.sh -p 7.3 start
#
#   # Override WordPress version
#   ./wp-env.sh -w WordPress/WordPress#6.5 start
#
#   # Override both
#   ./wp-env.sh -p 7.3 -w WordPress/WordPress#trunk start --no-cache
#
#   # Mixed with wp-env arguments
#   ./wp-env.sh start --php-version 8.1 --no-cache
#
# CONFIGURATION:
#   Create a .wp-env.conf file with:
#     REMOTE_USER=your-ssh-username
#     REMOTE_HOST=your-ssh-host
#     REMOTE_PATH=path/to/remote/directory
#
#   The remote host must have ~/.wpenvrc sourced (handled automatically by this script).
#   Requires OpenSSH >= 7.8 for the -o SetEnv feature.
#
# ACCESSING REMOTE SERVER:
#   If the remote web server cannot be accessed directly at http://REMOTE_HOST:8888/,
#   create an SSH tunnel: ssh -f -N -L 8888:localhost:8888 ${REMOTE_USER}@${REMOTE_HOST}
#
# IMPLEMENTATION:
#   See docs/maintenance-testing.md for detailed technical documentation.
#
set -euo pipefail

# Parse CLI options for PHP/WP version override
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

# Default values for version overrides
PHP_VERSION_OVERRIDE=""
WP_VERSION_OVERRIDE=""
SSH_SETENV_OPTS=()
REMAINING_ARGS=()

# Parse all arguments for version overrides
# This allows --php-version/-p and --wp-version/-w to appear anywhere in the command line
while [[ $# -gt 0 ]]; do
  case "$1" in
    --php-version|-p)
      PHP_VERSION_OVERRIDE="$2"
      shift 2
      ;;
    --wp-version|-w)
      WP_VERSION_OVERRIDE="$2"
      shift 2
      ;;
    *)
      REMAINING_ARGS+=("$1")
      shift
      ;;
  esac
done
set -- "${REMAINING_ARGS[@]}"

# Build SetEnv options for SSH if overrides specified
if [[ -n "$PHP_VERSION_OVERRIDE" ]]; then
  SSH_SETENV_OPTS+=("-o" "SetEnv=WP_ENV_PHP_VERSION=$PHP_VERSION_OVERRIDE")
fi
if [[ -n "$WP_VERSION_OVERRIDE" ]]; then
  SSH_SETENV_OPTS+=("-o" "SetEnv=WP_ENV_CORE=$WP_VERSION_OVERRIDE")
fi

rsync -az --delete --exclude='.git' --exclude='.DS_Store' --exclude='local' \
    --exclude='protect,r .wordpress-org' --exclude='protect,r .distignore' \
    . ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/ && \
ssh "${SSH_SETENV_OPTS[@]}" ${REMOTE_USER}@${REMOTE_HOST} "source ~/.wpenvrc && cd ~/${REMOTE_PATH} && wp-env $*"

