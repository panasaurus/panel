import http, { FractalResponseData } from '@/api/http';
import { getGlobalDaemonType } from '@/api/server/getServer';
import { ServerBackup } from '@/api/server/types';
import { rawDataToServerBackup } from '@/api/transformers';

interface RequestParameters {
    name?: string;
    ignored?: string;
    isLocked: boolean;
}

interface CreateBackupResponse {
    data?: any;
    meta?: {
        job_id: string;
        status: string;
        progress: number;
        message?: string;
    };
    job_id?: string;
    status?: 'pending' | 'running' | 'completed' | 'failed';
    message?: string;
    uuid?: string;
    object?: string;
    attributes?: any;
}

export default async (
    uuid: string,
    params: RequestParameters,
): Promise<{ backup: ServerBackup; jobId: string; status: string; progress: number; message?: string }> => {
    const daemonType = getGlobalDaemonType();
    const response = await http.post<CreateBackupResponse>(`/api/client/servers/${daemonType}/${uuid}/backups`, {
        name: params.name,
        ignored: params.ignored,
        is_locked: params.isLocked,
    });

    if (!response.data) {
        throw new Error('Invalid response: missing data');
    }

    if (response.data.data && response.data.meta) {
        const backupData = rawDataToServerBackup(response.data.data);

        return {
            backup: backupData,
            jobId: response.data.meta.job_id,
            status: response.data.meta.status,
            progress: response.data.meta.progress,
            message: response.data.meta.message,
        };
    }

    if (response.data.job_id && response.data.status) {
        const tempBackup: ServerBackup = {
            uuid: '',
            name: params.name || 'Pending...',
            isSuccessful: false,
            isLocked: params.isLocked,
            isAutomatic: false,
            ignoredFiles: params.ignored || '',
            checksum: '',
            bytes: 0,
            sizeGb: 0,
            adapter: '',
            isRustic: false,
            snapshotId: null,
            createdAt: new Date(),
            completedAt: null,
            canRetry: false,
            jobStatus: response.data.status || 'pending',
            jobProgress: 0,
            jobMessage: response.data.message || '',
            jobId: response.data.job_id || null,
            jobError: null,
            jobStartedAt: null,
            jobLastUpdatedAt: null,
            isInProgress: true,
        };

        return {
            backup: tempBackup,
            jobId: response.data.job_id,
            status: response.data.status,
            progress: 0,
            message: response.data.message || '',
        };
    }

    if (response.data.uuid || response.data.object === 'backup' || response.data.attributes) {
        try {
            const backupData = rawDataToServerBackup(
                response.data.attributes ? (response.data as FractalResponseData) : ({ attributes: response.data } as FractalResponseData),
            );

            return {
                backup: backupData,
                jobId: backupData.jobId || '',
                status: backupData.jobStatus || 'pending',
                progress: backupData.jobProgress || 0,
                message: backupData.jobMessage || '',
            };
        } catch (transformError: any) {
            throw new Error(`Failed to process backup response: ${transformError.message}`);
        }
    }

    throw new Error('Invalid response: unknown structure');
};
