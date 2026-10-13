import {
  ArrowUpRightFromSquare as ArrowUpRight,
  Bars,
  ChevronDown,
  LayoutCellsLarge,
  Magnifier,
  SlidersVertical,
  ArrowRotateRight,
  Xmark,
} from "@gravity-ui/icons";
import { useStoreState } from "easy-peasy";
import { useEffect, useRef, useState } from "react";
import { useLocation } from "react-router-dom";
import useSWR from "swr";

import { SaurusMark } from "@/components/elements/PanasaurusLogo";
import ServerRow from "@/components/dashboard/ServerRow";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/elements/DropdownMenu";
import PageContentBlock from "@/components/elements/PageContentBlock";
import Pagination from "@/components/elements/Pagination";
import getServers from "@/api/getServers";
import { type PaginatedResult } from "@/api/http";
import { type Server } from "@/api/server/getServer";
import useFlash from "@/plugins/useFlash";
import { usePersistedState } from "@/plugins/usePersistedState";
import BlueprintGlobalBeforeSection from "@blueprint/components/Dashboard/Global/BeforeSection";
import BlueprintGlobalAfterSection from "@blueprint/components/Dashboard/Global/AfterSection";
import BlueprintServerlistBeforeContent from "@blueprint/components/Dashboard/Serverlist/BeforeContent";
import BlueprintServerlistAfterContent from "@blueprint/components/Dashboard/Serverlist/AfterContent";

const DashboardContainer = () => {
  const location = useLocation();
  const defaultPage = Number(
    new URLSearchParams(location.search).get("page") || "1",
  );
  const [page, setPage] = useState(
    Number.isInteger(defaultPage) && defaultPage > 0 ? defaultPage : 1,
  );
  const [search, setSearch] = useState("");
  const [query, setQuery] = useState("");
  const searchField = useRef<HTMLInputElement>(null);
  const user = useStoreState((state) => state.user.data!);
  const { clearFlashes, clearAndAddHttpError } = useFlash();
  const [serverViewMode, setServerViewMode] = usePersistedState<
    "owner" | "admin-all" | "all"
  >(`${user.uuid}:server_view_mode`, "owner");
  const [display, setDisplay] = usePersistedState<"list" | "grid">(
    `${user.uuid}:dashboard_display_option`,
    "grid",
  );
  const view =
    !user.rootAdmin && serverViewMode === "admin-all"
      ? "owner"
      : serverViewMode;
  const viewLabel =
    view === "admin-all"
      ? "All servers (admin)"
      : view === "all"
        ? "All accessible servers"
        : "Your servers";
  const {
    data: servers,
    error,
    mutate,
    isValidating,
  } = useSWR<PaginatedResult<Server>>(
    ["/api/client/servers", view, page, query],
    () => getServers({ page, type: view, query: query || undefined }),
    { revalidateOnFocus: false },
  );

  useEffect(() => {
    if (search.trim() === query) return;
    const timeout = window.setTimeout(() => {
      setQuery(search.trim());
      setPage(1);
    }, 300);
    return () => window.clearTimeout(timeout);
  }, [search, query]);

  useEffect(() => {
    if (servers && servers.pagination.currentPage > 1 && !servers.items.length)
      setPage(1);
  }, [servers]);

  useEffect(() => {
    const url = new URL(window.location.href);
    if (page > 1) url.searchParams.set("page", String(page));
    else url.searchParams.delete("page");
    window.history.replaceState(window.history.state, "", url);
  }, [page]);

  useEffect(() => {
    if (location.state?.focusServerSearch) searchField.current?.focus();
  }, [location.key, location.state]);

  useEffect(() => {
    if (error) clearAndAddHttpError({ key: "dashboard", error });
    else clearFlashes("dashboard");
  }, [error, clearAndAddHttpError, clearFlashes]);

  const changeView = (value: "owner" | "admin-all" | "all") => {
    setPage(1);
    setServerViewMode(value);
  };
  const count = servers?.pagination.total;

  return (
    <PageContentBlock
      title="Your servers"
      showFlashKey="dashboard"
      className="client-dashboard-page"
    >
      <BlueprintGlobalBeforeSection />
      <div className="client-page-intro">
        <div>
          <h1>Welcome back, {user.username}.</h1>
          <p>Your servers, your people, your next adventure.</p>
        </div>
      </div>
      <section
        id="client-server-library"
        className="client-server-library"
        aria-labelledby="client-library-title"
      >
        <div className="client-library-heading">
          <div>
            <h2 id="client-library-title">Server library</h2>
            <span
              className="client-count"
              aria-label={
                count === undefined
                  ? "Loading server count"
                  : `${count} servers`
              }
            >
              {count ?? "—"}
            </span>
          </div>
          <span className="client-library-caption">
            A little space. Endless possibilities.
          </span>
        </div>
        <div className="client-library-toolbar">
          <div className="client-server-search">
            <Magnifier aria-hidden width={18} height={18} />
            <input
              ref={searchField}
              id="client-server-search"
              type="search"
              placeholder="Search your servers…"
              aria-label="Search servers"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
            {search && (
              <button
                aria-label="Clear search"
                onClick={() => {
                  setSearch("");
                  searchField.current?.focus();
                }}
              >
                <Xmark aria-hidden width={16} height={16} />
              </button>
            )}
          </div>
          <div className="client-library-controls">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <button className="client-filter-button">
                  <SlidersVertical aria-hidden width={16} height={16} />
                  <span>{viewLabel}</span>
                  <ChevronDown aria-hidden width={14} height={14} />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent className="client-dropdown" align="end">
                <DropdownMenuItem onSelect={() => changeView("owner")}>
                  Your servers
                </DropdownMenuItem>
                <DropdownMenuItem onSelect={() => changeView("all")}>
                  All accessible servers
                </DropdownMenuItem>
                {user.rootAdmin && (
                  <DropdownMenuItem onSelect={() => changeView("admin-all")}>
                    All servers (admin)
                  </DropdownMenuItem>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
            <div
              className="client-view-switch"
              role="group"
              aria-label="Server layout"
            >
              <button
                aria-label="List view"
                aria-pressed={display === "list"}
                onClick={() => setDisplay("list")}
              >
                <Bars aria-hidden width={18} height={18} />
              </button>
              <button
                aria-label="Grid view"
                aria-pressed={display === "grid"}
                onClick={() => setDisplay("grid")}
              >
                <LayoutCellsLarge aria-hidden width={18} height={18} />
              </button>
            </div>
            <button
              className={`client-icon-button ${isValidating ? "is-refreshing" : ""}`}
              aria-label="Refresh servers"
              disabled={isValidating}
              onClick={() => void mutate()}
            >
              <ArrowRotateRight aria-hidden width={19} height={19} />
            </button>
          </div>
        </div>
        <BlueprintServerlistBeforeContent />
        {error ? (
          <div className="client-empty-state">
            <h3>We couldn’t load your servers.</h3>
            <p>Check your connection and try again.</p>
            <button
              className="client-secondary-button"
              onClick={() => void mutate()}
            >
              Try again
            </button>
          </div>
        ) : !servers ? (
          <div className="client-loading" role="status">
            <span className="client-loader" />
            Loading your servers…
          </div>
        ) : (
          <Pagination data={servers} onPageSelect={setPage}>
            {({ items }) =>
              items.length ? (
                <div
                  className={`client-server-collection ${display === "grid" ? "is-grid" : ""}`}
                >
                  {items.map((server) => (
                    <ServerRow
                      key={server.uuid}
                      server={server}
                      layout={display}
                    />
                  ))}
                </div>
              ) : (
                <div className="client-empty-state">
                  <div className="client-empty-icon">
                    <SaurusMark dead className="client-empty-saurus" />
                  </div>
                  <h3>{query ? "No matching servers." : "No servers yet"}</h3>
                  {query && <p>Try a different name or clear your search.</p>}
                  {query ? (
                    <button
                      className="client-secondary-button"
                      onClick={() => setSearch("")}
                    >
                      Clear search
                    </button>
                  ) : user.rootAdmin ? (
                    <a
                      className="client-secondary-button"
                      href="/admin/servers/new"
                    >
                      Create a server{" "}
                      <ArrowUpRight aria-hidden width={16} height={16} />
                    </a>
                  ) : view !== "all" ? (
                    <button
                      className="client-secondary-button"
                      onClick={() => changeView("all")}
                    >
                      Show all accessible servers
                    </button>
                  ) : (
                    <button
                      className="client-secondary-button"
                      onClick={() => void mutate()}
                    >
                      Refresh servers
                    </button>
                  )}
                </div>
              )
            }
          </Pagination>
        )}
        <BlueprintServerlistAfterContent />
      </section>
      <BlueprintGlobalAfterSection />
    </PageContentBlock>
  );
};

export default DashboardContainer;
