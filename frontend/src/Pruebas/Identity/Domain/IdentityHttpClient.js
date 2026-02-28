import axios from 'axios'

const STORAGE_KEY_TOKEN = 'identity_bearer_token'
const STORAGE_KEY_URL = 'identity_base_url'

const DEFAULT_URL = 'https://admin.local23.jotelulu.space'

function getToken() {
  return localStorage.getItem(STORAGE_KEY_TOKEN) || ''
}

function setToken(token) {
  localStorage.setItem(STORAGE_KEY_TOKEN, token)
}

function getBaseUrl() {
  return localStorage.getItem(STORAGE_KEY_URL) || DEFAULT_URL
}

function setBaseUrl(url) {
  localStorage.setItem(STORAGE_KEY_URL, url)
}

function createClient() {
  const client = axios.create({
    baseURL: getBaseUrl(),
    timeout: 30000,
    headers: { 'Content-Type': 'application/json' },
  })

  client.interceptors.request.use((config) => {
    const token = getToken()
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  })

  return client
}

export const IdentityHttpClient = {
  getToken,
  setToken,
  getBaseUrl,
  setBaseUrl,
  createClient,
}
