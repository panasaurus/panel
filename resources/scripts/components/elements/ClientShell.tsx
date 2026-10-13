import { ArrowLeft, Bars, Gear, House, Puzzle, Xmark } from "@gravity-ui/icons";
import { useStoreState } from "easy-peasy";
import { type ReactNode, useEffect, useRef, useState } from "react";
import { Link, NavLink, useLocation, useNavigate } from "react-router-dom";

import blueprintRoutes from "@/blueprint/extends/routers/routes";
import BlueprintAttribution from "@/blueprint/extends/Attribution";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/elements/DropdownMenu";
import Logo from "@/components/elements/PanasaurusLogo";
import UserAvatar from "@/components/elements/UserAvatar";
import ServerSidebarNavItem from "@/components/server/ServerSidebarNavItem";
import http from "@/api/http";
import { getServerNavRoutes } from "@/routers/routes";
import "@/assets/client.css";

interface ClientShellProps {
  children: ReactNode;
  server?: { id: string; name: string; internalId: number | string };
}

const ClientShell = ({ children, server }: ClientShellProps) => {
  const user = useStoreState((state) => state.user.data!);
  const location = useLocation();
  const navigate = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);
  const menuButton = useRef<HTMLButtonElement>(null);
  const sidebar = useRef<HTMLElement>(null);
  const [loggingOut, setLoggingOut] = useState(false);
  const title =
    server?.name ||
    (location.pathname.startsWith("/account") ? "Account settings" : "Servers");
  const adminHref = server
    ? `/admin/servers/view/${server.internalId}`
    : "/admin";

  useEffect(() => {
    setMenuOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    const desktop = window.matchMedia("(min-width: 1024px)");
    const closeOnDesktop = () => {
      if (desktop.matches) setMenuOpen(false);
    };
    desktop.addEventListener("change", closeOnDesktop);
    return () => desktop.removeEventListener("change", closeOnDesktop);
  }, []);

  useEffect(() => {
    if (!menuOpen) return;
    const previous = document.activeElement as HTMLElement | null;
    const focusFrame = window.requestAnimationFrame(() => {
      sidebar.current?.querySelector<HTMLElement>("a, button")?.focus();
    });
    const keydown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setMenuOpen(false);
        menuButton.current?.focus();
      }
      if (event.key !== "Tab") return;
      const items = sidebar.current?.querySelectorAll<HTMLElement>("a, button");
      if (!items?.length) return;
      const first = items[0];
      const last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last?.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first?.focus();
      }
    };
    document.addEventListener("keydown", keydown);
    return () => {
      window.cancelAnimationFrame(focusFrame);
      document.removeEventListener("keydown", keydown);
      previous?.focus();
    };
  }, [menuOpen]);

  useEffect(() => {
    if (server) return;
    const keydown = (event: KeyboardEvent) => {
      if (event.key.toLowerCase() === "k" && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        navigate("/", { state: { focusServerSearch: true } });
      }
    };
    document.addEventListener("keydown", keydown);
    return () => document.removeEventListener("keydown", keydown);
  }, [navigate, server]);

  const logout = () => {
    setLoggingOut(true);
    http
      .post("/auth/logout")
      .finally(() => window.location.assign("/auth/login"));
  };

  return (
    <div className="client-shell">
      {menuOpen && (
        <button
          className="client-menu-scrim"
          aria-label="Close navigation"
          onClick={() => setMenuOpen(false)}
        />
      )}
      <aside
        ref={sidebar}
        id="client-sidebar"
        className={`client-sidebar ${menuOpen ? "is-open" : ""}`}
      >
        <div className="client-sidebar-brand">
          <Link to="/" aria-label="Panasaurus home">
            <Logo />
          </Link>
          <button
            className="client-icon-button client-menu-close"
            aria-label="Close navigation"
            onClick={() => setMenuOpen(false)}
          >
            <Xmark aria-hidden width={20} height={20} />
          </button>
        </div>
        {server ? (
          <Link to="/" className="client-back-link">
            <ArrowLeft aria-hidden width={16} height={16} /> All servers
          </Link>
        ) : (
          <p className="client-nav-label">Your workspace</p>
        )}
        <nav
          aria-label={server ? "Server navigation" : "Workspace navigation"}
          className="client-nav"
        >
          {server ? (
            getServerNavRoutes().map((route) => (
              <ServerSidebarNavItem
                key={route.path || "home"}
                route={route}
                serverId={server.id}
                onClick={() => setMenuOpen(false)}
              />
            ))
          ) : (
            <>
              <NavLink to="/" end>
                <House aria-hidden width={20} height={20} /> Servers
              </NavLink>
              <NavLink to="/account">
                <Gear aria-hidden width={20} height={20} /> Account settings
              </NavLink>
              {blueprintRoutes.account
                .filter(
                  (route) => route.name && (!route.adminOnly || user.rootAdmin),
                )
                .map(({ path, name }) => (
                  <NavLink
                    key={path}
                    to={`/account/${path}`.replace("//", "/")}
                    end
                  >
                    <Puzzle aria-hidden width={20} height={20} /> {name}
                  </NavLink>
                ))}
            </>
          )}
        </nav>
        <div className="client-sidebar-bottom">
          <div className="client-attribution">
            <BlueprintAttribution />
          </div>
        </div>
      </aside>
      <div
        className={`client-workspace ${menuOpen ? "is-menu-open" : ""}`}
        inert={menuOpen || undefined}
      >
        <header className="client-topbar">
          <button
            ref={menuButton}
            className="client-icon-button client-menu-toggle"
            aria-label="Open navigation"
            aria-controls="client-sidebar"
            aria-expanded={menuOpen}
            onClick={() => setMenuOpen(true)}
          >
            <Bars aria-hidden width={21} height={21} />
          </button>
          <div className="client-breadcrumb">
            <span>Workspace</span>
            <span aria-hidden>/</span>
            <strong>{title}</strong>
          </div>
          <div className="client-topbar-actions">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <button className="client-avatar" aria-label="Account menu">
                  <UserAvatar user={user} />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent
                className="client-dropdown"
                align="end"
                sideOffset={12}
              >
                <DropdownMenuLabel>
                  <strong>{user.username}</strong>
                  <span className="client-user-email">{user.email}</span>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem onSelect={() => navigate("/account")}>
                  Account settings
                </DropdownMenuItem>
                {user.rootAdmin && (
                  <DropdownMenuItem
                    onSelect={() => window.location.assign(adminHref)}
                  >
                    Admin panel
                  </DropdownMenuItem>
                )}
                <DropdownMenuSeparator />
                <DropdownMenuItem disabled={loggingOut} onSelect={logout}>
                  {loggingOut ? "Signing out…" : "Sign out"}
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </header>
        <main
          className="client-content"
          data-pyro-main=""
          data-pyro-transitionrouter=""
        >
          {children}
        </main>
      </div>
    </div>
  );
};

export default ClientShell;
