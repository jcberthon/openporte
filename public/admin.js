(() => {
  document.addEventListener('DOMContentLoaded', () => {
    function onApiChange(api) {
      [...document.querySelectorAll('[data-custom-api]')].forEach((el) => {
        el.disabled = api !== 'custom';
      });
    }
    const apiEl = document.querySelector('#openporte_api');
    if (apiEl) {
      apiEl.addEventListener('change', (ev) => onApiChange(ev.target.value));
      onApiChange(apiEl.value);
    }

    // Show the free-form seconds input only when Expiration is set to Custom.
    function onExpiresChange(value) {
      const customEl = document.querySelector('#openporte_expires_custom');
      if (customEl) {
        customEl.style.display = value === 'custom' ? '' : 'none';
      }
    }
    const expiresEl = document.querySelector('#openporte_expires');
    if (expiresEl) {
      expiresEl.addEventListener('change', (ev) => onExpiresChange(ev.target.value));
      onExpiresChange(expiresEl.value);
    }

    // Show/Hide toggle for password-type settings fields. The labels come
    // from data attributes rendered server-side, so they stay translated.
    [...document.querySelectorAll('.openporte-toggle-password')].forEach((btn) => {
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        if (!input) {
          return;
        }
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        btn.textContent = reveal ? btn.dataset.labelHide : btn.dataset.labelShow;
      });
    });

    // Copy to clipboard functionality for password fields
    [...document.querySelectorAll('.openporte-copy-password')].forEach((btn) => {
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        if (!input) {
          return;
        }
        // Temporarily change type to text for copying
        const originalType = input.type;
        input.type = 'text';
        input.select();
        try {
          document.execCommand('copy');
          // Show a temporary success message
          const originalText = btn.textContent;
          btn.textContent = 'Copied!';
          setTimeout(() => {
            btn.textContent = originalText;
          }, 2000);
        } catch (e) {
          // Fallback for modern browsers
          navigator.clipboard.writeText(input.value).then(() => {
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(() => {
              btn.textContent = originalText;
            }, 2000);
          }).catch(() => {
            // Could not copy
            console.error('Failed to copy to clipboard');
          });
        }
        input.type = originalType;
      });
    });

    // Regenerate password functionality
    [...document.querySelectorAll('.openporte-regenerate-password')].forEach((btn) => {
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        const optionName = btn.dataset.optionName;
        if (!input || !optionName) {
          return;
        }
        
        // Ask for confirmation
        if (!confirm('Are you sure you want to regenerate the shared secret? This will invalidate all existing challenges.')) {
          return;
        }
        
        // Show loading state
        const originalText = btn.textContent;
        btn.textContent = 'Generating...';
        btn.disabled = true;
        
        // Create a new random secret
        const newSecret = generateRandomSecret();
        input.value = newSecret;
        
        // Update the option via AJAX
        const data = new FormData();
        data.append('action', 'openporte_regenerate_secret');
        data.append('option_name', optionName);
        data.append('new_secret', newSecret);
        data.append('nonce', openporteAdmin.nonce || '');
        
        fetch(ajaxurl, {
          method: 'POST',
          body: data
        }).then(response => response.json()).then(data => {
          if (data.success) {
            // Secret was updated successfully
            btn.textContent = 'Regenerated!';
            setTimeout(() => {
              btn.textContent = originalText;
              btn.disabled = false;
            }, 2000);
          } else {
            // Revert and show error
            btn.textContent = originalText;
            btn.disabled = false;
            alert('Failed to regenerate secret: ' + (data.data.message || 'Unknown error'));
          }
        }).catch(error => {
          btn.textContent = originalText;
          btn.disabled = false;
          console.error('Error:', error);
          alert('Failed to regenerate secret. Please try again.');
        });
      });
    });

    // Helper function to generate a random secret (32 bytes as hex)
    function generateRandomSecret() {
      const array = new Uint8Array(32);
      window.crypto.getRandomValues(array);
      return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
    }
  });
})();
