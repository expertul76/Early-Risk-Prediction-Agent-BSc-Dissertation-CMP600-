// CSRF token for AJAX requests
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    || document.querySelector('input[name="_csrf"]')?.value || '';

// Fetch wrapper with CSRF
async function api(url, options = {}) {
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
    };

    const response = await fetch(url, { ...defaults, ...options });
    return response.json();
}

// Flash message auto-dismiss
document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transition = 'opacity 0.3s';
        setTimeout(() => el.remove(), 300);
    }, 5000);
});
