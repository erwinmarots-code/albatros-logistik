import axios from '../axios'
import { ref } from 'vue'

export function useExport(moduleName) {
    const isExporting = ref(false)
    const exportError = ref(null)

    const exportData = async (filters = {}) => {
        isExporting.value = true
        exportError.value = null

        try {
            // Bangun query string dari filters
            const params = new URLSearchParams()
            Object.keys(filters).forEach(key => {
                if (filters[key] !== null && filters[key] !== undefined && filters[key] !== '') {
                    params.append(key, filters[key])
                }
            })

            // 🔥 PERBAIKAN: HAPUS prefix "/api" karena sudah ada di baseURL
            const url = `/${moduleName}/export?${params.toString()}`

            const response = await axios.get(url, {
                responseType: 'blob',
                headers: {
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                }
            })

            // Buat link download
            const blob = new Blob([response.data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            })
            const link = document.createElement('a')
            link.href = window.URL.createObjectURL(blob)
            link.download = `${moduleName}_${new Date().toISOString().slice(0, 10)}.xlsx`
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(link.href)

        } catch (error) {
            console.error('Export error:', error)

            if (error.response && error.response.status === 403) {
                exportError.value = 'Anda tidak memiliki izin untuk ekspor.'
            } else if (error.response && error.response.data?.message) {
                exportError.value = error.response.data.message
            } else {
                exportError.value = 'Terjadi kesalahan saat mengekspor data.'
            }
            alert(exportError.value)
        } finally {
            isExporting.value = false
        }
    }

    return {
        isExporting,
        exportError,
        exportData,
    }
}