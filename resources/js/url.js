// The app may be served from a subfolder, so paths must include its base
const base = document.querySelector('meta[name="app-base"]')?.content ?? '';

export const url = (path) => base + path;
