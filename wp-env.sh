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
#   passing arguments to wp-env. Environment variables are exported inline in the
#   remote shell command.
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
#
# ACCESSING REMOTE SERVER:
#   If the remote web server cannot be accessed directly at http://REMOTE_HOST:8888/,
#   create an SSH tunnel: ssh -f -N -L 8888:localhost:8888 ${REMOTE_USER}@${REMOTE_HOST}
#
# IMPLEMENTATION:
#   See docs/maintenance-testing.md for detailed technical documentation.
#
set -euo pipefail

# Verify the configuration file exists and source it to get REMOTE_USER, REMOTE_HOST, and REMOTE_PATH
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

# Verify that the necessary environment variables are set
if [ -z "${REMOTE_USER:-}" ] || [ -z "${REMOTE_HOST:-}" ] || [ -z "${REMOTE_PATH:-}" ]; then
  echo 'REMOTE_USER, REMOTE_HOST, and REMOTE_PATH must be set in .wp-env.conf' >&2
  exit 1
fi

# Verify that rsync is installed
if ! command -v rsync >/dev/null 2>&1; then
  echo 'rsync is required but not found. Please install rsync and try again.' >&2
  exit 1
fi

if ! ssh -q -o BatchMode=yes -o ConnectTimeout=5 ${REMOTE_USER}@${REMOTE_HOST} 'echo 2>&1'; then
  echo "Unable to connect to ${REMOTE_USER}@${REMOTE_HOST}. Please check your SSH configuration and try again." >&2
  exit 1
fi

if [ -z "$SSH_AUTH_SOCK" ]; then
  echo "Warning: SSH agent is not running or SSH_AUTH_SOCK is not set." >&2
  echo "         Please ensure your SSH agent or you might not be able to authenticate." >&2
fi

if [ ! -f '.wpenvrc' ]; then
  echo "Warning: .wpenvrc file exists locally. This file is meant to be used on the remote host to define environment variables." >&2
  echo "         The local .wpenvrc will be ignored and not copied to the remote host." >&2
  echo "         Please ensure that any necessary environment variables (e.g. PATH) are defined" >&2
  echo "         or defined them in ~/.wpenvrc file locally, it will be copied to the remote host automatically." >&2
fi

# Verify that the script is being run from the root of a git repository
if [ ! -d '.git' ]; then
  echo 'This script must be run from the root of the repository.' >&2
  exit 1
fi

# Default values for version overrides
PHP_VERSION_OVERRIDE=""
WP_VERSION_OVERRIDE=""
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

# shellcheck disable=SC2029
rsync -az --delete --exclude='.git' --exclude='.DS_Store' --exclude='local' \
    --exclude='protect,r .wordpress-org' --exclude='protect,r .distignore' \
    . ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/ && \
ssh ${REMOTE_USER}@${REMOTE_HOST} \
    "cd ~/${REMOTE_PATH} && \
     source ./.wpenvrc && \
     ${PHP_VERSION_OVERRIDE:+WP_ENV_PHP_VERSION=$PHP_VERSION_OVERRIDE} \
     ${WP_VERSION_OVERRIDE:+WP_ENV_CORE=$WP_VERSION_OVERRIDE} \
     wp-env $*"
