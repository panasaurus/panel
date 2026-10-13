import { Eye, EyeSlash, Key, Plus, TrashBin } from "@gravity-ui/icons";
import { format } from "date-fns";
import { Actions, useStoreActions } from "easy-peasy";
import { Field, Form, Formik, FormikHelpers } from "formik";
import { useCallback, useEffect, useState } from "react";
import { object, string } from "yup";

import FlashMessageRender from "@/components/FlashMessageRender";
import ApiKeyModal from "@/components/dashboard/ApiKeyModal";
import ActionButton from "@/components/elements/ActionButton";
import Code from "@/components/elements/Code";
import FormikFieldWrapper from "@/components/elements/FormikFieldWrapper";
import Input, { Textarea } from "@/components/elements/Input";
import SpinnerOverlay from "@/components/elements/SpinnerOverlay";
import { Dialog } from "@/components/elements/dialog";

import createApiKey from "@/api/account/createApiKey";
import deleteApiKey from "@/api/account/deleteApiKey";
import getApiKeys, { ApiKey } from "@/api/account/getApiKeys";
import { httpErrorToHuman } from "@/api/http";

import { ApplicationStore } from "@/state";

import BlueprintBeforeContent from "@blueprint/components/Account/API/BeforeContent";
import BlueprintAfterContent from "@blueprint/components/Account/API/AfterContent";

interface CreateValues {
  description: string;
  allowedIps: string;
}

const AccountApiContainer = () => {
  const [deleteIdentifier, setDeleteIdentifier] = useState("");
  const [keys, setKeys] = useState<ApiKey[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [apiKey, setApiKey] = useState("");
  const [showKeys, setShowKeys] = useState<Record<string, boolean>>({});

  const {
    addError,
    clearFlashes,
    clearAndAddHttpError: flashHttpError,
  } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

  const clearAndAddHttpError = useCallback(
    (error?: Error | string | null) =>
      flashHttpError({ key: "account:api-keys", error }),
    [flashHttpError],
  );

  useEffect(() => {
    getApiKeys()
      .then((keys) => setKeys(keys))
      .catch((error) => clearAndAddHttpError(error))
      .finally(() => setLoading(false));
  }, [clearAndAddHttpError]);

  const doDeletion = (identifier: string) => {
    setLoading(true);
    clearAndAddHttpError();
    deleteApiKey(identifier)
      .then(() =>
        setKeys((s) => [
          ...(s || []).filter((key) => key.identifier !== identifier),
        ]),
      )
      .catch((error) => clearAndAddHttpError(error))
      .then(() => {
        setLoading(false);
        setDeleteIdentifier("");
      });
  };

  const submitCreate = (
    values: CreateValues,
    { setSubmitting, resetForm }: FormikHelpers<CreateValues>,
  ) => {
    clearFlashes("account:api-keys");
    createApiKey(values.description, values.allowedIps)
      .then(({ secretToken, ...key }) => {
        resetForm();
        setSubmitting(false);
        setApiKey(`${key.identifier}${secretToken}`);
        setKeys((s) => [...s!, key]);
        setShowCreateModal(false);
      })
      .catch((error) => {
        console.error(error);
        addError({ key: "account:api-keys", message: httpErrorToHuman(error) });
        setSubmitting(false);
      });
  };

  const toggleKeyVisibility = (identifier: string) => {
    setShowKeys((prev) => ({
      ...prev,
      [identifier]: !prev[identifier],
    }));
  };

  return (
    <section
      className="account-settings-key-section"
      aria-labelledby="account-api-heading"
    >
      <BlueprintBeforeContent />
      <FlashMessageRender byKey="account:api-keys" />
      <ApiKeyModal
        visible={apiKey.length > 0}
        onModalDismissed={() => setApiKey("")}
        apiKey={apiKey}
      />
      {showCreateModal && (
        <Dialog.Confirm
          open={showCreateModal}
          onClose={() => setShowCreateModal(false)}
          title="Create API key"
          confirm="Create Key"
          onConfirmed={() => {
            const form = document.getElementById(
              "create-api-form",
            ) as HTMLFormElement;
            if (form) {
              const submitButton = form.querySelector(
                'button[type="submit"]',
              ) as HTMLButtonElement;
              if (submitButton) submitButton.click();
            }
          }}
        >
          <Formik
            onSubmit={submitCreate}
            initialValues={{ description: "", allowedIps: "" }}
            validationSchema={object().shape({
              allowedIps: string(),
              description: string().required().min(4),
            })}
          >
            {({ isSubmitting }) => (
              <Form id="create-api-form" className="space-y-4">
                <SpinnerOverlay visible={isSubmitting} />

                <FormikFieldWrapper
                  label="Description"
                  name="description"
                  description="A description of this API key."
                >
                  <Field name="description" as={Input} className="w-full" />
                </FormikFieldWrapper>

                <FormikFieldWrapper
                  label="Allowed IPs"
                  name="allowedIps"
                  description="Leave blank to allow any IP address to use this API key, otherwise provide each IP address on a new line. Note: You can also use CIDR ranges here."
                >
                  <Field
                    name="allowedIps"
                    as={Textarea}
                    rows={3}
                    className="w-full"
                    placeholder="192.0.2.10"
                  />
                </FormikFieldWrapper>

                <button type="submit" className="hidden" />
              </Form>
            )}
          </Formik>
        </Dialog.Confirm>
      )}

      <div className="account-settings-key-content">
        <div>
          <div className="account-settings-section-heading">
            <div>
              <h2 id="account-api-heading">API keys</h2>
              <p>
                Create access keys for apps and tools that connect to your
                account.
              </p>
            </div>
            <ActionButton
              variant="primary"
              onClick={() => setShowCreateModal(true)}
              className="flex items-center gap-2"
            >
              <Plus width={22} height={22} fill="currentColor" />
              Create API key
            </ActionButton>
          </div>
        </div>

        <div>
          <div className="account-settings-key-list">
            <SpinnerOverlay visible={loading} />
            <Dialog.Confirm
              title={"Delete API key"}
              confirm={"Delete Key"}
              open={!!deleteIdentifier}
              onClose={() => setDeleteIdentifier("")}
              onConfirmed={() => doDeletion(deleteIdentifier)}
            >
              All requests using the <Code>{deleteIdentifier}</Code> key will be
              invalidated.
            </Dialog.Confirm>

            {keys.length === 0 ? (
              <div className="text-center py-12">
                <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-[#ffffff11] flex items-center justify-center">
                  <Key
                    width={22}
                    height={22}
                    className="text-zinc-400"
                    fill="currentColor"
                  />
                </div>
                <h3 className="text-lg font-medium text-zinc-200 mb-2">
                  No API keys
                </h3>
                <p className="text-sm text-zinc-400 max-w-sm mx-auto">
                  {loading
                    ? "Loading your API keys..."
                    : "You haven't created any API keys yet. Create one to get started with the API."}
                </p>
              </div>
            ) : (
              <div className="space-y-3">
                {keys.map((key) => (
                  <div
                    key={key.identifier}
                    className="account-settings-key-row"
                  >
                    <div className="account-settings-key-card">
                      <div className="flex items-center justify-between">
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-3 mb-2">
                            <h4 className="text-sm font-medium text-zinc-100 truncate">
                              {key.description}
                            </h4>
                          </div>
                          <div className="client-api-key-meta flex items-center gap-4 text-xs text-zinc-400">
                            <span>
                              Last used:{" "}
                              {key.lastUsedAt
                                ? format(key.lastUsedAt, "MMM d, yyyy HH:mm")
                                : "Never"}
                            </span>
                            <div className="flex items-center gap-2">
                              <span>Key:</span>
                              <code className="font-mono px-2 py-1 bg-[#ffffff08] border border-[#ffffff08] rounded text-zinc-300">
                                {showKeys[key.identifier]
                                  ? key.identifier
                                  : "••••••••••••••••"}
                              </code>
                              <ActionButton
                                variant="secondary"
                                size="sm"
                                aria-label={
                                  showKeys[key.identifier]
                                    ? "Hide key identifier"
                                    : "Show key identifier"
                                }
                                onClick={() =>
                                  toggleKeyVisibility(key.identifier)
                                }
                                className="p-1 text-zinc-400 hover:text-zinc-300"
                              >
                                {showKeys[key.identifier] ? (
                                  <EyeSlash
                                    width={18}
                                    height={18}
                                    fill="currentColor"
                                  />
                                ) : (
                                  <Eye
                                    width={18}
                                    height={18}
                                    fill="currentColor"
                                  />
                                )}
                              </ActionButton>
                            </div>
                          </div>
                        </div>
                        <ActionButton
                          variant="danger"
                          size="sm"
                          className="ml-4"
                          aria-label={`Delete API key ${key.description}`}
                          onClick={() => setDeleteIdentifier(key.identifier)}
                        >
                          <TrashBin
                            width={20}
                            height={20}
                            fill="currentColor"
                          />
                        </ActionButton>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
      <BlueprintAfterContent />
    </section>
  );
};

export default AccountApiContainer;
