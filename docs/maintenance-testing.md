# Maintenance and Testing Guide

## Maintenance

### Server Requirements

OpenPorte (ALTCHA) maintains compatibility with WordPress 5.0 through 7.0 and PHP 7.3 through 8.5.

#### Support Status

**⚠️ Deprecated (Supported for v1.27.0 only)**
- WordPress: 5.0+
- PHP: 7.3
- *Will be removed in versions after 1.27.**

**✅ Recommended**
- WordPress: 6.8+
- PHP: 8.3-8.5

**✅ Latest Tested**
- WordPress: 7.0
- PHP: 8.3

#### Compatibility Notes

- WordPress 5.0 is the oldest version compatible with PHP 7.3
- WordPress 6.8 is the oldest version with full PHP 8.3 compatibility
- WordPress 7.0+ no longer supports PHP 7.2 and 7.3
- PHP 8.2 has only 6 months of security support from upstream and is not recommended
- PHP 7.3 support is deprecated and will be removed in future releases


## Testing

### Verify OpenPorte

The OpenPorte plugin can be tested remotely using the `wp-env.sh` helper script. See [Testing tools](#testing-tools) below for technical details about the script.

**Note**: The script no longer requires OpenSSH >= 7.8. The previous implementation used `ssh -o SetEnv` which required specific sshd configuration on the remote host. The current implementation exports variables inline in the remote shell command, which works on any SSH version.

#### CLI Reference

The `wp-env.sh` script accepts the following options:

| Option | Short | Description | Environment Variable |
|--------|-------|-------------|---------------------|
| `--php-version` | `-p` | Override PHP version | `WP_ENV_PHP_VERSION` |
| `--wp-version` | `-w` | Override WordPress Core version | `WP_ENV_CORE` |
| `--verbose` | `-v` | Display detailed environment information after start | N/A |

##### Supported Version Formats

**PHP Version**:
- Simple version: `7.3`, `7.4`, `8.0`, `8.1`, `8.2`
- Any value accepted by wp-env's `phpVersion` config

**WordPress Core Version**:
- Simple version: `6.5`, `7.0` (auto-expanded to `WordPress/WordPress#6.5`)
- Branch reference: `WordPress/WordPress#7.0`
- Tag reference: `WordPress/WordPress#6.5.3`
- Latest trunk: `WordPress/WordPress#trunk`
- Any value accepted by wp-env's `core` config

##### Usage Examples

```bash
# Start environment with default versions
./wp-env.sh start

# Start with verbose output (shows environment details)
./wp-env.sh -v start
./wp-env.sh --verbose start

# Override PHP version only
./wp-env.sh --php-version 7.3 start
./wp-env.sh -p 7.3 start

# Override WordPress version only (both formats supported)
./wp-env.sh -w 6.5 start
./wp-env.sh --wp-version WordPress/WordPress#6.5 start

# Override both versions
./wp-env.sh -p 7.3 -w 7.0 start
./wp-env.sh --php-version 8.1 --wp-version WordPress/WordPress#trunk start

# Verbose start with overrides
./wp-env.sh -v -p 7.3 -w 7.0 start

# Options can be mixed with wp-env arguments
./wp-env.sh start --php-version 7.4 --no-cache
./wp-env.sh -p 8.0 -w 6.5 start --no-cache

# Stop the environment
./wp-env.sh stop
./wp-env.sh -p 7.3 stop
```

##### Verbose Output Example

When using `-v` or `--verbose` with `start`, the script displays:

```
✅ WordPress environment started successfully!

Environment Details:
  Admin URL:        http://localhost:8888/wp-admin
  WordPress:        6.9
  PHP:              8.4.21
  Database:         MariaDB 11.4.10-MariaDB
  Runtime:          docker
  Multisite:        no
  Xdebug:           off
  Install Path:     /home/user/.wp-env/wp-env-openporte-xxx

Service Status:    ✅ Running
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
- **OpenSSH**: Any version (the script uses inline export, not `-o SetEnv`)
- **rsync**: For code synchronization
- **Git**: For repository validation

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

#### Older WordPress versions

When provisioning the minimum-supported stack (e.g. **PHP 8.0 / WP 5.6**), two
steps in [`tests/bin/wp-init.sh`](../tests/bin/wp-init.sh) have minimum-WordPress
requirements and will abort the `afterStart` hook unless adjusted:

- **Plugin Check** requires **WordPress 6.3+**. Comment out its install line
  (`wp plugin install plugin-check`) on older benches. Static analysis is not
  available on these versions anyway — run Plugin Check on a recent-WordPress
  bench (e.g. WP 7.0) and use the older bench for runtime verification only.
- **Contact Form 7** installs the latest release by default, which fails the
  WordPress-version requirement on older cores. Pin a compatible version with
  `--version=`, e.g. `--version=5.3.2` for WP 5.6. (`wp-cli`'s
  `--ignore-requirements` flag is too recent to rely on in the bundled wp-env
  CLI, so an explicit `--version` is the dependable approach.)

On a minimum-version bench, run the **fresh-install verification only** — skip
the ALTCHA → OpenPorte upgrade scenario.

#### Troubleshooting

**"Environment variables not applied"**

**Error**: wp-env ignores the PHP/WP version overrides

**Check**:
1. Verify the remote command includes the export statements by adding `-v` to SSH: `ssh -v ${REMOTE_USER}@${REMOTE_HOST} "export WP_ENV_PHP_VERSION=7.3 && echo test"`
2. Ensure wp-env respects `WP_ENV_PHP_VERSION` and `WP_ENV_CORE` environment variables
3. Test directly on the remote: `ssh ${REMOTE_USER}@${REMOTE_HOST} "export WP_ENV_PHP_VERSION=7.3 && echo \$WP_ENV_PHP_VERSION"`

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
     │  2. rsync code          │
     │──────────────────────────▶│
     │                         │
     │  3. SSH with inline      │
     │    variable export      │
     │──────────────────────────▶│
     │                         │
     │  4. (Optional) SSH tunnel │
     │◀──────────────────────────│  <- Local:8888 → Remote:8888
     │                         │
     └─────────────────────────┘
           ↓
     Remote runs: export WP_ENV_*=
                 source ~/.wpenvrc
                 cd ~/REMOTE_PATH
                 wp-env [args]
```

##### Environment Variable Injection

The script exports environment variables inline in the remote shell command using the `${var:+value}` bash parameter expansion pattern. This approach was chosen because the previous `ssh -o SetEnv` method required specific `AcceptEnv` configuration in the remote sshd server, which cannot be assumed across different hosting environments.

**Example**: If `--php-version 7.3` is specified, the remote command becomes:
```bash
ssh user@host "export WP_ENV_PHP_VERSION=7.3 && source ~/.wpenvrc && cd ~/path && wp-env start"
```

If no overrides are specified, the export statement is omitted entirely.

**Advantages**:
- Works on any SSH version (no OpenSSH ≥ 7.8 requirement)
- No dependency on remote sshd configuration
- Automatic cleanup (variables are session-scoped)
- Simple and direct implementation

##### Implementation Details

**Option Parsing**

The script processes all arguments to extract version overrides and flags before passing the remainder to wp-env:

```bash
VERBOSE_MODE=false

while [[ $# -gt 0 ]]; do
  case "$1" in
    --php-version|-p)
      PHP_VERSION_OVERRIDE="$2"
      shift 2
      ;;
    --wp-version|-w)
      # Auto-prepend WordPress/WordPress# if not already present
      if [[ "$2" != *"#"* ]]; then
        WP_VERSION_OVERRIDE="WordPress/WordPress#$2"
      else
        WP_VERSION_OVERRIDE="$2"
      fi
      shift 2
      ;;
    --verbose|-v)
      VERBOSE_MODE=true
      shift
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
4. WordPress version accepts both short format (`6.5`) and full format (`WordPress/WordPress#6.5`)
5. Verbose mode is a flag (no argument) that enables detailed output after start

**Remote Command Construction**

The environment variables are set inline in the remote shell command using the `${var:+value}` bash parameter expansion pattern, which expands to `value` only if `var` is non-empty:

```bash
cd ~/${REMOTE_PATH} && \
 source ./.wpenvrc && \
 ${PHP_VERSION_OVERRIDE:+WP_ENV_PHP_VERSION=$PHP_VERSION_OVERRIDE} \
 ${WP_VERSION_OVERRIDE:+WP_ENV_CORE=$WP_VERSION_OVERRIDE} \
 wp-env $*
```

This approach:
- Omits the variable assignments if no overrides are specified
- Sets only the variables that have been set
- Works on any SSH version without requiring sshd configuration
- Variables are set after sourcing `.wpenvrc` but before running `wp-env`, allowing overrides

**Remote Command Execution**

```bash
ssh ${REMOTE_USER}@${REMOTE_HOST} \
  "export ${PHP_VERSION_OVERRIDE:+WP_ENV_PHP_VERSION=$PHP_VERSION_OVERRIDE} \
          ${WP_VERSION_OVERRIDE:+WP_ENV_CORE=$WP_VERSION_OVERRIDE} \
   && source ~/.wpenvrc && cd ~/${REMOTE_PATH} && wp-env $*"
```

Note: The remote command:
1. Exports the override variables (if any)
2. Sources `~/.wpenvrc` (which may contain default environment variables)
3. Changes to the project directory
4. Runs wp-env with all remaining arguments

The inline export variables take precedence over variables defined in `~/.wpenvrc`.

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

##### Implementation Notes

The current implementation using inline `export` with `${var:+value}` parameter expansion was chosen because it:
- Works on any SSH version (no OpenSSH ≥ 7.8 requirement)
- Does not require any special sshd configuration on the remote host
- Is simpler and more direct than the previous `ssh -o SetEnv` approach
- Automatically handles the case where no overrides are specified

The `${var:+value}` syntax expands to `value` only if `var` is non-empty, allowing conditional inclusion of export statements without complex control flow.

If you need to support older OpenSSH versions, this can be added as a fallback.

##### Future Enhancements

Potential improvements for the script:

1. **More options**: Add `--mysql-version`, `--port`, etc.
2. **Dry-run mode**: Add `--dry-run` to show what would be executed without running
3. **Verbose mode**: Add `-v` for detailed output
4. **Configuration validation**: Validate PHP/WP version formats before execution
5. **Parallel testing**: Support running multiple test environments simultaneously

##### See Also

- [wp-env Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
- [Bash Parameter Expansion Guide](https://www.gnu.org/software/bash/manual/html_node/Shell-Parameter-Expansion.html)
- [OpenPorte Plugin](../readme.txt)
