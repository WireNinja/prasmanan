import fs from "fs";
import path from "path";
import { loadEnv } from "vite";

/**
 * Easy environment loader for Vite config
 */
export function getEnv(mode) {
    return loadEnv(mode, process.cwd(), "");
}

/**
 * Standard CSS inputs for Prasmanan projects
 */
export const standardInputs = [
    "resources/css/app.css",
    "resources/js/app.js",
    "resources/css/pdf.css",
];

/**
 * Auto-discover Filament themes in resources/css/filament
 */
export function getFilamentThemes() {
    const baseDir = path.resolve("resources/css/filament");
    if (!fs.existsSync(baseDir)) return [];

    return fs
        .readdirSync(baseDir, { withFileTypes: true })
        .filter((dirent) => dirent.isDirectory())
        .map((dirent) => `resources/css/filament/${dirent.name}/theme.css`)
        .filter((file) => fs.existsSync(path.resolve(file)));
}

/**
 * Get all Vite inputs combined (Standard + Filament Themes)
 */
export function getViteInputs() {
    return [...standardInputs, ...getFilamentThemes()];
}

/**
 * Common Vite server watch exclusions for opinionated Prasmanan project
 */
export const commonWatchExclusions = [
    "**/storage/**",
    "**/public/**",
    "**/tests/**",
    "**/vendor/**",
    "**/node_modules/**",
    "**/docs/**",
    "**/database/**",
    "**/config/**",
    "**/bootstrap/**",
    "**/.git/**",
    "**/.idea/**",
    "**/.vscode/**",
    "**/.agents/**",
];
