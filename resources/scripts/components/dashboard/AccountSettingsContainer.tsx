import * as Tabs from "@radix-ui/react-tabs";
import { useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";

import MessageBox from "@/components/MessageBox";
import AccountApiContainer from "@/components/dashboard/AccountApiContainer";
import AccountOverviewContainer, {
  AccountSecuritySettings,
} from "@/components/dashboard/AccountOverviewContainer";
import AccountSSHContainer from "@/components/dashboard/ssh/AccountSSHContainer";
import PageContentBlock from "@/components/elements/PageContentBlock";

const sections = [
  { value: "profile", label: "Profile", path: "/account" },
  { value: "security", label: "Security", path: "/account/security" },
  { value: "api", label: "API keys", path: "/account/api" },
  { value: "ssh", label: "SSH keys", path: "/account/ssh" },
];

const AccountSettingsContainer = () => {
  const { pathname, state } = useLocation();
  const navigate = useNavigate();
  const active =
    sections.find((section) => section.path === pathname.replace(/\/$/, ""))
      ?.value || "profile";

  useEffect(() => {
    if (state?.twoFactorRedirect && active === "profile") {
      navigate("/account/security", { replace: true, state });
    }
  }, [state, active, navigate]);

  return (
    <PageContentBlock title="Account settings" className="account-settings">
      <header className="account-settings-heading">
        <h1>Settings</h1>
        <p>Manage your account, security, and access keys in one place.</p>
      </header>
      {state?.twoFactorRedirect && (
        <div className="account-settings-notice">
          <MessageBox title="Two-factor authentication required" type="error">
            Enable an authenticator app to continue using your account.
          </MessageBox>
        </div>
      )}
      <Tabs.Root
        value={active}
        onValueChange={(value) =>
          navigate(sections.find((section) => section.value === value)!.path)
        }
        activationMode="manual"
      >
        <Tabs.List
          className="account-settings-tabs"
          aria-label="Account settings"
        >
          {sections.map((section) => (
            <Tabs.Trigger
              key={section.value}
              value={section.value}
              className="account-settings-tab"
            >
              {section.label}
            </Tabs.Trigger>
          ))}
        </Tabs.List>
        <Tabs.Content value="profile" className="account-settings-panel">
          <AccountOverviewContainer />
        </Tabs.Content>
        <Tabs.Content value="security" className="account-settings-panel">
          <AccountSecuritySettings />
        </Tabs.Content>
        <Tabs.Content value="api" className="account-settings-panel">
          <AccountApiContainer />
        </Tabs.Content>
        <Tabs.Content value="ssh" className="account-settings-panel">
          <AccountSSHContainer />
        </Tabs.Content>
      </Tabs.Root>
    </PageContentBlock>
  );
};

export default AccountSettingsContainer;
