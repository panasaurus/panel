// Provides necessary information for components to function properly
// million-ignore
const PanasaurusProvider = ({ children }) => {
    return (
        <div
            data-pyro-panasaurusprovider=''
            data-pyro-panasaurus-version={import.meta.env.VITE_PANASAURUS_VERSION}
            data-pyro-panasaurus-build={import.meta.env.VITE_PANASAURUS_BUILD_NUMBER}
            data-pyro-commit-hash={import.meta.env.VITE_COMMIT_HASH}
            style={{
                display: 'contents',
            }}
        >
            {children}
        </div>
    );
};

export default PanasaurusProvider;
