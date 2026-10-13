import { Suspense } from "react";
import { Route, Routes } from "react-router-dom";
import { useStoreState } from "easy-peasy";

import routes from "@/routers/routes";
import blueprintRoutes from "@/blueprint/extends/routers/routes";
import DashboardContainer from "@/components/dashboard/DashboardContainer";
import ClientShell from "@/components/elements/ClientShell";
import { NotFound } from "@/components/elements/ScreenBlock";

const DashboardRouter = () => {
  const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin);

  return (
    <ClientShell>
      <Suspense
        fallback={
          <div className="client-loading" role="status">
            Loading your workspace…
          </div>
        }
      >
        <Routes>
          <Route path="" element={<DashboardContainer />} />
          {routes.account.map(({ route, component: Component }) => (
            <Route
              key={route}
              path={`/account/${route}`.replace("//", "/")}
              element={<Component />}
            />
          ))}
          {blueprintRoutes.account
            .filter((route) => !route.adminOnly || rootAdmin)
            .map(({ path, component: Component }) => (
              <Route
                key={`bp-${path}`}
                path={`/account/${path}`.replace("//", "/")}
                element={<Component />}
              />
            ))}
          <Route path="*" element={<NotFound />} />
        </Routes>
      </Suspense>
    </ClientShell>
  );
};

export default DashboardRouter;
