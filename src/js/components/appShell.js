/**
 * Root shell component. Manages sidebar open/close state.
 * Sidebar defaults to open on viewports ≥ 768 px (md breakpoint).
 *
 * @returns {{ sidebarOpen: boolean }}
 */
export const appShell = () => ({
    sidebarOpen: window.innerWidth >= 768
});
