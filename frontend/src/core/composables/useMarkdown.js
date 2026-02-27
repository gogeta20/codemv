import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'

const md = new MarkdownIt({
  html: false,
  linkify: true,
  typographer: true,
  highlight(code, lang) {
    if (lang && hljs.getLanguage(lang)) {
      return `<pre class="hljs-block"><code class="hljs language-${lang}">${
        hljs.highlight(code, { language: lang, ignoreIllegals: true }).value
      }</code></pre>`
    }
    return `<pre class="hljs-block"><code class="hljs">${
      hljs.highlightAuto(code).value
    }</code></pre>`
  }
})

export function useMarkdown() {
  function render(source) {
    return source ? md.render(source) : ''
  }
  return { render }
}
