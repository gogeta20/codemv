import axios from 'axios'

const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8280'

const httpClient = axios.create({
  baseURL,
  timeout: 15000,
})

httpClient.interceptors.request.use(
  (config) => config,
  (error) => Promise.reject(error)
)

httpClient.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(error)
)

export default httpClient
