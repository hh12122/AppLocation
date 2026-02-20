import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import axios from 'axios'

// Make Pusher available globally for Laravel Echo
(window as any).Pusher = Pusher

// Configure axios for CSRF protection (required for broadcasting auth)
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
} else {
    console.error('CSRF token not found in meta tags!')
}

// Create Echo instance with proper configuration
const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    encrypted: true,

    // Use default authEndpoint (Laravel Echo will handle it properly)
    authEndpoint: '/broadcasting/auth',

    // Use axios as the authorizer (it properly handles CSRF and cookies)
    authorizer: (channel: any) => {
        return {
            authorize: (socketId: string, callback: (error: any, data?: any) => void) => {
                axios.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                })
                    .then(response => {
                    callback(null, response.data)
                })
                    .catch(error => {
                        console.error('Broadcasting auth failed:', {
                            status: error.response?.status,
                            statusText: error.response?.statusText,
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
