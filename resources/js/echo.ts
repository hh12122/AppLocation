import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Make Pusher available globally for Laravel Echo
(window as any).Pusher = Pusher

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

// Create Echo instance with proper configuration
const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: false, // Force false for local dev to avoid WSS errors
    enabledTransports: ['ws'],
    disableStats: true,

    // Use default authEndpoint (Laravel Echo will handle it properly)
    authEndpoint: '/broadcasting/auth',

    authorizer: (channel: any) => {
        return {
            authorize: (socketId: string, callback: (error: any, data?: any) => void) => {
                fetch('/broadcasting/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        socket_id: socketId,
                        channel_name: channel.name
                    }),
                    credentials: 'include'
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw { response: { status: response.status, data } }
                        }).catch(() => {
                            throw { response: { status: response.status } }
                        })
                    }
                    return response.json()
                })
                .then(data => {
                    callback(null, data)
                })
                .catch(error => {
                    console.error('Broadcasting auth failed:', {
                        status: error.response?.status,
                        data: error.response?.data,
                        channel: channel.name
                    })
                    callback(error)
                })
            }
        }
    }
})

// Add connection status logging for debugging
const pusher = echo.connector.pusher

pusher.connection.bind('connected', () => {
    console.log('✅ Pusher connected successfully')
})

pusher.connection.bind('error', (err: any) => {
    console.error('❌ Pusher connection error:', err)
})

pusher.connection.bind('disconnected', () => {
    console.warn('⚠️ Pusher disconnected')
})

export default echo
