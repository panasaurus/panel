import {
    BarsPlay,
    Copy,
    EllipsisVertical,
    FileArrowDown,
    FileZipper,
    PencilToLine,
    Shield,
    TrashBin,
} from '@gravity-ui/icons';
import { join } from 'pathe';
import { ReactNode, memo, useState } from 'react';
import isEqual from 'react-fast-compare';
import { toast } from 'sonner';

import ActionButton from '@/components/elements/ActionButton';
import Can from '@/components/elements/Can';
import { ContextMenuContent, ContextMenuItem } from '@/components/elements/ContextMenu';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/elements/DropdownMenu';
import { Dialog } from '@/components/elements/dialog';
import ChmodFileModal from '@/components/server/files/ChmodFileModal';
import RenameFileModal from '@/components/server/files/RenameFileModal';

import compressFiles from '@/api/server/files/compressFiles';
import copyFile from '@/api/server/files/copyFile';
import decompressFiles from '@/api/server/files/decompressFiles';
import deleteFiles from '@/api/server/files/deleteFiles';
import getFileDownloadUrl from '@/api/server/files/getFileDownloadUrl';
import { FileObject } from '@/api/server/files/loadDirectory';

import { ServerContext } from '@/state/server';

import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import useFlash from '@/plugins/useFlash';

type ModalType = 'rename' | 'move' | 'chmod';

interface Props {
    file: FileObject;
    children: (actions: ReactNode) => ReactNode;
}

const FileDropdownMenu = ({ file, children }: Props) => {
    const [modal, setModal] = useState<ModalType | null>(null);
    const [showConfirmation, setShowConfirmation] = useState(false);

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { mutate } = useFileManagerSwr();
    const { clearAndAddHttpError, clearFlashes } = useFlash();
    const directory = ServerContext.useStoreState((state) => state.files.directory);

    const doDeletion = async () => {
        clearFlashes('files');

        await mutate((files) => files!.filter((f) => f.key !== file.key), false);

        deleteFiles(uuid, directory, [file.name]).catch((error) => {
            mutate();
            clearAndAddHttpError({ key: 'files', error });
        });

        setShowConfirmation(false);
    };

    const doCopy = () => {
        clearFlashes('files');
        toast.info('Duplicating...');

        copyFile(uuid, join(directory, file.name))
            .then(() => mutate())
            .then(() => toast.success('File successfully duplicated.'))
            .catch((error) => clearAndAddHttpError({ key: 'files', error }));
    };

    const doDownload = () => {
        clearFlashes('files');

        getFileDownloadUrl(uuid, join(directory, file.name))
            .then((url) => {
                window.location.href = url;
            })
            .catch((error) => clearAndAddHttpError({ key: 'files', error }));
    };

    const doArchive = () => {
        clearFlashes('files');
        toast.info('Archiving files...');

        compressFiles(uuid, directory, [file.name])
            .then(() => mutate())
            .then(() => toast.success('Files successfully archived.'))
            .catch((error) => clearAndAddHttpError({ key: 'files', error }));
    };

    const doUnarchive = () => {
        clearFlashes('files');
        toast.info('Unarchiving files...');

        decompressFiles(uuid, directory, file.name)
            .then(() => mutate())
            .then(() => toast.success('Files successfully unarchived.'))
            .catch((error) => clearAndAddHttpError({ key: 'files', error }));
    };

    const renderItems = (MenuItem: typeof ContextMenuItem | typeof DropdownMenuItem) => (
        <>
            <Can action={'file.update'}>
                <MenuItem className='flex gap-2' onSelect={() => setModal('rename')}>
                    <PencilToLine className='h-4! w-4!' fill='currentColor' />
                    <span>Rename</span>
                </MenuItem>
                <MenuItem className='flex gap-2' onSelect={() => setModal('move')}>
                    <BarsPlay className='h-4! w-4!' fill='currentColor' />
                    <span>Move</span>
                </MenuItem>
                <MenuItem className='flex gap-2' onSelect={() => setModal('chmod')}>
                    <Shield className='h-4! w-4!' fill='currentColor' />
                    <span>Permissions</span>
                </MenuItem>
            </Can>
            {file.isFile && (
                <Can action={'file.create'}>
                    <MenuItem className='flex gap-2' onClick={doCopy}>
                        <Copy className='h-4! w-4!' fill='currentColor' />
                        <span>Duplicate</span>
                    </MenuItem>
                </Can>
            )}
            {file.isArchiveType() ? (
                <Can action={'file.create'}>
                    <MenuItem className='flex gap-2' onSelect={doUnarchive} title={'Unarchive'}>
                        <FileZipper className='h-4! w-4!' fill='currentColor' />
                        <span>Unarchive</span>
                    </MenuItem>
                </Can>
            ) : (
                <Can action={'file.archive'}>
                    <MenuItem className='flex gap-2' onSelect={doArchive}>
                        <FileZipper className='h-4! w-4!' fill='currentColor' />
                        <span>Archive</span>
                    </MenuItem>
                </Can>
            )}
            {file.isFile && (
                <Can action={'file.read'}>
                    <MenuItem className='flex gap-2' onSelect={doDownload}>
                        <FileArrowDown className='h-4! w-4!' fill='currentColor' />
                        <span>Download</span>
                    </MenuItem>
                </Can>
            )}
            <Can action={'file.delete'}>
                <MenuItem className='flex gap-2' onSelect={() => setShowConfirmation(true)}>
                    <TrashBin className='h-4! w-4!' fill='currentColor' />
                    <span>Delete</span>
                </MenuItem>
            </Can>
        </>
    );

    return (
        <>
            <Dialog.Confirm
                open={showConfirmation}
                onClose={() => setShowConfirmation(false)}
                title={`Delete ${file.isFile ? 'File' : 'Directory'}`}
                confirm={'Delete'}
                onConfirmed={doDeletion}
            >
                You will not be able to recover the contents of
                <span className={'font-semibold text-zinc-50'}> {file.name}</span> once deleted.
            </Dialog.Confirm>
            {modal ? (
                modal === 'chmod' ? (
                    <ChmodFileModal
                        visible
                        appear
                        files={[{ file: file.name, mode: file.modeBits }]}
                        onDismissed={() => setModal(null)}
                    />
                ) : (
                    <RenameFileModal
                        visible
                        appear
                        files={[file.name]}
                        useMoveTerminology={modal === 'move'}
                        onDismissed={() => setModal(null)}
                    />
                )
            ) : null}
            <ContextMenuContent className='flex flex-col gap-1'>
                {renderItems(ContextMenuItem)}
            </ContextMenuContent>
            {children(
                <Can
                    matchAny
                    action={[
                        'file.update',
                        'file.archive',
                        'file.delete',
                        ...(file.isFile ? ['file.create', 'file.read'] : []),
                    ]}
                >
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <ActionButton
                                variant='secondary'
                                size='sm'
                                aria-label={`Actions for ${file.name}`}
                                className='ml-2 mr-2 h-9! w-9! shrink-0 p-0! border-transparent! bg-transparent! hover:bg-zinc-700!'
                                onClick={(event) => event.stopPropagation()}
                            >
                                <EllipsisVertical width={20} height={20} aria-hidden />
                            </ActionButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align='end' className='flex flex-col gap-1 border border-[#27272a]'>
                            {renderItems(DropdownMenuItem)}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </Can>
            )}
        </>
    );
};

export default memo(FileDropdownMenu, isEqual);
