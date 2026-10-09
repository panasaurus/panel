/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_PANASAURUS_VERSION: string;
    readonly VITE_COMMIT_HASH: string;
    readonly VITE_BRANCH_NAME: string;
    readonly VITE_PANASAURUS_BUILD_NUMBER: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
