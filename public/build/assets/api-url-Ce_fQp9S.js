const a=document.querySelector('meta[name="api-base-url"]')?.content||"/api";function t(e){return e.startsWith("/api/")?`${a}${e.slice(4)}`:e}export{t as a};
