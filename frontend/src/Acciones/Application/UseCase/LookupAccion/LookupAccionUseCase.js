import axios from 'axios'

const lookupClient = axios.create({ baseURL: 'http://localhost:5001', timeout: 8000 })

async function LookupAccionUseCase(query) {
  const response = await lookupClient.get('/lookup', { params: { q: query } })
  return response.data.results ?? []
}

export { LookupAccionUseCase }
