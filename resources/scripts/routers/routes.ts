import {
  Box,
  BranchesDown,
  ClockArrowRotateLeft,
  CloudArrowUpIn,
  Database,
  FolderOpen,
  Gear,
  House,
  PencilToLine,
  Persons,
  Terminal,
} from "@gravity-ui/icons";
import type { ComponentType, SVGProps } from "react";
import { lazy } from "react";

import AccountSettingsContainer from "@/components/dashboard/AccountSettingsContainer";
import ActivityLogContainer from "@/components/dashboard/activity/ActivityLogContainer";
import ServerActivityLogContainer from "@/components/server/ServerActivityLogContainer";
import BackupContainer from "@/components/server/backups/BackupContainer";
import ServerConsoleContainer from "@/components/server/console/ServerConsoleContainer";
import DatabasesContainer from "@/components/server/databases/DatabasesContainer";
import FileManagerContainer from "@/components/server/files/FileManagerContainer";
import ModrinthContainer from "@/components/server/modrinth/ModrinthContainer";
import NetworkContainer from "@/components/server/network/NetworkContainer";
import ScheduleContainer from "@/components/server/schedules/ScheduleContainer";
import SettingsContainer from "@/components/server/settings/SettingsContainer";
import ShellContainer from "@/components/server/shell/ShellContainer";
import StartupContainer from "@/components/server/startup/StartupContainer";
import CreateUserContainer from "@/components/server/users/CreateUserContainer";
import EditUserContainer from "@/components/server/users/EditUserContainer";
import UsersContainer from "@/components/server/users/UsersContainer";

const FileEditContainer = lazy(
  () => import("@/components/server/files/FileEditContainer"),
);
const ScheduleEditContainer = lazy(
  () => import("@/components/server/schedules/ScheduleEditContainer"),
);

type IconComponent = ComponentType<SVGProps<SVGSVGElement>>;

export type FeatureLimitKey = "databases" | "backups" | "allocations";

interface RouteDefinition {
  route: string;
  path?: string;
  name: string | undefined;
  component: ComponentType;
  end?: boolean;
}

export interface ServerRouteDefinition extends RouteDefinition {
  permission?: string | string[] | null;
  icon?: IconComponent;
  featureLimit?: FeatureLimitKey | "network";
  isSubRoute?: boolean;
  highlightPatterns?: RegExp[];
}

interface Routes {
  account: RouteDefinition[];
  server: ServerRouteDefinition[];
}

const routes: Routes = {
  account: [
    {
      route: "",
      path: "",
      name: "Account",
      component: AccountSettingsContainer,
      end: true,
    },
    {
      route: "api",
      path: "api",
      name: "API Credentials",
      component: AccountSettingsContainer,
    },
    {
      route: "ssh",
      path: "ssh",
      name: "SSH Keys",
      component: AccountSettingsContainer,
    },
    {
      route: "security",
      path: "security",
      name: "Security",
      component: AccountSettingsContainer,
    },
    {
      route: "activity",
      path: "activity",
      name: "Activity",
      component: ActivityLogContainer,
    },
  ],
  server: [
    {
      route: "",
      path: "",
      permission: null,
      name: "Home",
      component: ServerConsoleContainer,
      icon: House,
      end: true,
    },
    {
      route: "files/*",
      path: "files",
      permission: "file.*",
      name: "Files",
      component: FileManagerContainer,
      icon: FolderOpen,
      highlightPatterns: [/^\/server\/[^/]+\/files(\/.*)?$/],
    },
    {
      route: "files/:action/*",
      permission: "file.*",
      name: undefined,
      component: FileEditContainer,
      isSubRoute: true,
    },
    {
      route: "databases/*",
      path: "databases",
      permission: "database.*",
      name: "Databases",
      component: DatabasesContainer,
      icon: Database,
      featureLimit: "databases",
      end: true,
    },
    {
      route: "backups/*",
      path: "backups",
      permission: "backup.*",
      name: "Backups",
      component: BackupContainer,
      icon: CloudArrowUpIn,
      featureLimit: "backups",
      end: true,
    },
    {
      route: "network/*",
      path: "network",
      permission: "allocation.*",
      name: "Networking",
      component: NetworkContainer,
      icon: BranchesDown,
      featureLimit: "network",
      end: true,
    },
    {
      route: "users/*",
      path: "users",
      permission: "user.*",
      name: "Users",
      component: UsersContainer,
      icon: Persons,
      end: true,
    },
    {
      route: "users/new",
      permission: "user.*",
      name: undefined,
      component: CreateUserContainer,
      isSubRoute: true,
    },
    {
      route: "users/:id/edit",
      permission: "user.*",
      name: undefined,
      component: EditUserContainer,
      isSubRoute: true,
    },
    {
      route: "startup/*",
      path: "startup",
      permission: ["startup.read", "startup.update", "startup.docker-image"],
      name: "Startup",
      component: StartupContainer,
      icon: Terminal,
      end: true,
    },
    {
      route: "schedules/*",
      path: "schedules",
      permission: "schedule.*",
      name: "Schedules",
      component: ScheduleContainer,
      icon: ClockArrowRotateLeft,
      highlightPatterns: [/^\/server\/[^/]+\/schedules(\/\d+)?$/],
    },
    {
      route: "schedules/:id/*",
      permission: "schedule.*",
      name: undefined,
      component: ScheduleEditContainer,
      isSubRoute: true,
    },
    {
      route: "settings/*",
      path: "settings",
      permission: ["settings.*", "file.sftp"],
      name: "Settings",
      component: SettingsContainer,
      icon: Gear,
      end: true,
    },
    {
      route: "activity/*",
      path: "activity",
      permission: "activity.*",
      name: "Activity",
      component: ServerActivityLogContainer,
      icon: PencilToLine,
      end: true,
    },
    {
      route: "shell/*",
      path: "shell",
      permission: "startup.software",
      name: "Software",
      component: ShellContainer,
      icon: Box,
      end: true,
    },
    {
      route: "mods/*",
      path: "mods",
      permission: ["modrinth.download", "settings.modrinth"],
      name: "Modrinth",
      component: ModrinthContainer,
      isSubRoute: true,
    },
  ],
};

export default routes;

export const getServerNavRoutes = (): ServerRouteDefinition[] => {
  return routes.server.filter(
    (route) => route.name && route.icon && !route.isSubRoute,
  );
};
