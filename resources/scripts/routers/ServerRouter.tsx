import { Suspense, useEffect, useState } from "react";
import { useStoreState } from "easy-peasy";
import { Route, Routes, useLocation, useParams } from "react-router-dom";

import routes from "@/routers/routes";
import blueprintRoutes from "@/blueprint/extends/routers/routes";
import ClientShell from "@/components/elements/ClientShell";
import ErrorBoundary from "@/components/elements/ErrorBoundary";
import PermissionRoute from "@/components/elements/PermissionRoute";
import { NotFound, ServerError } from "@/components/elements/ScreenBlock";
import CommandMenu from "@/components/elements/commandk/CmdK";
import ConflictStateRenderer from "@/components/server/ConflictStateRenderer";
import InstallListener from "@/components/server/InstallListener";
import TransferListener from "@/components/server/TransferListener";
import WebsocketHandler from "@/components/server/WebsocketHandler";
import { httpErrorToHuman } from "@/api/http";
import { ServerContext } from "@/state/server";

const ServerRouter = () => {
  const { id: routeId } = useParams<"id">();
  const location = useLocation();
  const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin);
  const [error, setError] = useState("");
  const server = ServerContext.useStoreState((state) => state.server.data);
  const inConflictState = ServerContext.useStoreState(
    (state) => state.server.inConflictState,
  );
  const getServer = ServerContext.useStoreActions(
    (actions) => actions.server.getServer,
  );
  const clearServerState = ServerContext.useStoreActions(
    (actions) => actions.clearServerState,
  );

  useEffect(() => {
    setError("");
    if (routeId)
      getServer(routeId).catch((reason) => setError(httpErrorToHuman(reason)));
    return () => clearServerState();
  }, [routeId, getServer, clearServerState]);

  return (
    <ClientShell
      server={
        server
          ? { id: server.id, name: server.name, internalId: server.internalId }
          : undefined
      }
    >
      {!server ? (
        error ? (
          <ServerError title="Couldn’t load this server" message={error} />
        ) : (
          <div className="client-loading" role="status">
            <span className="client-loader" />
            Loading your server…
          </div>
        )
      ) : (
        <>
          <CommandMenu />
          <InstallListener />
          <TransferListener />
          <WebsocketHandler />
          {inConflictState &&
          (!rootAdmin ||
            !location.pathname.endsWith(`/server/${server.id}`)) ? (
            <ConflictStateRenderer />
          ) : (
            <ErrorBoundary>
              <Routes location={location}>
                {routes.server.map(
                  ({ route, permission, component: Component }) => (
                    <Route
                      key={route}
                      path={route}
                      element={
                        <PermissionRoute permission={permission ?? undefined}>
                          <Suspense
                            fallback={
                              <div className="client-loading" role="status">
                                Loading…
                              </div>
                            }
                          >
                            <Component />
                          </Suspense>
                        </PermissionRoute>
                      }
                    />
                  ),
                )}
                {blueprintRoutes.server.map(
                  ({ path, permission, component: Component }) => (
                    <Route
                      key={`bp-${path}`}
                      path={path}
                      element={
                        <PermissionRoute permission={permission ?? undefined}>
                          <Suspense
                            fallback={
                              <div className="client-loading" role="status">
                                Loading…
                              </div>
                            }
                          >
                            <Component />
                          </Suspense>
                        </PermissionRoute>
                      }
                    />
                  ),
                )}
                <Route path="*" element={<NotFound />} />
              </Routes>
            </ErrorBoundary>
          )}
        </>
      )}
    </ClientShell>
  );
};

export default ServerRouter;
