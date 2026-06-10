// Build the API base URL from the current window location.
// We use window.location.port so it works across all environments:
//   - Laragon on port 80  → no port suffix  (http://noovapos.local/api/)
//   - artisan serve       → :8000            (http://localhost:8000/api/)
//   - Vite dev server     → :5173            (http://localhost:5173/api/)  [proxied]
//   - Production (80/443) → no port suffix
const { protocol, hostname, port } = window.location;
const portSuffix = port && port !== '80' && port !== '443' ? `:${port}` : '';

export const environment = {
    URL: `${protocol}//${hostname}${portSuffix}`,
};
