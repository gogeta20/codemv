import axios from 'axios'

const agentClient = axios.create({ baseURL: 'http://localhost:5001', timeout: 15000 })

async function GetAccionDescriptionUseCase(symbol) {
  const response = await agentClient.get('/description', { params: { symbol } })
  return response.data
}

export { GetAccionDescriptionUseCase }
