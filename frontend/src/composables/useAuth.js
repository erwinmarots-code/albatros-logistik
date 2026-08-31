import axios from '../axios'
import { ref } from 'vue'

export function useAuth() {
    const user = ref(null)
    const isLoading = ref(false)
    const error = ref(null)

    const getUser = async () => {
        try {
            const response = await axios.get('/user')
            user.value = response.data.data
            return user.value
        } catch (e) {
            console.error('Failed to get user:', e)
            return null
        }
    }

    const updatePassword = async (currentPassword, newPassword, newPasswordConfirmation) => {
        isLoading.value = true
        error.value = null
        try {
            const response = await axios.post('/update-password', {
                current_password: currentPassword,
                new_password: newPassword,
                new_password_confirmation: newPasswordConfirmation,
            })
            return response.data
        } catch (e) {
            if (e.response && e.response.data.errors) {
                error.value = e.response.data.errors
            } else if (e.response && e.response.data.message) {
                error.value = { general: [e.response.data.message] }
            } else {
                error.value = { general: ['Terjadi kesalahan saat mengganti password'] }
            }
            throw e
        } finally {
            isLoading.value = false
        }
    }

    return {
        user,
        isLoading,
        error,
        getUser,
        updatePassword,
    }
}