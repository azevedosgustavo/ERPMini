class ApiClient {
    constructor(basePath) {
        this.basePath = basePath || '';
        this.accessTokenStorageKey = 'mini_erp_oauth_access_token';
        this.refreshTokenStorageKey = 'mini_erp_oauth_refresh_token';
        this.accessToken = localStorage.getItem(this.accessTokenStorageKey) || '';
        this.refreshToken = localStorage.getItem(this.refreshTokenStorageKey) || '';
        this.refreshingPromise = null;
    }

    hasToken() {
        return !!this.accessToken;
    }

    setTokens(tokenPayload) {
        this.accessToken = tokenPayload && tokenPayload.access_token ? String(tokenPayload.access_token) : '';
        this.refreshToken = tokenPayload && tokenPayload.refresh_token ? String(tokenPayload.refresh_token) : '';

        if (this.accessToken) {
            localStorage.setItem(this.accessTokenStorageKey, this.accessToken);
        } else {
            localStorage.removeItem(this.accessTokenStorageKey);
        }

        if (this.refreshToken) {
            localStorage.setItem(this.refreshTokenStorageKey, this.refreshToken);
        } else {
            localStorage.removeItem(this.refreshTokenStorageKey);
        }
    }

    clearTokens() {
        this.accessToken = '';
        this.refreshToken = '';
        localStorage.removeItem(this.accessTokenStorageKey);
        localStorage.removeItem(this.refreshTokenStorageKey);
    }

    async refreshAccessToken() {
        if (!this.refreshToken) {
            return false;
        }

        if (this.refreshingPromise) {
            return this.refreshingPromise;
        }

        this.refreshingPromise = (async () => {
            const response = await fetch(`${this.basePath}/api/oauth/token`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    grant_type: 'refresh_token',
                    refresh_token: this.refreshToken
                })
            });

            const contentType = response.headers.get('content-type') || '';
            const payload = contentType.includes('application/json') ? await response.json() : null;

            if (!response.ok || !payload || payload.success === false || !payload.data || !payload.data.access_token) {
                this.clearTokens();
                return false;
            }

            this.setTokens(payload.data);
            return true;
        })();

        try {
            return await this.refreshingPromise;
        } finally {
            this.refreshingPromise = null;
        }
    }

    async request(path, options = {}, hasRetried = false) {
        const headers = {
            'Content-Type': 'application/json',
            ...(options.headers || {})
        };

        if (this.accessToken) {
            headers.Authorization = `Bearer ${this.accessToken}`;
        }

        const response = await fetch(`${this.basePath}${path}`, {
            headers: {
                ...headers
            },
            ...options
        });

        if (response.status === 401 && !hasRetried && path !== '/api/oauth/token' && this.refreshToken) {
            const refreshed = await this.refreshAccessToken();

            if (refreshed) {
                return this.request(path, options, true);
            }
        }

        const contentType = response.headers.get('content-type') || '';
        const payload = contentType.includes('application/json') ? await response.json() : null;

        if (!response.ok || (payload && payload.success === false)) {
            throw new Error(payload && payload.message ? payload.message : 'Request failed.');
        }

        return payload ? payload.data : null;
    }

    get(path) {
        return this.request(path, { method: 'GET' });
    }

    post(path, body) {
        return this.request(path, { method: 'POST', body: JSON.stringify(body) });
    }

    put(path, body) {
        return this.request(path, { method: 'PUT', body: JSON.stringify(body) });
    }

    delete(path) {
        return this.request(path, { method: 'DELETE' });
    }
}

window.apiClient = new ApiClient(window.APP_BASE_PATH || '');