// Light / dark theme toggle. Persists choice in localStorage.

const STORAGE_KEY = "noovapos-theme";

export const getTheme = () => {
    if (typeof window === "undefined") return "light";
    return localStorage.getItem(STORAGE_KEY) || "light";
};

export const applyTheme = (theme) => {
    if (typeof document === "undefined") return;
    if (theme === "dark") {
        document.documentElement.setAttribute("data-theme", "dark");
    } else {
        document.documentElement.removeAttribute("data-theme");
    }
};

export const setTheme = (theme) => {
    if (typeof window === "undefined") return;
    localStorage.setItem(STORAGE_KEY, theme);
    applyTheme(theme);
};

export const toggleTheme = () => {
    const next = getTheme() === "dark" ? "light" : "dark";
    setTheme(next);
    return next;
};

// Apply on first load
if (typeof window !== "undefined") {
    applyTheme(getTheme());
}
