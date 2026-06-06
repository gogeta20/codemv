import axios from 'axios'

const agentClient = axios.create({ baseURL: 'http://localhost:5001', timeout: 15000 })

async function GetAccionHistoryUseCase(symbol) {
  const response = await agentClient.get('/history', { params: { symbol } })
  return response.data
}

export { GetAccionHistoryUseCase }
