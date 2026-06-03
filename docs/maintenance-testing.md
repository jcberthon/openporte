# Maintenance and Testing Guide

## Testing

### Verify OpenPorte

The OpenPorte plugin can be tested remotely using the `wp-env.sh` helper script. See [Testing tools](#testing-tools) below for technical details about the script.

#### CLI Reference

The `wp-env.sh` script accepts the following options:

| Option | Short | Description | Environment Variable |
|--------|-------|-------------|---------------------|
| `--php-version` | `-p` | Override PHP version | `WP_ENV_PHP_VERSION` |
| `--wp-version` | `-w` | Override WordPress Core version | `WP_ENV_CORE` |

##### Supported Version Formats

**PHP Version**:
- Simple version: `7.3`, `7.4`, `8.0`, `8.1`, `8.2`
- Any value accepted by wp-env's `phpVersion` config

**WordPress Core Version**:
- Branch reference: `WordPress/WordPress#7.0`
- Tag reference: `WordPress/WordPress#6.5.3`
- Latest trunk: `WordPress/WordPress#trunk`
- Any value accepted by wp-env's `core` config

##### Usage Examples

```bash
# Start environment with default versions
./wp-env.sh start

# Override PHP version only
./wp-env.sh --php-version 7.3 start
./wp-env.sh -p 7.3 start

# Override WordPress version only
./wp-env.sh --wp-version WordPress/WordPress#6.5 start
./wp-env.sh -w WordPress/WordPress#6.5 start

# Override both versions
./wp-env.sh -p 7.3 -w WordPress/WordPress#7.0 start
./wp-env.sh --php-version 8.1 --wp-version WordPress/WordPress#trunk start

# Options can be mixed with wp-env arguments
./wp-env.sh start --php-version 7.4 --no-cache
./wp-env.sh -p 8.0 -w WordPress/WordPress#6.5 start --no-cache

# Stop the environment
./wp-env.sh stop
./wp-env.sh -p 7.3 stop
```

#### Configuration

Create a `.wp-env.conf` file in the repository root with your remote connection details:

```bash
REMOTE_USER='your-ssh-username'
REMOTE_HOST='your-ssh-host'
REMOTE_PATH='path/to/remote/directory'
```

This file is **sourced** by the script, so it must contain valid shell syntax.

#### Requirements

**Local Machine**:
- **Bash**: The script requires bash (uses `[[ ]]`, arrays, etc.)
- **OpenSSH**: Must be version ≥ 7.8 (released August 2018) for the `-o SetEnv` feature
- **rsync**: For code synchronization
- **Git**: For repository validation

Check your OpenSSH version:
```bash
ssh -V
# Should show: OpenSSH_7.8 or higher
```

#### Accessing the Remote Web Server

By default, wp-env on the remote host binds to `localhost:8888`. If you cannot access the remote web server directly at `http://REMOTE_HOST:8888/` due to network restrictions, firewalls, or security requirements (e.g., HTTPS-only contexts), you can create an SSH tunnel to forward the port locally.

##### SSH Tunnel Command

```bash
ssh -f -N -L 8888:localhost:8888 ${REMOTE_USER}@${REMOTE_HOST}
```

**Options explained**:
- `-f`: Fork into background after authentication
- `-N`: Do not execute a remote command (just forward ports)
- `-L 8888:localhost:8888`: Forward local port 8888 to remote's localhost:8888

##### Use Cases

**1. Network Firewall Blocks Port 8888**
If the remote host's firewall or network configuration blocks external access to port 8888, the SSH tunnel creates a secure connection that bypasses these restrictions.

**2. HTTP Blocked as Untrusted**
If your local browser or security policy blocks HTTP connections to the remote host, the tunnel makes the remote server appear as if it's running locally.

**3. Secure Context Required**
Some browser APIs (Service Workers, geolocation, etc.) require a secure context (HTTPS). While the tunnel itself doesn't provide HTTPS, you can combine it with a local HTTPS proxy if needed.

**4. Multiple Remote Environments**
You can run multiple tunnels on different local ports to access multiple remote environments simultaneously:
```bash
# First remote environment
ssh -f -N -L 8888:localhost:8888 user1@host1
# Second remote environment
ssh -f -N -L 8889:localhost:8888 user2@host2
```

##### Accessing via Tunnel

After creating the tunnel, access the remote WordPress instance at:
```
http://localhost:8888/
```

To stop the tunnel:
```bash
# Find the SSH process
ps aux | grep "ssh -f -N -L"
# Kill the process
kill <PID>
```

Or use a specific SSH config with a control socket for easier management.

#### Testing Matrix

For comprehensive plugin testing, consider this matrix:

| PHP Version | WordPress Version | Notes |
|-------------|-------------------|-------|
| 7.3 | 6.5 | Minimum supported |
| 7.4 | 7.0 | Current default |
| 8.0 | 7.0 | PHP 8.0 compatibility |
| 8.1 | trunk | Latest PHP + bleeding edge |
| 8.2 | 7.0 | Latest stable PHP |

Example test commands:
```bash
# Test PHP 7.3 with WP 6.5
./wp-env.sh -p 7.3 -w WordPress/WordPress#6.5 start

# Test PHP 8.2 with WP trunk
./wp-env.sh -p 8.2 -w WordPress/WordPress#trunk start
```

#### Troubleshooting

**"SetEnv not supported"**

**Error**: `ssh: invalid option -- 'o'` or similar

**Cause**: OpenSSH version is too old (< 7.8)

**Solution**: Upgrade OpenSSH on your local machine, or use the alternative `.wpenvrc` modification approach (see [Alternative Approach: Modifying ~/.wpenvrc](#alternative-approach-modifying-wpenvrc) below).

**"Environment variables not applied"**

**Error**: wp-env ignores the PHP/WP version overrides

**Check**:
1. Verify OpenSSH version: `ssh -V`
2. Test SetEnv directly: `ssh -o SetEnv=TEST_VAR=testvalue user@host 'echo $TEST_VAR'`
3. Ensure wp-env respects `WP_ENV_PHP_VERSION` and `WP_ENV_CORE`

**"Permission denied"**

**Error**: `Permission denied (publickey)`

**Solution**: Set up SSH key-based authentication:
```bash
ssh-copy-id ${REMOTE_USER}@${REMOTE_HOST}
```

**"rsync errors"**

**Error**: Various rsync permission or path issues

**Solution**: Ensure the remote directory exists and is writable:
```bash
ssh ${REMOTE_USER}@${REMOTE_HOST} "mkdir -p ~/path/to/remote/directory"
```

---

### Testing tools

#### wp-env.sh

The `wp-env.sh` script is a wrapper around [`@wordpress/env`](https://github.com/WordPress/wordpress-develop/tree/trunk/tools/wp-env) (wp-env) that facilitates remote testing of the OpenPorte plugin across different PHP and WordPress versions.

##### Why This Exists

The official wp-env tool runs Docker containers locally. However, for this project we:
1. Need to test on a remote server with specific configurations
2. Want to quickly switch between PHP versions (7.3, 7.4, 8.0, 8.1, 8.2, etc.)
3. Want to test against different WordPress Core versions
4. Need to synchronize the local plugin code to the remote test environment

This script automates the rsync-and-ssh workflow while providing a clean CLI interface for version overrides.

##### Architecture

```
Local Machine              Remote Host
     │                         │
     │  1. Parse CLI args      │
     │  2. Build SetEnv opts    │
     │  3. rsync code           │
     │──────────────────────────▶│
     │                         │
     │  4. SSH with SetEnv      │
     │──────────────────────────▶│
     │                         │
     │  5. (Optional) SSH tunnel │
     │◀──────────────────────────│  <- Local:8888 → Remote:8888
     │                         │
     └─────────────────────────┘
           ↓
     Remote runs: source ~/.wpenvrc
                 cd ~/REMOTE_PATH
                 wp-env [args]
```

##### Environment Variable Injection

The script uses OpenSSH's `-o SetEnv=KEY=VALUE` option to inject environment variables into the remote session. This approach was chosen over modifying `~/.wpenvrc` on the remote host because:

| Criteria | `ssh -o SetEnv` | Modify `~/.wpenvrc` |
|---------|----------------|---------------------|
| Remote filesystem changes | None | Requires backup/restore |
| Cleanup required | Automatic (session-scoped) | Manual |
| Code complexity | ~25 lines | ~50+ lines |
| Readability | High (explicit in command) | Lower (indirect) |
| Error potential | Low | High (partial cleanup) |
| SSH version requirement | ≥ 7.8 (2018) | Any |

**Decision**: `ssh -o SetEnv` is superior for this use case.

##### Implementation Details

**Option Parsing**

The script processes all arguments to extract version overrides before passing the remainder to wp-env:

```bash
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
```

**Key design decisions**:
1. Version options can appear **anywhere** in the command line (not just at the beginning)
2. If an option appears multiple times, the **last occurrence wins** (standard POSIX behavior)
3. Options are **consumed** and not passed to wp-env

**SetEnv Option Construction**

```bash
if [[ -n "$PHP_VERSION_OVERRIDE" ]]; then
  SSH_SETENV_OPTS+=("-o" "SetEnv=WP_ENV_PHP_VERSION=$PHP_VERSION_OVERRIDE")
fi
if [[ -n "$WP_VERSION_OVERRIDE" ]]; then
  SSH_SETENV_OPTS+=("-o" "SetEnv=WP_ENV_CORE=$WP_VERSION_OVERRIDE")
fi
```

The options are stored as an array and expanded with `"${SSH_SETENV_OPTS[@]}"` to properly handle spaces in values.

**Remote Command Execution**

```bash
ssh "${SSH_SETENV_OPTS[@]}" ${REMOTE_USER}@${REMOTE_HOST} \
  "source ~/.wpenvrc && cd ~/${REMOTE_PATH} && wp-env $*"
```

Note: The remote command:
1. Sources `~/.wpenvrc` (which may contain default environment variables)
2. Changes to the project directory
3. Runs wp-env with all remaining arguments

The `SetEnv` variables from SSH take precedence over variables defined in `~/.wpenvrc`.

##### Configuration (Remote Host)

The remote host must have `~/.wpenvrc` which is automatically sourced before running wp-env. It can contain default environment variables:

```bash
# Example ~/.wpenvrc on remote
export WP_ENV_PHP_VERSION="8.1"
export WP_ENV_CORE="WordPress/WordPress#7.0"
export WP_ENV_PORT="8888"
```

Variables set via `--php-version` or `--wp-version` will **override** these defaults.

Additionally, the remote host requires:
- **wp-env**: Must be installed and available in PATH
- **Docker**: wp-env requires Docker to be running
- **SSH access**: The local machine must have password-less SSH access
- **Bash**: Remote shell must be bash (for `~/.wpenvrc` sourcing)

##### Alternative Approach: Modifying ~/.wpenvrc

If `ssh -o SetEnv` is not available (OpenSSH < 7.8), an alternative implementation would:

1. Read current `~/.wpenvrc` from remote
2. Add/override the specified variables
3. Write modified version to remote
4. Run wp-env
5. Restore original `~/.wpenvrc` (using trap for cleanup)

This approach was **not implemented** because it requires:
- Additional SSH commands (slower)
- Complex error handling with traps
- Risk of leaving remote in modified state if script crashes
- More code (~50+ lines vs ~25 lines)

If you need to support older OpenSSH versions, this can be added as a fallback.

##### Future Enhancements

Potential improvements for the script:

1. **Fallback for old OpenSSH**: Detect OpenSSH version and use `.wpenvrc` modification if < 7.8
2. **More options**: Add `--mysql-version`, `--port`, etc.
3. **Dry-run mode**: Add `--dry-run` to show what would be executed without running
4. **Verbose mode**: Add `-v` for detailed output
5. **Configuration validation**: Validate PHP/WP version formats before execution
6. **Parallel testing**: Support running multiple test environments simultaneously

##### See Also

- [wp-env Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
- [OpenSSH SetEnv Option](https://man.openbsd.org/ssh_config.5#SetEnv)
- [OpenPorte Plugin](../readme.txt)
