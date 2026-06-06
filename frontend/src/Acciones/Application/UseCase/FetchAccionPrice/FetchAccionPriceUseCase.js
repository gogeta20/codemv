import axios from 'axios'

const agentClient = axios.create({ baseURL: 'http://localhost:5001', timeout: 15000 })

async function FetchAccionPriceUseCase(symbol, uuid) {
  const response = await agentClient.get('/price', { params: { symbol, uuid } })
  return response.data
}

export { FetchAccionPriceUseCase }
