import axios from 'axios'

const agentClient = axios.create({ baseURL: 'http://localhost:5001', timeout: 15000 })

async function GetAccionNewsLiveUseCase(symbol) {
  const response = await agentClient.get('/news', { params: { symbol } })
  return response.data
}

export { GetAccionNewsLiveUseCase }
