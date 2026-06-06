import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import mockData from './mock.json'

async function InMemory() {
  await UtilHelper.wait(1200)
  return mockData
}

async function Api(uuid, incomeFile, balanceFile, cashflowFile, price) {
  const form = new FormData()
  form.append('income', incomeFile)
  form.append('balance', balanceFile)
  form.append('cashflow', cashflowFile)
  if (price !== null && price !== undefined && price !== '') {
    form.append('price', price)
  }
  const response = await httpClient.post(`/api/acciones/${uuid}/analisis`, form)
  return response.data
}

async function UploadAnalisisUseCase(uuid, incomeFile, balanceFile, cashflowFile, price) {
  return UtilHelper.checkEnvironment()
    ? await InMemory()
    : await Api(uuid, incomeFile, balanceFile, cashflowFile, price)
}

export { UploadAnalisisUseCase }
