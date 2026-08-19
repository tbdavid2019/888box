/**
 * 888box WebMCP Bridge
 * 
 * Implements the browser-level Model Context Protocol (WebMCP) standard (`document.modelContext`),
 * enabling browser AI agents (Chrome 146+, Cloudflare BrowserRun, sidecar agents) to interact with
 * 888box directly using structured tools.
 */
(async function initWebMCP() {
  // Check if browser environment supports WebMCP document.modelContext
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return;
  }

  if (!('modelContext' in document) || typeof document.modelContext?.registerTool !== 'function') {
    // Browser does not support WebMCP yet; silently exit
    return;
  }

  // Find script element to get custom endpoint if configured
  const currentScript = document.currentScript || document.querySelector('script[data-mcp-url]');
  const mcpUrl = currentScript?.getAttribute('data-mcp-url') || '/mcp.php';

  try {
    // Discover available tools from backend MCP endpoint
    const listRes = await fetch(mcpUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'WebMCP'
      },
      body: JSON.stringify({
        jsonrpc: '2.0',
        id: 'webmcp-init-' + Date.now(),
        method: 'tools/list',
        params: {}
      })
    });

    if (!listRes.ok) {
      console.warn('[WebMCP] Failed to fetch tools from endpoint:', mcpUrl, listRes.status);
      return;
    }

    const data = await listRes.json();
    const tools = data?.result?.tools || [];

    if (!Array.isArray(tools) || tools.length === 0) {
      return;
    }

    let registeredCount = 0;
    for (const tool of tools) {
      if (!tool?.name) continue;

      document.modelContext.registerTool({
        name: tool.name,
        description: tool.description || '',
        inputSchema: tool.inputSchema || { type: 'object', properties: {} },
        execute: async (args) => {
          const callRes = await fetch(mcpUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'WebMCP'
            },
            body: JSON.stringify({
              jsonrpc: '2.0',
              id: 'webmcp-call-' + Date.now(),
              method: 'tools/call',
              params: {
                name: tool.name,
                arguments: args || {}
              }
            })
          });

          if (!callRes.ok) {
            return {
              content: [{ type: 'text', text: `HTTP Error: ${callRes.status} ${callRes.statusText}` }],
              isError: true
            };
          }

          const callData = await callRes.json();
          if (callData.error) {
            return {
              content: [{ type: 'text', text: `RPC Error ${callData.error.code}: ${callData.error.message}` }],
              isError: true
            };
          }

          return callData.result || { content: [{ type: 'text', text: JSON.stringify(callData) }] };
        }
      });
      registeredCount++;
    }

    console.info(`[888box] Registered ${registeredCount} WebMCP tools into document.modelContext`);
  } catch (err) {
    console.debug('[WebMCP] Initialization skipped or error:', err);
  }
})();
