import {
  ArrowUpRightFromSquare as ArrowUpRight,
  Terminal,
} from "@gravity-ui/icons";
import { useEffect, useState } from "react";
import { Link } from "react-router-dom";

import { bytesToString, ip } from "@/lib/formatters";
import { type Server } from "@/api/server/getServer";
import getServerResourceUsage, {
  type ServerStats,
} from "@/api/server/getServerResourceUsage";
import BlueprintBeforeEntryName from "@blueprint/components/Dashboard/Serverlist/ServerRow/BeforeEntryName";
import BlueprintAfterEntryName from "@blueprint/components/Dashboard/Serverlist/ServerRow/AfterEntryName";
import BlueprintBeforeEntryDescription from "@blueprint/components/Dashboard/Serverlist/ServerRow/BeforeEntryDescription";
import BlueprintAfterEntryDescription from "@blueprint/components/Dashboard/Serverlist/ServerRow/AfterEntryDescription";

const Usage = ({
  title,
  value,
  current,
  limit,
}: {
  title: string;
  value: string;
  current: number;
  limit: number;
}) => {
  const percentage =
    limit > 0 ? Math.min(100, Math.max(0, (current / limit) * 100)) : 0;
  return (
    <div className={`client-server-usage ${percentage >= 90 ? "is-high" : ""}`}>
      <div>
        <span>{title}</span>
        <strong>{value}</strong>
      </div>
      <div className="client-usage-track" aria-hidden>
        <span style={{ width: `${percentage}%` }} />
      </div>
    </div>
  );
};

const ServerRow = ({
  server,
  className,
  layout = "list",
}: {
  server: Server;
  className?: string;
  layout?: "list" | "grid";
}) => {
  const [stats, setStats] = useState<ServerStats | null>(null);
  const [unavailable, setUnavailable] = useState(false);

  useEffect(() => {
    let active = true;
    setStats(null);
    setUnavailable(false);
    if (server.status || server.isTransferring || server.isNodeUnderMaintenance)
      return;
    const update = async () => {
      try {
        const next = await getServerResourceUsage(server.uuid);
        if (active) {
          setStats(next);
          setUnavailable(false);
        }
      } catch {
        if (active) {
          setStats(null);
          setUnavailable(true);
        }
      }
    };
    void update();
    const timer = window.setInterval(() => void update(), 30000);
    return () => {
      active = false;
      window.clearInterval(timer);
    };
  }, [
    server.uuid,
    server.status,
    server.isTransferring,
    server.isNodeUnderMaintenance,
  ]);

  const suspended = server.status === "suspended" || stats?.isSuspended;
  const installing = server.status === "installing" || stats?.isInstalling;
  const installFailed =
    server.status === "install_failed" || server.status === "reinstall_failed";
  const state = installFailed
    ? "Install failed"
    : suspended
      ? "Suspended"
      : server.isTransferring
        ? "Transferring"
        : server.isNodeUnderMaintenance
          ? "Maintenance"
          : installing
            ? "Installing"
            : server.status === "restoring_backup"
              ? "Restoring backup"
              : stats?.status === "running"
                ? "Online"
                : stats?.status === "offline"
                  ? "Offline"
                  : stats?.status === "starting"
                    ? "Starting"
                    : stats?.status === "stopping"
                      ? "Stopping"
                      : unavailable
                        ? "Connection unavailable"
                        : "Connecting…";
  const tone =
    state === "Online"
      ? "online"
      : ["Suspended", "Connection unavailable", "Install failed"].includes(
            state,
          )
        ? "error"
        : [
              "Installing",
              "Starting",
              "Stopping",
              "Transferring",
              "Maintenance",
              "Restoring backup",
            ].includes(state)
          ? "pending"
          : "neutral";
  const allocation = server.allocations.find((item) => item.isDefault);
  const address = allocation
    ? `${allocation.alias || ip(allocation.ip)}:${allocation.port}`
    : "No connection address";
  const showUsage = stats && !suspended && !installing;

  return (
    <Link
      to={`/server/${server.id}`}
      className={`client-server-card ${layout === "grid" ? "is-grid" : ""} ${className || ""}`}
    >
      <div className="client-server-card-heading">
        <span className="client-server-icon">
          <Terminal aria-hidden width={22} height={22} />
        </span>
        <div className="client-server-name">
          <BlueprintBeforeEntryName />
          <h3>{server.name}</h3>
          <BlueprintAfterEntryName />
          <p>{address}</p>
        </div>
        <ArrowUpRight
          className="client-server-arrow"
          aria-hidden
          width={19}
          height={19}
        />
      </div>
      <div className="client-server-description">
        <BlueprintBeforeEntryDescription />
        {server.description && <p>{server.description}</p>}
        <BlueprintAfterEntryDescription />
      </div>
      <div className="client-server-meta">
        <span className={`client-server-status is-${tone}`}>
          <span aria-hidden />
          {state}
        </span>
        <span className="client-server-node">{server.node}</span>
      </div>
      <div className="client-server-resources">
        {showUsage ? (
          <>
            <Usage
              title="CPU"
              value={`${stats.cpuUsagePercent.toFixed(1)}%`}
              current={stats.cpuUsagePercent}
              limit={server.limits.cpu}
            />
            <Usage
              title="Memory"
              value={bytesToString(stats.memoryUsageInBytes, 1)}
              current={stats.memoryUsageInBytes}
              limit={server.limits.memory * 1024 * 1024}
            />
            <Usage
              title="Storage"
              value={bytesToString(stats.diskUsageInBytes, 1)}
              current={stats.diskUsageInBytes}
              limit={server.limits.disk * 1024 * 1024}
            />
          </>
        ) : (
          <p className="client-usage-unavailable">
            {unavailable
              ? "Open the server to check its connection."
              : suspended ||
                  installing ||
                  server.status ||
                  server.isTransferring ||
                  server.isNodeUnderMaintenance
                ? "Live usage will appear when the server is available."
                : "Connecting to your server…"}
          </p>
        )}
      </div>
    </Link>
  );
};

export default ServerRow;
