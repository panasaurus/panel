import { hashToPath } from '@/helpers';
import { useVirtualizer } from '@tanstack/react-virtual';
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { useLocation } from 'react-router-dom';

import Can from '@/components/elements/Can';
import { Checkbox } from '@/components/elements/CheckboxNew';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import { ServerError } from '@/components/elements/ScreenBlock';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import FileManagerBreadcrumbs from '@/components/server/files/FileManagerBreadcrumbs';
import FileManagerStatus from '@/components/server/files/FileManagerStatus';
import FileObjectRow from '@/components/server/files/FileObjectRow';
import MassActionsBar from '@/components/server/files/MassActionsBar';
import NewDirectoryButton from '@/components/server/files/NewDirectoryButton';
import UploadButton from '@/components/server/files/UploadButton';

import { httpErrorToHuman } from '@/api/http';
import { FileObject } from '@/api/server/files/loadDirectory';

import { useStoreActions } from '@/state/hooks';
import { ServerContext } from '@/state/server';

import useFileManagerSwr from '@/plugins/useFileManagerSwr';

import NewFileButton from './NewFileButton';
import BlueprintBeforeContent from '@blueprint/components/Server/Files/Browse/BeforeContent';
import BlueprintAfterContent from '@blueprint/components/Server/Files/Browse/AfterContent';

const sortFiles = (files: FileObject[]): FileObject[] => {
    const sortedFiles: FileObject[] = [...files]
        .sort((a, b) => a.name.localeCompare(b.name))
        .sort((a, b) => (a.isFile === b.isFile ? 0 : a.isFile ? 1 : -1));
    return sortedFiles.filter((file, index) => index === 0 || file.name !== sortedFiles[index - 1]?.name);
};

const FileManagerContainer = () => {
    const listRef = useRef<HTMLDivElement | null>(null);
    const [scrollMargin, setScrollMargin] = useState(0);

    const id = ServerContext.useStoreState((state) => state.server.data!.id);
    const { hash, pathname } = useLocation();
    const { data: files, error, mutate } = useFileManagerSwr();

    const directory = ServerContext.useStoreState((state) => state.files.directory);
    const clearFlashes = useStoreActions((actions) => actions.flashes.clearFlashes);
    const setDirectory = ServerContext.useStoreActions((actions) => actions.files.setDirectory);

    const setSelectedFiles = ServerContext.useStoreActions((actions) => actions.files.setSelectedFiles);
    const selectedFilesLength = ServerContext.useStoreState((state) => state.files.selectedFiles.length);

    const searchInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        clearFlashes('files');
        setSelectedFiles([]);
        setDirectory(hashToPath(hash));
    }, [hash, clearFlashes, setSelectedFiles, setDirectory]);

    useEffect(() => {
        mutate();
    }, [directory, mutate]);

    const [searchTerm, setSearchTerm] = useState('');
    const filesArray = sortFiles(files ?? []).filter((file) =>
        file.name.toLowerCase().includes(searchTerm.toLowerCase()),
    );
    const selectedFiles = ServerContext.useStoreState((state) => state.files.selectedFiles);
    const allVisibleSelected =
        filesArray.length > 0 && filesArray.every((file) => selectedFiles.includes(file.name));
    const onSelectAllClick = () => {
        setSelectedFiles(allVisibleSelected ? [] : filesArray.map((file) => file.name));
    };

    useEffect(() => {
        setSearchTerm('');
        listRef.current?.closest('.client-content')?.scrollTo({ top: 0 });
        if (searchInputRef.current) searchInputRef.current.value = '';
    }, [hash, pathname, directory]);

    useLayoutEffect(() => {
        const list = listRef.current;
        const viewport = list?.closest<HTMLElement>('.client-content');
        if (!list || !viewport) return;
        const measure = () =>
            setScrollMargin(
                list.getBoundingClientRect().top -
                    viewport.getBoundingClientRect().top +
                    viewport.scrollTop -
                    viewport.clientTop,
            );
        measure();
        const observer = new ResizeObserver(measure);
        observer.observe(viewport);
        if (list.parentElement) observer.observe(list.parentElement);
        return () => observer.disconnect();
    }, [files?.length, searchTerm]);

    const rowVirtualizer = useVirtualizer({
        count: filesArray.length,
        getScrollElement: () => listRef.current?.closest<HTMLElement>('.client-content') ?? null,
        estimateSize: () => 54,
        getItemKey: (index) => filesArray[index]!.name,
        scrollMargin,
        overscan: 8,
    });

    if (error) {
        return <ServerError title={'Something went wrong.'} message={httpErrorToHuman(error)} />;
    }

    return (
        <ServerContentBlock
            className={`client-file-manager ${selectedFilesLength > 0 ? 'client-file-manager-selected' : ''}`}
            title={'File Manager'}
            showFlashKey={'files'}
        >
            <BlueprintBeforeContent />
            <h1 className='sr-only'>Files</h1>
            <div className='min-w-0'>
                <ErrorBoundary>
                    <div className='client-file-toolbar mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between'>
                        <div className='client-file-toolbar-path min-w-0 flex-1'>
                            <FileManagerBreadcrumbs
                                renderLeft={
                                    <Checkbox
                                        aria-label='Select all visible files'
                                        className='ml-[1.22rem] mr-4 shrink-0'
                                        checked={allVisibleSelected}
                                        onCheckedChange={() => onSelectAllClick()}
                                    />
                                }
                            />
                        </div>
                        <Can action={'file.create'}>
                            <div className='client-file-toolbar-actions flex shrink-0 flex-wrap items-center gap-2'>
                                <FileManagerStatus />
                                <NewDirectoryButton />
                                <NewFileButton id={id} />
                                <UploadButton />
                            </div>
                        </Can>
                    </div>
                </ErrorBoundary>
            </div>
            {!files ? null : (
                <>
                    {!files.length ? (
                        <p className={`text-sm text-zinc-400 text-center`}>This folder is empty.</p>
                    ) : (
                        <>
                            <div className='relative mb-4 border border-[#27272a] rounded-lg'>
                                <div className='absolute left-4 top-1/2 pl-2 -translate-y-1/2 pointer-events-none'>
                                    <svg
                                        xmlns='http://www.w3.org/2000/svg'
                                        fill='none'
                                        viewBox='0 0 24 24'
                                        strokeWidth={1.5}
                                        stroke='currentColor'
                                        className='w-5 h-5 opacity-40'
                                    >
                                        <path
                                            strokeLinecap='round'
                                            strokeLinejoin='round'
                                            d='m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z'
                                        />
                                    </svg>
                                </div>

                                <input
                                    ref={searchInputRef}
                                    className='pl-14 py-4 w-full rounded-lg bg-[#ffffff11] text-sm font-bold outline-none'
                                    type='text'
                                    placeholder='Search...'
                                    aria-label='Search files'
                                    onChange={(event) => setSearchTerm(event.target.value)}
                                />
                            </div>
                            {!filesArray.length && (
                                <p className='py-8 text-center text-sm text-zinc-400'>
                                    No files match your search.
                                </p>
                            )}
                            <div ref={listRef} className='relative'>
                                <div
                                    data-pyro-file-manager-files
                                    className='overflow-hidden rounded-lg bg-[#101012]'
                                    style={{ height: `${rowVirtualizer.getTotalSize()}px` }}
                                >
                                    <div
                                        className='w-full overflow-hidden rounded-lg gap-0.5 flex flex-col'
                                        style={{
                                            height: `${rowVirtualizer.getTotalSize()}px`,
                                            width: '100%',
                                            position: 'relative',
                                        }}
                                    >
                                        {rowVirtualizer.getVirtualItems().map((item) => {
                                            if (filesArray[item.index] !== undefined) {
                                                return (
                                                    <div
                                                        key={item.key}
                                                        className='w-full absolute left-0 top-0'
                                                        style={{
                                                            height: `${item.size}px`,
                                                            transform: `translateY(${item.start - scrollMargin}px)`,
                                                        }}
                                                    >
                                                        <FileObjectRow
                                                            file={filesArray[item.index]!}
                                                            key={filesArray[item.index]?.name}
                                                        />
                                                    </div>
                                                );
                                            }
                                            return <></>;
                                        })}
                                    </div>
                                </div>
                            </div>
                            <MassActionsBar />
                        </>
                    )}
                </>
            )}
            <BlueprintAfterContent />
        </ServerContentBlock>
    );
};

export default FileManagerContainer;
