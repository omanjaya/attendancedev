/**
 * XSS Vulnerability Audit Utility
 *
 * Comprehensive tool to scan Vue components and detect potential
 * XSS vulnerabilities, particularly v-html usage and unsafe content rendering.
 */

export interface XSSVulnerability {
  type:
    | 'v-html'
    | 'innerHTML'
    | 'outerHTML'
    | 'insertAdjacentHTML'
    | 'document.write'
    | 'eval'
    | 'Function'
    | 'unsanitized-interpolation'
  severity: 'critical' | 'high' | 'medium' | 'low'
  location: {
    file: string
    line?: number
    column?: number
    context?: string
  }
  description: string
  recommendation: string
  example?: string
}

export interface AuditResult {
  vulnerabilities: XSSVulnerability[]
  scannedFiles: number
  criticalCount: number
  highCount: number
  mediumCount: number
  lowCount: number
  summary: string
}

/**
 * XSS vulnerability patterns to detect
 */
const XSS_PATTERNS = {
  // Vue.js specific
  vHtml: {
    pattern: /v-html\s*=\s*["']([^"']*?)["']/gi,
    type: 'v-html' as const,
    severity: 'high' as const,
    description: 'v-html directive found - potential XSS vulnerability',
    recommendation: 'Sanitize content before using v-html or use text interpolation instead',
  },

  unsafeInterpolation: {
    pattern: /\{\{\s*([^}]*?(?:innerHTML|outerHTML|document\.write)[^}]*?)\s*\}\}/gi,
    type: 'unsanitized-interpolation' as const,
    severity: 'high' as const,
    description: 'Unsafe content interpolation detected',
    recommendation: 'Sanitize content before interpolating or use safe alternatives',
  },

  // JavaScript DOM manipulation
  innerHTML: {
    pattern: /\.innerHTML\s*=\s*([^;]+)/gi,
    type: 'innerHTML' as const,
    severity: 'high' as const,
    description: 'Direct innerHTML assignment - potential XSS vulnerability',
    recommendation: 'Use textContent instead or sanitize HTML content',
  },

  outerHTML: {
    pattern: /\.outerHTML\s*=\s*([^;]+)/gi,
    type: 'outerHTML' as const,
    severity: 'high' as const,
    description: 'Direct outerHTML assignment - potential XSS vulnerability',
    recommendation: 'Avoid outerHTML assignment or sanitize content',
  },

  insertAdjacentHTML: {
    pattern: /\.insertAdjacentHTML\s*\(\s*[^,]+,\s*([^)]+)\)/gi,
    type: 'insertAdjacentHTML' as const,
    severity: 'high' as const,
    description: 'insertAdjacentHTML usage - potential XSS vulnerability',
    recommendation: 'Use insertAdjacentText instead or sanitize HTML content',
  },

  documentWrite: {
    pattern: /document\.write\s*\(\s*([^)]+)\)/gi,
    type: 'document.write' as const,
    severity: 'critical' as const,
    description: 'document.write usage - critical XSS vulnerability',
    recommendation: 'Avoid document.write completely or sanitize content',
  },

  eval: {
    pattern: /\beval\s*\(\s*([^)]+)\)/gi,
    type: 'eval' as const,
    severity: 'critical' as const,
    description: 'eval() usage - critical security vulnerability',
    recommendation: 'Avoid eval() completely - use safe alternatives like JSON.parse()',
  },

  Function: {
    pattern: /\bFunction\s*\(\s*[^)]*\)\s*\(\s*([^)]*)\)/gi,
    type: 'Function' as const,
    severity: 'critical' as const,
    description: 'Function constructor usage - critical security vulnerability',
    recommendation: 'Avoid Function constructor - use safe alternatives',
  },
}

/**
 * Safe patterns that are acceptable
 */
const SAFE_PATTERNS = [
  // Sanitized v-html
  /v-html\s*=\s*["'].*?sanitize.*?["']/i,
  /v-html\s*=\s*["'].*?DOMPurify.*?["']/i,

  // Static content
  /v-html\s*=\s*["'][^{]*["']/i, // No interpolation

  // Trusted sources
  /innerHTML\s*=\s*["'][^'"{]*["']/i, // Static strings only
]

/**
 * Check if a pattern match is in a safe context
 */
function isSafeContext(content: string, matchIndex: number): boolean {
  // Check if it's in a comment
  const beforeMatch = content.substring(0, matchIndex)
  const lastCommentStart = Math.max(beforeMatch.lastIndexOf('<!--'), beforeMatch.lastIndexOf('//'))
  const lastCommentEnd = Math.max(beforeMatch.lastIndexOf('-->'), beforeMatch.lastIndexOf('\n'))

  if (lastCommentStart > lastCommentEnd) {
    return true // Inside comment
  }

  // Check if it's in a template literal or string
  const quotes = ['"', '\'', '`']
  for (const quote of quotes) {
    const lastQuoteStart = beforeMatch.lastIndexOf(quote)
    if (lastQuoteStart > -1) {
      const quotesCount =
        (beforeMatch.substring(lastQuoteStart) + content.substring(matchIndex)).split(quote)
          .length - 1
      if (quotesCount % 2 === 1) {
        return true // Inside string
      }
    }
  }

  return false
}

/**
 * Extract line and column information
 */
function getLocationInfo(content: string, index: number): { line: number; column: number } {
  const lines = content.substring(0, index).split('\n')
  return {
    line: lines.length,
    column: lines[lines.length - 1].length + 1,
  }
}

/**
 * Get context around the vulnerability
 */
function getContext(content: string, index: number, length: number): string {
  const start = Math.max(0, index - 50)
  const end = Math.min(content.length, index + length + 50)
  const context = content.substring(start, end)

  return context.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim()
}

/**
 * Scan content for XSS vulnerabilities
 */
export function scanContent(content: string, filename: string): XSSVulnerability[] {
  const vulnerabilities: XSSVulnerability[] = []

  for (const [patternName, patternInfo] of Object.entries(XSS_PATTERNS)) {
    const regex = new RegExp(patternInfo.pattern.source, patternInfo.pattern.flags)
    let match

    while ((match = regex.exec(content)) !== null) {
      const matchIndex = match.index

      // Skip if in safe context
      if (isSafeContext(content, matchIndex)) {
        continue
      }

      // Check against safe patterns
      const isSafe = SAFE_PATTERNS.some((safePattern) => {
        const fullMatch = match[0]
        return safePattern.test(fullMatch)
      })

      if (isSafe) {
        continue
      }

      const location = getLocationInfo(content, matchIndex)
      const context = getContext(content, matchIndex, match[0].length)

      vulnerabilities.push({
        type: patternInfo.type,
        severity: patternInfo.severity,
        location: {
          file: filename,
          line: location.line,
          column: location.column,
          context,
        },
        description: patternInfo.description,
        recommendation: patternInfo.recommendation,
        example: getExampleFix(patternInfo.type, match[0]),
      })
    }
  }

  return vulnerabilities
}

/**
 * Generate example fixes for vulnerabilities
 */
function getExampleFix(type: XSSVulnerability['type'], originalCode: string): string {
  switch (type) {
    case 'v-html':
      return `// Instead of: ${originalCode}
// Use: v-html="sanitizeHtml(content)" or {{ content }}`

    case 'innerHTML':
      return `// Instead of: ${originalCode}
// Use: element.textContent = content`

    case 'outerHTML':
      return `// Instead of: ${originalCode}
// Use: element.replaceWith(document.createTextNode(content))`

    case 'insertAdjacentHTML':
      return `// Instead of: ${originalCode}
// Use: element.insertAdjacentText('afterbegin', content)`

    case 'document.write':
      return `// Instead of: ${originalCode}
// Use: document.createElement() and appendChild()`

    case 'eval':
      return `// Instead of: ${originalCode}
// Use: JSON.parse() for JSON data or proper function calls`

    case 'Function':
      return `// Instead of: ${originalCode}
// Use: predefined functions or proper module imports`

    default:
      return 'Use proper sanitization or safe alternatives'
  }
}

/**
 * Scan a Vue component file
 */
export async function scanVueComponent(filepath: string): Promise<XSSVulnerability[]> {
  try {
    const response = await fetch(`/api/audit/file-content?path=${encodeURIComponent(filepath)}`)
    if (!response.ok) {
      throw new Error(`Failed to load file: ${filepath}`)
    }

    const content = await response.text()
    return scanContent(content, filepath)
  } catch (error) {
    console.error(`Error scanning file ${filepath}:`, error)
    return []
  }
}

/**
 * Scan multiple files for vulnerabilities
 */
export async function auditProject(filepaths: string[]): Promise<AuditResult> {
  const allVulnerabilities: XSSVulnerability[] = []
  let scannedFiles = 0

  for (const filepath of filepaths) {
    try {
      const vulnerabilities = await scanVueComponent(filepath)
      allVulnerabilities.push(...vulnerabilities)
      scannedFiles++
    } catch (error) {
      console.error(`Failed to scan ${filepath}:`, error)
    }
  }

  // Count by severity
  const criticalCount = allVulnerabilities.filter((v) => v.severity === 'critical').length
  const highCount = allVulnerabilities.filter((v) => v.severity === 'high').length
  const mediumCount = allVulnerabilities.filter((v) => v.severity === 'medium').length
  const lowCount = allVulnerabilities.filter((v) => v.severity === 'low').length

  // Generate summary
  const summary = generateSummary(allVulnerabilities, scannedFiles)

  return {
    vulnerabilities: allVulnerabilities,
    scannedFiles,
    criticalCount,
    highCount,
    mediumCount,
    lowCount,
    summary,
  }
}

/**
 * Generate audit summary
 */
function generateSummary(vulnerabilities: XSSVulnerability[], scannedFiles: number): string {
  if (vulnerabilities.length === 0) {
    return `✅ No XSS vulnerabilities detected in ${scannedFiles} files.`
  }

  const criticalCount = vulnerabilities.filter((v) => v.severity === 'critical').length
  const highCount = vulnerabilities.filter((v) => v.severity === 'high').length
  const mediumCount = vulnerabilities.filter((v) => v.severity === 'medium').length
  const lowCount = vulnerabilities.filter((v) => v.severity === 'low').length

  let summary = `🔍 XSS Audit Results: ${vulnerabilities.length} potential vulnerabilities found in ${scannedFiles} files.\n\n`

  if (criticalCount > 0) {
    summary += `🚨 Critical: ${criticalCount}\n`
  }
  if (highCount > 0) {
    summary += `⚠️ High: ${highCount}\n`
  }
  if (mediumCount > 0) {
    summary += `⚡ Medium: ${mediumCount}\n`
  }
  if (lowCount > 0) {
    summary += `ℹ️ Low: ${lowCount}\n`
  }

  summary += '\nRecommendations:\n'
  summary += '• Review and sanitize all v-html usage\n'
  summary += '• Replace innerHTML with textContent where possible\n'
  summary += '• Implement a content sanitization library like DOMPurify\n'
  summary += '• Use CSP headers to prevent XSS attacks\n'

  return summary
}

/**
 * HTML sanitization utility
 */
export class HTMLSanitizer {
  private static readonly ALLOWED_TAGS = [
    'p',
    'br',
    'strong',
    'em',
    'u',
    'span',
    'div',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'ul',
    'ol',
    'li',
    'a',
    'img',
    'blockquote',
    'code',
    'pre',
  ]

  private static readonly ALLOWED_ATTRIBUTES = ['href', 'src', 'alt', 'title', 'class', 'id']

  /**
   * Basic HTML sanitization (not production-ready - use DOMPurify in production)
   */
  static sanitize(html: string): string {
    if (typeof html !== 'string') {return ''}

    // Remove script tags and their content
    html = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')

    // Remove event handlers
    html = html.replace(/\s*on\w+\s*=\s*["'][^"']*["']/gi, '')

    // Remove javascript: protocols
    html = html.replace(/javascript:/gi, '')

    // Remove data: protocols (can contain javascript)
    html = html.replace(/data:/gi, '')

    // Remove style attributes (can contain expression())
    html = html.replace(/\s*style\s*=\s*["'][^"']*["']/gi, '')

    return html
  }

  /**
   * Strict text-only sanitization
   */
  static textOnly(input: string): string {
    if (typeof input !== 'string') {return ''}

    return input
      .replace(/<[^>]*>/g, '') // Remove all HTML tags
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/&amp;/g, '&')
      .replace(/&quot;/g, '"')
      .replace(/&#x27;/g, '\'')
  }
}

/**
 * Vue directive for safe HTML rendering
 */
export const vSafeHtml = {
  mounted(el: HTMLElement, binding: any) {
    const sanitizedHTML = HTMLSanitizer.sanitize(binding.value || '')
    el.innerHTML = sanitizedHTML
  },

  updated(el: HTMLElement, binding: any) {
    const sanitizedHTML = HTMLSanitizer.sanitize(binding.value || '')
    el.innerHTML = sanitizedHTML
  },
}

/**
 * Composable for safe content rendering
 */
export function useSafeContent() {
  const sanitizeHtml = (content: string) => HTMLSanitizer.sanitize(content)
  const textOnly = (content: string) => HTMLSanitizer.textOnly(content)

  return {
    sanitizeHtml,
    textOnly,
  }
}
