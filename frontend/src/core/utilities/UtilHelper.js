export class UtilHelper {
  static checkEnvironment(mode = 'development') {
    const envMode = import.meta.env.MODE
    const useMocks = import.meta.env.VITE_USE_MOCKS === 'true'
    return useMocks && envMode === mode
  }

  static wait(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms))
  }

  static generateUUID() {
    // Always use UUID v4 format to ensure RFC 4122 compliance
    // Required format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
    // where 4 = version 4, y = variant (8, 9, a, or b)
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
      const random = (Math.random() * 16) | 0
      const value = char === 'x' ? random : (random & 0x3) | 0x8
      return value.toString(16)
    })
  }
}
