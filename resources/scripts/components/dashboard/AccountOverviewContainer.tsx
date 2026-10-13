import { Person, ShieldCheck } from "@gravity-ui/icons";
import { useStoreState } from "easy-peasy";
import type { ReactNode } from "react";

import FlashMessageRender from "@/components/FlashMessageRender";
import ConfigureTwoFactorForm from "@/components/dashboard/forms/ConfigureTwoFactorForm";
import UpdateEmailAddressForm from "@/components/dashboard/forms/UpdateEmailAddressForm";
import UpdatePasswordForm from "@/components/dashboard/forms/UpdatePasswordForm";
import Input from "@/components/elements/Input";
import UserAvatar from "@/components/elements/UserAvatar";

const SettingsRow = ({
  title,
  description,
  children,
}: {
  title: string;
  description: string;
  children: ReactNode;
}) => (
  <div className="account-settings-row">
    <div className="account-settings-row-label">
      <h3>{title}</h3>
      <p>{description}</p>
    </div>
    <div className="account-settings-row-control">{children}</div>
  </div>
);

const AccountOverviewContainer = () => {
  const user = useStoreState((state) => state.user.data!);

  return (
    <section aria-labelledby="account-profile-heading">
      <div className="account-settings-section-heading">
        <div>
          <h2 id="account-profile-heading">Profile details</h2>
          <p>
            Your account information and the email address we use to reach you.
          </p>
        </div>
      </div>
      <SettingsRow
        title="Your profile"
        description="This is the account you use to manage your servers."
      >
        <div className="account-settings-identity">
          <div className="account-settings-avatar" aria-hidden="true">
            <UserAvatar user={user} />
          </div>
          <div>
            <strong>{user.username}</strong>
            <span>{user.email}</span>
          </div>
          <span className="account-settings-status">
            {user.rootAdmin ? "Administrator" : "Member"}
          </span>
        </div>
      </SettingsRow>
      <SettingsRow
        title="Username"
        description="Contact your administrator if you need to change your username."
      >
        <label
          className="account-settings-input-label"
          htmlFor="account_username"
        >
          Username
        </label>
        <div className="account-settings-username">
          <Person aria-hidden="true" width={19} height={19} />
          <Input
            id="account_username"
            value={user.username}
            readOnly
            autoComplete="username"
          />
        </div>
      </SettingsRow>
      <SettingsRow
        title="Email address"
        description="Enter your current password to confirm a change to your email address."
      >
        <FlashMessageRender byKey="account:email" />
        <UpdateEmailAddressForm />
      </SettingsRow>
    </section>
  );
};

export const AccountSecuritySettings = () => {
  const isEnabled = useStoreState((state) => state.user.data!.useTotp);

  return (
    <section aria-labelledby="account-security-heading">
      <div className="account-settings-section-heading">
        <div>
          <h2 id="account-security-heading">Account security</h2>
          <p>Manage your password and add an extra layer of protection.</p>
        </div>
      </div>
      <SettingsRow
        title="Password"
        description="Choose a unique password that you do not use for any other account."
      >
        <FlashMessageRender byKey="account:password" />
        <UpdatePasswordForm />
      </SettingsRow>
      <SettingsRow
        title="Two-factor authentication"
        description="Use an authenticator app to confirm that it is you when you sign in."
      >
        <div
          className={`account-settings-security-status ${isEnabled ? "is-enabled" : ""}`}
        >
          <ShieldCheck aria-hidden="true" width={20} height={20} />
          <span>
            {isEnabled
              ? "Two-factor authentication is enabled"
              : "Two-factor authentication is not enabled"}
          </span>
        </div>
        <FlashMessageRender byKey="account:two-step" />
        <ConfigureTwoFactorForm />
      </SettingsRow>
    </section>
  );
};

export default AccountOverviewContainer;
